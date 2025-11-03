<?php
require_once '..Database/config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();

    $barang_id = intval($_POST['barang_id'] ?? 0);
    $jumlah = intval($_POST['jumlah'] ?? 0);
    $alasan = $_POST['alasan'] ?? '';
    $alasan_lainnya = $_POST['alasan_lainnya'] ?? '';
    $keterangan = $_POST['keterangan'] ?? '';

    if ($barang_id <= 0 || $jumlah <= 0) {
        throw new Exception('Data tidak valid');
    }

    // Cek stok tersedia
    $check_sql = "SELECT nama, stok FROM stok_barang WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$barang_id]);
    $barang = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$barang) {
        throw new Exception('Barang tidak ditemukan');
    }

    if ($jumlah > $barang['stok']) {
        throw new Exception('Jumlah retur melebihi stok tersedia');
    }

    // Kurangi stok
    $update_sql = "UPDATE stok_barang SET stok = stok - ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->execute([$jumlah, $barang_id]);

    // Simpan data retur
    $retur_sql = "INSERT INTO retur_barang (barang_id, jumlah, alasan, alasan_lainnya, keterangan) 
                  VALUES (?, ?, ?, ?, ?)";
    $retur_stmt = $conn->prepare($retur_sql);
    $retur_stmt->execute([$barang_id, $jumlah, $alasan, $alasan_lainnya, $keterangan]);

    // Catat di riwayat
    $riwayat_sql = "INSERT INTO riwayat_transaksi (jenis_transaksi, barang_id, nama_barang, keterangan, perubahan_stok) 
                   VALUES ('Retur', ?, ?, ?, ?)";
    $riwayat_stmt = $conn->prepare($riwayat_sql);
    $keterangan_riwayat = "Retur {$jumlah} pcs - {$alasan}" . ($alasan_lainnya ? " - {$alasan_lainnya}" : "");
    $perubahan_stok = -$jumlah;
    $riwayat_stmt->execute([$barang_id, $barang['nama'], $keterangan_riwayat, $perubahan_stok]);

    echo json_encode(['status' => 'success', 'message' => 'Retur berhasil diproses']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>