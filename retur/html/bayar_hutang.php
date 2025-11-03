<?php
require_once '..Database/config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();

    $hutang_id = intval($_POST['hutang_id'] ?? 0);
    $jumlah_bayar = intval($_POST['jumlah_bayar'] ?? 0);
    $metode_bayar = $_POST['metode_bayar'] ?? 'Tunai';

    if ($hutang_id <= 0 || $jumlah_bayar <= 0) {
        throw new Exception('Data tidak valid');
    }

    // Simpan pembayaran
    $sql = "INSERT INTO pembayaran_hutang (hutang_id, jumlah_bayar, metode_bayar) 
            VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$hutang_id, $jumlah_bayar, $metode_bayar])) {
        echo json_encode(['status' => 'success', 'message' => 'Pembayaran berhasil dicatat']);
    } else {
        throw new Exception('Gagal mencatat pembayaran');
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>