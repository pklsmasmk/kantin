<?php
require_once '..Database/config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();

    if (!$conn) {
        throw new Exception('Koneksi database gagal');
    }

    // Cek dulu apakah tabel hutang_pembelian ada
    $checkTable = $conn->query("SHOW TABLES LIKE 'hutang_pembelian'")->rowCount();

    if ($checkTable === 0) {
        // Jika tabel tidak ada, return array kosong
        echo json_encode([]);
        exit;
    }

    $sql = "SELECT h.*, sb.nama as nama_barang, sb.tipe,
                   (SELECT COALESCE(SUM(jumlah_bayar), 0) FROM pembayaran_hutang WHERE hutang_id = h.id) as total_bayar,
                   (h.harga_total - (SELECT COALESCE(SUM(jumlah_bayar), 0) FROM pembayaran_hutang WHERE hutang_id = h.id)) as sisa_hutang
            FROM hutang_pembelian h
            JOIN stok_barang sb ON h.barang_id = sb.id
            ORDER BY h.tanggal DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);

} catch (Exception $e) {
    // Return array kosong jika error
    echo json_encode([]);
}
?>