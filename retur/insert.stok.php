<?php
header('Content-Type: application/json');
require_once 'config.php'; // Include file config

$conn = getDBConnection();

// Ambil data dari POST request
$tipe = $conn->real_escape_string(trim($_POST['tipe'] ?? ''));
$pemasok = $conn->real_escape_string(trim($_POST['pemasok'] ?? ''));
$stok = intval($_POST['stok'] ?? 0);
$harga_dasar = intval($_POST['harga_dasar'] ?? 0);
$harga_jual = intval($_POST['harga_jual'] ?? 0);

// Validasi input
if (empty($tipe) || empty($pemasok) || $stok < 0 || $harga_dasar < 0 || $harga_jual < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
    exit;
}

// Query insert
$sql = "INSERT INTO stok_barang (tipe, pemasok, stok, harga_dasar, harga_jual, created_at) 
        VALUES ('$tipe', '$pemasok', $stok, $harga_dasar, $harga_jual, NOW())";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['status' => 'success', 'message' => 'Data berhasil disimpan']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $conn->error]);
}

$conn->close();
?>