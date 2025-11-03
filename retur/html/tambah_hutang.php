<?php
require_once '..Database/config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();

    $barang_id = intval($_POST['barang_id'] ?? 0);
    $jumlah = intval($_POST['jumlah'] ?? 0);
    $harga_total = intval($_POST['harga_total'] ?? 0);
    $keterangan = $_POST['keterangan'] ?? '';
    $tanggal_jatuh_tempo = $_POST['tanggal_jatuh_tempo'] ?? '';

    if ($barang_id <= 0 || $jumlah <= 0 || $harga_total <= 0) {
        throw new Exception('Data tidak valid');
    }

    // Insert data hutang
    $sql = "INSERT INTO hutang_pembelian (barang_id, jumlah, harga_total, keterangan, tanggal_jatuh_tempo, status) 
            VALUES (?, ?, ?, ?, ?, 'BELUM LUNAS')";
    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$barang_id, $jumlah, $harga_total, $keterangan, $tanggal_jatuh_tempo])) {

        // UPDATE STOK BARANG - TAMBAHKAN INI
        $update_sql = "UPDATE stok_barang SET stok = stok + ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->execute([$jumlah, $barang_id]);

        echo json_encode(['status' => 'success', 'message' => 'Hutang berhasil dicatat']);
    } else {
        throw new Exception('Gagal mencatat hutang');
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>