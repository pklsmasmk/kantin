<?php
header('Content-Type: application/json');
require_once '../Database/config.php';

try {
    $conn = getDBConnection();

    // Debug: log data yang diterima
    error_log("Data POST: " . print_r($_POST, true));
    
    $nama = $_POST['nama'] ?? '';
    $tipe = $_POST['tipe'] ?? '';
    $pemasok = $_POST['pemasok'] ?? '';
    $stok = intval($_POST['stok'] ?? 0);
    $harga_dasar = intval($_POST['harga_dasar'] ?? 0);
    $harga_jual = intval($_POST['harga_jual'] ?? 0);

    // Debug: log setelah processing
    error_log("Processed - Nama: $nama, Tipe: $tipe, Stok: $stok");
    
    if (empty($nama) || empty($tipe)) {
        throw new Exception('Nama dan tipe harus diisi');
    }

    $sql = "INSERT INTO stok_barang (nama, tipe, pemasok, stok, harga_dasar, harga_jual) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$nama, $tipe, $pemasok, $stok, $harga_dasar, $harga_jual])) {
        $barang_id = $conn->lastInsertId();

        $riwayat_sql = "INSERT INTO riwayat_transaksi (jenis_transaksi, barang_id, nama_barang, keterangan, perubahan_stok) 
                       VALUES ('Tambah Barang', ?, ?, 'Menambah barang baru', ?)";
        $riwayat_stmt = $conn->prepare($riwayat_sql);
        $riwayat_stmt->execute([$barang_id, $nama, $stok]);

        echo json_encode(['status' => 'success', 'message' => 'Barang berhasil ditambahkan']);
    } else {
        throw new Exception('Gagal menambah barang');
    }

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>