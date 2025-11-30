<?php
// File: webhook.php

// 1. Terima data dari Go
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data) {
    // Siapkan data pesan baru
    $newMessage = [
        "type" => "in", // 'in' artinya pesan masuk
        "sender" => $data['sender'],
        "message" => $data['message'],
        "time" => date("H:i")
    ];

    saveMessage($newMessage);
}

// Fungsi Simpan ke JSON
function saveMessage($msg) {
    $file = 'db_chat.json';
    
    // Baca data lama jika ada
    $currentData = [];
    if (file_exists($file)) {
        $jsonContent = file_get_contents($file);
        $currentData = json_decode($jsonContent, true);
        if (!$currentData) $currentData = [];
    }

    // Tambahkan pesan baru ke atas (biar terbaru muncul duluan)
    array_unshift($currentData, $msg);

    // Simpan kembali
    file_put_contents($file, json_encode($currentData, JSON_PRETTY_PRINT));
}

echo "OK";
?>