<?php
require_once '../Database/config.php';
require_once '../Database/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $jumlah = $_POST['jumlah'];
    
    if (updateStokBarang($id, $jumlah)) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT nama_barang, harga_dasar FROM stok_barang WHERE id = ?"); // Perbaiki nama kolom
        $stmt->execute([$id]);
        $barang = $stmt->fetch();
        
        $transaksiData = [
            'barang_id' => $id,
            'jenis_transaksi' => 'restock',
            'jumlah' => $jumlah,
            'harga' => $barang['harga_dasar'],
            'total' => $jumlah * $barang['harga_dasar'],
            'keterangan' => 'Restock barang: ' . $barang['nama_barang'] 
        ];
        
        catatTransaksi($transaksiData);
        
        echo 'success';
    } else {
        echo 'error';
    }
}
?>