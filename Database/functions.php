<?php
require_once 'config.php';

function getAllStokBarang() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM stok_barang ORDER BY id DESC");
    return $stmt->fetchAll();
}

function tambahBarang($data) {
    $pdo = getDBConnection();
    $sql = "INSERT INTO stok_barang (nama_barang, tipe, stok, harga_dasar, harga_jual) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nama_barang'],
        $data['tipe_barang'],
        $data['stok'],
        $data['harga_dasar'],
        $data['harga_jual']
    ]);
}

function updateStokBarang($id, $stok) {
    $pdo = getDBConnection();
    $sql = "UPDATE stok_barang SET stok = stok + ?, updated_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$stok, $id]);
}

function catatTransaksi($data) {
    $pdo = getDBConnection();
    
    $sql = "INSERT INTO riwayat_transaksi (nama_barang, jenis_transaksi, pemasok, jumlah, harga, total, keterangan) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nama_barang'],
        $data['jenis_transaksi'],
        $data['pemasok'],
        $data['jumlah'],
        $data['harga'],
        $data['total'],
        $data['keterangan']
    ]);
}
?>