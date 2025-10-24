<?php
header('Content-Type: application/json');
require_once '../Database/config.php';

$conn = getDBConnection();

// Ambil data dari POST request
$id = intval($_POST['id'] ?? 0);
$tipe = $conn->real_escape_string(trim($_POST['tipe'] ?? ''));
$pemasok = $conn->real_escape_string(trim($_POST['pemasok'] ?? ''));
$stok = intval($_POST['stok'] ?? 0);
$harga_dasar = intval($_POST['harga_dasar'] ?? 0);
$harga_jual = intval($_POST['harga_jual'] ?? 0);

// Validasi input
if ($id <= 0 || empty($tipe) || empty($pemasok) || $stok < 0 || $harga_dasar < 0 || $harga_jual < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
    exit;
}

// Query update
$sql = "UPDATE stok_barang 
        SET tipe = '$tipe', 
            pemasok = '$pemasok', 
            stok = $stok, 
            harga_dasar = $harga_dasar, 
            harga_jual = $harga_jual,
            updated_at = NOW()
        WHERE id = $id";

if ($conn->query($sql) === TRUE) {
    if ($conn->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil diupdate']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan atau tidak ada perubahan']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal update data: ' . $conn->error]);
}

$conn->close();
?>