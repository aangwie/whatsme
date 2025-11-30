### 📱 WhatsApp Gateway & Simple Chat Dashboard
Aplikasi ini adalah solusi WhatsApp Gateway self-hosted yang menggabungkan performa tinggi Go (Golang) sebagai backend koneksi WhatsApp, dengan kemudahan PHP sebagai frontend antarmuka chat.

### Fitur utama:

- **Scan QR Code via Web:** Tidak perlu melihat terminal, scan langsung dari browser.

- **Kirim Pesan API:** Endpoint HTTP untuk mengirim pesan.

- **Webhook & Receive:** Menerima pesan masuk dan meneruskannya ke PHP.

- **Chat Dashboard:** Antarmuka web sederhana untuk kirim & terima pesan secara real-time.

- **Tanpa Dependency C:** Menggunakan driver SQLite Pure Go (mudah diinstall di Windows).

---

### 📋 Prasyarat Sistem
Sebelum memulai, pastikan komputer Anda sudah terinstal:

- **Go (Golang):** Versi 1.18 ke atas. Download di sini.

- **XAMPP (Apache & PHP):** Atau web server PHP lainnya. Download di sini.

- **Git:** (Opsional) Untuk clone library.

---

### 🛠️ Langkah 1: Instalasi Backend (Go)
1. Bagian ini bertugas menangani koneksi ke WhatsApp.

2. Buat folder baru untuk proyek Go (bebas di mana saja), misalnya di C:\Projects\wa-gateway.

3. Buka terminal/CMD di folder tersebut.

4. Inisialisasi modul Go:

Bash
`go mod init wa-gateway`
Install library yang dibutuhkan:

Bash

# Library WhatsApp
`go get go.mau.fi/whatsmeow`

# Library Database SQLite (Versi Pure Go - Aman untuk Windows)
`go get github.com/glebarez/go-sqlite`

# Library Generate QR Terminal (Opsional, untuk debug)
`go get github.com/mdp/qrterminal/v3`
Pastikan file main.go sudah berisi kode terakhir yang kita buat (yang mendukung Webhook, CORS, dan API QR).

### 💻 Langkah 2: Instalasi Frontend (PHP)
Bagian ini bertugas menampilkan antarmuka Chat dan Scan QR.

1. Masuk ke folder htdocs XAMPP Anda. Biasanya di: C:\xampp\htdocs\

2. Buat folder baru bernama whatsme.

Di dalam folder whatsme, pastikan terdapat 4 file PHP berikut:

- index.php: Halaman untuk Scan QR Code.

- chat.php: Halaman Dashboard Chat kirim/terima.

- webhook.php: Skript penerima pesan dari Go & penyimpan log.

- kirim.php: Skript pengirim pesan (jembatan antara UI ke Go).

(File db_chat.json akan otomatis dibuat oleh sistem saat ada pesan masuk/keluar).

### ⚙️ Langkah 3: Konfigurasi
Sebelum menjalankan, cek kesesuaian konfigurasi URL:

1. Buka file main.go (Go).

2. Cari baris const WebhookURL.

Pastikan URL-nya mengarah ke file PHP Anda dengan benar:

Go

`const WebhookURL = "http://localhost/whatsme/webhook.php"`
(Jika nama folder di htdocs bukan whatsme, sesuaikan bagian ini).

### 🚀 Langkah 4: Menjalankan Aplikasi
Lakukan langkah ini setiap kali ingin menggunakan aplikasi.

1. Jalankan XAMPP
Buka XAMPP Control Panel dan klik Start pada module Apache.

2. Jalankan Backend Go
Buka terminal di folder project Go (wa-gateway), lalu ketik:

Bash

`go run main.go`
Tunggu hingga muncul pesan: Gateway berjalan di http://localhost:8080

3. Buka Aplikasi di Browser
A. Tautkan Perangkat (Login)

Buka browser dan akses: http://localhost/whatsme/index.php

Akan muncul QR Code.

Buka WhatsApp di HP Anda > Perangkat Tertaut > Tautkan Perangkat.

Scan QR Code di layar komputer.

Halaman akan berubah menjadi "Berhasil Terhubung!".

B. Mulai Chatting

Buka browser dan akses: http://localhost/whatsme/chat.php

Masukkan nomor tujuan (format: 62812xxx) dan isi pesan.

Klik tombol kirim.

Jika ada balasan dari HP tujuan, pesan akan muncul otomatis di layar chat.

### ❓ Troubleshooting (Masalah Umum)
Error: connection refused saat kirim pesan

Pastikan aplikasi Go (go run main.go) sedang berjalan di terminal.

- **Pesan tidak muncul di Chat UI**

Cek file `db_chat.json` di folder htdocs/whatsme. Jika tidak ada, pastikan PHP memiliki izin untuk menulis file (Write Permission) di folder tersebut.

Pastikan URL Webhook di main.go sudah benar.

- **QR Code tidak muncul**

Pastikan Anda membuka http://localhost/whatsme/index.php (lewat Apache), bukan membuka file .php langsung (klik 2x).

Database Locked / Error SQL

Hapus file `wa_session.db` di folder Go, lalu restart aplikasi Go untuk login ulang dari awal.

### ⚠️ Disclaimer
Aplikasi ini menggunakan library pihak ketiga (whatsmeow) dan bukan solusi resmi dari WhatsApp/Meta.

Gunakan dengan bijak.

Risiko pemblokiran nomor (Banned) ditanggung pengguna jika digunakan untuk SPAM atau aktivitas massal.

Disarankan menggunakan nomor sekunder untuk pengujian.