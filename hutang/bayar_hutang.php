<?php
require_once '../Database/config.php';
require_once '../Database/functions.php'; // TAMBAH INI

header('Content-Type: application/json');

try {
    // ... kode existing ...
    
    // GUNAKAN FUNCTION YANG SUDAH ADA
    if (bayarHutang($hutang_id, $jumlah_bayar, $_SESSION['username'] ?? 'system')) {
        echo json_encode(['status' => 'success', 'message' => 'Pembayaran berhasil dicatat']);
    } else {
        throw new Exception('Gagal mencatat pembayaran');
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>