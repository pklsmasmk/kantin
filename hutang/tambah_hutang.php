<?php
require_once '../Database/config.php';

function tambahHutang($data) {
    $pdo = getDBConnection();
    $sql = "INSERT INTO hutang (pemasok, nama_barang, jumlah, harga_dasar, total_hutang, sisa_hutang) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['pemasok'],
        $data['nama_barang'],
        $data['jumlah'],
        $data['harga_dasar'],
        $data['total_hutang'],
        $data['total_hutang']
    ]);
}

function getDaftarHutang() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM hutang ORDER BY created_at DESC");
    return $stmt->fetchAll();
}
?>