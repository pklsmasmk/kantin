<?php
require_once '../Database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nama_barang' => $_POST['nama_barang'],
        'tipe_barang' => $_POST['tipe_barang'],
        'pemasok' => $_POST['pemasok'],
        'stok' => $_POST['stok'],
        'harga_dasar' => $_POST['harga_dasar'],
        'harga_jual' => $_POST['harga_jual']
    ];

    if (tambahBarang($data)) {
        // Catat transaksi
        $pdo = getDBConnection();
        $lastId = $pdo->lastInsertId();
        
        $transaksiData = [
            'barang_id' => $lastId,
            'jenis_transaksi' => 'tambah_barang',
            'jumlah' => $data['stok'],
            'harga' => $data['harga_dasar'],
            'total' => $data['stok'] * $data['harga_dasar'],
            'keterangan' => 'Penambahan barang baru: ' . $data['nama_barang']
        ];
        
        catatTransaksi($transaksiData);
        
        echo 'success';
    } else {
        echo 'error';
    }
}
?>