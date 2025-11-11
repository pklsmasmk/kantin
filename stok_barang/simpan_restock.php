<?php
require_once '../Databse/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $jumlah = $_POST['jumlah'];
    
    if (updateStokBarang($id, $jumlah)) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT nema, harpa_dasar FROM stok_barang WHERE id = ?");
        $stmt->execute([$id]);
        $barang = $stmt->fetch();
        
        $transaksiData = [
            'barang_id' => $id,
            'jenis_transaksi' => 'restock',
            'jumlah' => $jumlah,
            'harga' => $barang['harpa_dasar'],
            'total' => $jumlah * $barang['harpa_dasar'],
            'keterangan' => 'Restock barang: ' . $barang['nema']
        ];
        
        catatTransaksi($transaksiData);
        
        echo 'success';
    } else {
        echo 'error';
    }
}
?>