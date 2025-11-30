<?php
// File: kirim.php

header('Content-Type: application/json');

// 1. Ambil input dari UI
$input = json_decode(file_get_contents('php://input'), true);
$nomor = $input['phone'];
$pesan = $input['message'];

// 2. Kirim ke Go Gateway
$url = 'http://localhost:8080/send';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["phone" => $nomor, "message" => $pesan]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 3. Jika sukses kirim ke Go, simpan ke JSON history
if ($httpCode == 200) {
    $newMessage = [
        "type" => "out", // 'out' artinya pesan keluar (kita yang kirim)
        "sender" => $nomor,
        "message" => $pesan,
        "time" => date("H:i")
    ];
    saveMessage($newMessage);
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "detail" => $result]);
}

function saveMessage($msg) {
    $file = 'db_chat.json';
    $currentData = [];
    if (file_exists($file)) {
        $jsonContent = file_get_contents($file);
        $currentData = json_decode($jsonContent, true);
        if (!$currentData) $currentData = [];
    }
    array_unshift($currentData, $msg);
    file_put_contents($file, json_encode($currentData, JSON_PRETTY_PRINT));
}
?>