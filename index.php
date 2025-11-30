<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Gateway Scan</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; background-color: #f4f4f4; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); display: inline-block; }
        h1 { color: #25D366; }
        #status { margin-bottom: 20px; font-weight: bold; color: #555; }
        #qrcode { display: flex; justify-content: center; margin: 20px 0; }
        .hidden { display: none; }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>

<div class="container">
    <h1>WhatsApp Gateway</h1>
    <p>Silakan buka WhatsApp di HP > Perangkat Tertaut > Tautkan Perangkat</p>
    
    <div id="status">Menghubungkan ke Server Go...</div>
    
    <div id="qrcode"></div>
    
    <div id="success-msg" class="hidden">
        <h3>✅ Berhasil Terhubung!</h3>
        <p>Gateway siap digunakan.</p>
    </div>
</div>

<script>
    let qrContainer = document.getElementById("qrcode");
    let statusText = document.getElementById("status");
    let successMsg = document.getElementById("success-msg");
    let lastQR = "";

    function checkStatus() {
        // Tembak API Go untuk cek status
        fetch('http://localhost:8080/qr')
            .then(response => response.json())
            .then(data => {
                if (data.connected) {
                    // JIKA SUDAH LOGIN
                    statusText.innerText = "Status: Terhubung";
                    qrContainer.innerHTML = ""; // Hapus QR
                    successMsg.classList.remove("hidden");
                } else if (data.qr_code && data.qr_code !== "") {
                    // JIKA BELUM LOGIN & ADA QR CODE BARU
                    statusText.innerText = "Status: Scan QR Code di bawah";
                    successMsg.classList.add("hidden");

                    // Hanya render ulang jika QR Code berubah (agar tidak kedip-kedip)
                    if (data.qr_code !== lastQR) {
                        lastQR = data.qr_code;
                        qrContainer.innerHTML = ""; // Bersihkan QR lama
                        new QRCode(qrContainer, {
                            text: data.qr_code,
                            width: 256,
                            height: 256
                        });
                    }
                } else {
                    statusText.innerText = "Menunggu QR Code dari Server...";
                }
            })
            .catch(error => {
                statusText.innerText = "Error: Gagal konek ke Server Go (Pastikan Go jalan!)";
                console.error(error);
            });
    }

    // Cek setiap 1 detik
    setInterval(checkStatus, 1000);
    checkStatus(); // Jalankan langsung saat load
</script>

</body>
</html>