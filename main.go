package main

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"os"
	"os/signal"
	"sync"
	"syscall"
	"time"

	_ "github.com/glebarez/go-sqlite"
	"go.mau.fi/whatsmeow"
	waProto "go.mau.fi/whatsmeow/binary/proto"
	"go.mau.fi/whatsmeow/store/sqlstore"
	"go.mau.fi/whatsmeow/types"
	"go.mau.fi/whatsmeow/types/events"
	waLog "go.mau.fi/whatsmeow/util/log"
)

// --- KONFIGURASI ---
const WebhookURL = "http://localhost/whatsme/webhook.php"
const Port = "8080"

var (
	client      *whatsmeow.Client
	currentQR   string     // Menyimpan string QR Code saat ini
	isLoggedIn  bool       // Status login
	qrLock      sync.Mutex // Untuk mencegah rebutan data (Race Condition)
)

type QRResponse struct {
	QRCode    string `json:"qr_code"`
	Connected bool   `json:"connected"`
}

func main() {
	dbLog := waLog.Stdout("Database", "DEBUG", true)
	container, err := sqlstore.New(context.Background(), "sqlite", "file:wa_session.db?_pragma=foreign_keys(1)", dbLog)
	if err != nil {
		panic(err)
	}

	deviceStore, err := container.GetFirstDevice(context.Background())
	if err != nil {
		panic(err)
	}

	clientLog := waLog.Stdout("Client", "DEBUG", true)
	client = whatsmeow.NewClient(deviceStore, clientLog)
	client.AddEventHandler(eventHandler)

	// Cek apakah sudah login sebelumnya
	if client.Store.ID != nil {
		isLoggedIn = true
		err = client.Connect()
		if err != nil {
			panic(err)
		}
	} else {
		// Jika belum login, siapkan channel QR (JANGAN connect dulu di sini)
		// Kita akan connect via Goroutine agar tidak memblokir server HTTP
		go func() {
			qrChan, _ := client.GetQRChannel(context.Background())
			err = client.Connect()
			if err != nil {
				panic(err)
			}
			for evt := range qrChan {
				qrLock.Lock()
				if evt.Event == "code" {
					currentQR = evt.Code
					isLoggedIn = false
					fmt.Println("QR Code baru diterima (buka browser untuk scan)")
				} else {
					// Event lain (misal login sukses)
					fmt.Println("Login event:", evt.Event)
					if evt.Event == "success" {
						isLoggedIn = true
						currentQR = ""
					}
				}
				qrLock.Unlock()
			}
		}()
	}

	// --- HTTP ROUTES ---
	http.HandleFunc("/send", sendMessageHandler) // Kirim Pesan
	http.HandleFunc("/qr", getQRHandler)         // Ambil Data QR (Untuk PHP)

	fmt.Printf("Gateway berjalan di http://localhost:%s\n", Port)
	
	// Keep Alive
	c := make(chan os.Signal, 1)
	signal.Notify(c, os.Interrupt, syscall.SIGTERM)
	go func() {
		<-c
		client.Disconnect()
		os.Exit(0)
	}()

	log.Fatal(http.ListenAndServe(":"+Port, nil))
}

// --- API BARU: GET QR CODE ---
func getQRHandler(w http.ResponseWriter, r *http.Request) {
	// Tambahkan Header CORS agar bisa diakses dari PHP/JS
	w.Header().Set("Access-Control-Allow-Origin", "*")
	w.Header().Set("Content-Type", "application/json")

	qrLock.Lock()
	resp := QRResponse{
		QRCode:    currentQR,
		Connected: isLoggedIn,
	}
	// Cek manual jika client sudah ready
	if client.IsConnected() && client.Store.ID != nil {
		resp.Connected = true
	}
	qrLock.Unlock()

	json.NewEncoder(w).Encode(resp)
}

// --- API LAMA: KIRIM PESAN ---
type SendMessageRequest struct {
	Phone   string `json:"phone"`
	Message string `json:"message"`
}

func sendMessageHandler(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Access-Control-Allow-Origin", "*") // Fix CORS
	
	if r.Method != http.MethodPost {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	var req SendMessageRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, "Bad request", http.StatusBadRequest)
		return
	}

	jid, err := types.ParseJID(req.Phone + "@s.whatsapp.net")
	if err != nil {
		http.Error(w, "Format nomor salah", http.StatusBadRequest)
		return
	}

	msg := &waProto.Message{Conversation: addr(req.Message)}
	_, err = client.SendMessage(context.Background(), jid, msg)
	if err != nil {
		http.Error(w, fmt.Sprintf("Gagal: %v", err), 500)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"status": "ok"})
}

// --- LOGIC LAINNYA ---
func eventHandler(evt interface{}) {
	switch v := evt.(type) {
	case *events.Message:
		if !v.Info.IsFromMe && v.Message.GetConversation() != "" {
			sender := v.Info.Sender.User
			msgText := v.Message.GetConversation()
			fmt.Printf("Pesan dari %s: %s\n", sender, msgText)
			go forwardToPHP(sender, msgText)
		}
	// Update status login jika berhasil connect
	case *events.Connected:
		qrLock.Lock()
		isLoggedIn = true
		currentQR = ""
		qrLock.Unlock()
		fmt.Println("Terhubung ke WhatsApp!")
	}
}

func forwardToPHP(sender, message string) {
	payload := map[string]string{"sender": sender, "message": message}
	jsonPayload, _ := json.Marshal(payload)
	httpClient := http.Client{Timeout: 5 * time.Second}
	resp, err := httpClient.Post(WebhookURL, "application/json", bytes.NewBuffer(jsonPayload))
	if err != nil {
		return
	}
	defer resp.Body.Close()
}

func addr(s string) *string { return &s }