<?php
require_once '../Database/config.php';
require_once '../Database/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi input
    if (empty($_POST['id']) || empty($_POST['jumlah'])) {
        echo 'error';
        exit;
    }
    
    $id = $_POST['id'];
    $jumlah = $_POST['jumlah'];
    
    // Cek koneksi database sebelum memproses
    $pdo = getDBConnection();
    if (!$pdo) {
        echo 'error';
        exit;
    }
    
    try {
        if (updateStokBarang($id, $jumlah)) {
            $stmt = $pdo->prepare("SELECT nama_barang, harga_dasar FROM stok_barang WHERE id = ?");
            $stmt->execute([$id]);
            $barang = $stmt->fetch();
            
            if ($barang) {
                $transaksiData = [
                    'nama_barang' => $barang['nama_barang'], // Tambahkan nama_barang
                    'jenis_transaksi' => 'restock',
                    'pemasok' => $_SESSION['username'] ?? 'system', // Tambahkan pemasok
                    'penjual' => '', // Tambahkan penjual (kosongkan jika tidak ada)
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
        } else {
            echo 'error';
        }
    } catch (Exception $e) {
        echo 'error';
    }
} else {
    echo 'error';
}
?>