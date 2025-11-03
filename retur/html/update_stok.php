<?php
header('Content-Type: application/json');
require_once '..Database/config.php';

try {
    $conn = getDBConnection();
    $table = 'stok_barang';

    // PERBAIKI SYNTAX DI BAWAH INI:
    $id = intval($_POST['id'] ?? 0);  // HAPUS: (value: ...)
    $nama = $_POST['nama'] ?? '';     // HAPUS: (value: ...)
    $tipe = $_POST['tipe'] ?? '';
    $pemasok = $_POST['pemasok'] ?? '';
    $stok = intval($_POST['stok'] ?? 0);
    $harga_dasar = intval($_POST['harga_dasar'] ?? 0);
    $harga_jual = intval($_POST['harga_jual'] ?? 0);

    if ($id <= 0) {
        throw new Exception('ID barang tidak valid');  // HAPUS: (message: ...)
    }
    
    // Cek apakah barang exists
    $check_sql = "SELECT id FROM $table WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$id]);
    
    if ($check_stmt->rowCount() === 0) {
        throw new Exception('Data tidak ditemukan!');  // HAPUS: (message: ...)
    }

    // Update data
    $sql = "UPDATE $table 
            SET nama = ?, 
                tipe = ?, 
                pemasok = ?, 
                stok = ?, 
                harga_dasar = ?, 
                harga_jual = ?,
                updated_at = NOW()
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$nama, $tipe, $pemasok, $stok, $harga_dasar, $harga_jual, $id])) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Data berhasil diupdate']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada perubahan data']);
        }
    } else {
        throw new Exception('Gagal update data');
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>