<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Dashboard</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #e5ddd5; margin: 0; display: flex; justify-content: center; height: 100vh; }
        .container { width: 100%; max-width: 900px; background: white; display: flex; flex-direction: column; box-shadow: 0 0 20px rgba(0,0,0,0.1); height: 100vh; }
        
        /* Header */
        .header { background-color: #075e54; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
        .header h2 { margin: 0; font-size: 18px; }
        
        /* Area Pesan (Tengah) */
        .chat-area { flex: 1; overflow-y: auto; padding: 20px; background-color: #e5ddd5; display: flex; flex-direction: column-reverse; /* Pesan baru di bawah jika pakai normal, tapi kita balik urutan array */ }
        
        .bubble { max-width: 70%; padding: 10px 15px; border-radius: 10px; margin-bottom: 10px; position: relative; font-size: 14px; line-height: 1.4; box-shadow: 0 1px 1px rgba(0,0,0,0.1); }
        
        .incoming { align-self: flex-start; background-color: white; border-top-left-radius: 0; }
        .outgoing { align-self: flex-end; background-color: #dcf8c6; border-top-right-radius: 0; }
        
        .sender-name { font-size: 11px; font-weight: bold; color: #e542a3; margin-bottom: 3px; display: block; }
        .time { font-size: 10px; color: #999; float: right; margin-top: 5px; margin-left: 10px; }

        /* Form Kirim (Bawah) */
        .input-area { background-color: #f0f0f0; padding: 15px; display: flex; align-items: center; gap: 10px; border-top: 1px solid #ddd; }
        input[type="text"] { padding: 12px; border: 1px solid #ccc; border-radius: 20px; outline: none; }
        #phone { width: 150px; }
        #message { flex: 1; }
        button { background-color: #075e54; color: white; border: none; padding: 12px 20px; border-radius: 50%; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #128c7e; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>WhatsApp Gateway</h2>
        <span id="status" style="font-size: 12px;">● Online</span>
    </div>

    <div class="chat-area" id="chatBox">
        <div style="text-align: center; color: #888; margin-top: 20px;">Belum ada pesan</div>
    </div>

    <div class="input-area">
        <input type="text" id="phone" placeholder="Nomor (628...)" required>
        <input type="text" id="message" placeholder="Ketik pesan..." required>
        <button onclick="kirimPesan()">➤</button>
    </div>
</div>

<script>
    const chatBox = document.getElementById('chatBox');
    
    // 1. Fungsi Mengambil Data Pesan (Auto Refresh)
    function loadMessages() {
        // Tambahkan timestamp agar tidak dicache browser
        fetch('db_chat.json?t=' + new Date().getTime())
            .then(response => {
                if (!response.ok) return []; 
                return response.json();
            })
            .then(data => {
                if (!data) return;
                
                let html = '';
                // Looping data
                data.forEach(msg => {
                    let typeClass = msg.type === 'in' ? 'incoming' : 'outgoing';
                    let senderDisplay = msg.type === 'in' ? `<span class="sender-name">${msg.sender}</span>` : '';
                    
                    html += `
                        <div class="bubble ${typeClass}">
                            ${senderDisplay}
                            ${msg.message}
                            <span class="time">${msg.time}</span>
                        </div>
                    `;
                });
                chatBox.innerHTML = html;
            })
            .catch(err => console.log('Belum ada database chat'));
    }

    // 2. Fungsi Mengirim Pesan
    function kirimPesan() {
        let phone = document.getElementById('phone').value;
        let message = document.getElementById('message').value;
        let btn = document.querySelector('button');

        if (!phone || !message) {
            alert("Isi nomor dan pesan!");
            return;
        }

        btn.disabled = true;
        btn.innerHTML = "...";

        fetch('kirim.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone: phone, message: message })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('message').value = ''; // Kosongkan input pesan
                loadMessages(); // Refresh chat langsung
            } else {
                alert('Gagal kirim');
            }
            btn.disabled = false;
            btn.innerHTML = "➤";
        })
        .catch(err => {
            alert("Error koneksi");
            btn.disabled = false;
            btn.innerHTML = "➤";
        });
    }

    // Jalankan loadMessages tiap 1 detik
    setInterval(loadMessages, 1000);
    loadMessages();
</script>

</body>
</html>