<?php
require_once '../Database/config.php';
require_once '../Database/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'multi_restock') {
    $restock_items = $_POST['restock_items'];
    $all_success = true;

    foreach ($restock_items as $item) {
        if (!empty($item['id']) && !empty($item['jumlah']) && !empty($item['harga_dasar'])) {
            $id = $item['id'];
            $jumlah = $item['jumlah'];
            $harga_dasar = $item['harga_dasar'];
            $metode_bayar = $item['metode_bayar'];

            if (updateStokBarang($id, $jumlah)) {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("SELECT nama_barang FROM stok_barang WHERE id = ?");
                $stmt->execute([$id]);
                $barang = $stmt->fetch();

                $transaksiData = [
                    'nama_barang' => $barang['nama_barang'],
                    'jenis_transaksi' => 'restock',
                    'pemasok' => $_SESSION['username'] ?? 'karyawan',  // USER LOGIN
                    'penjual' => $penjual_multi,
                    'jumlah' => $jumlah,
                    'harga' => $harga_dasar,
                    'total' => $jumlah * $harga_dasar,
                    'keterangan' => 'Multi Restock: ' . $barang['nama_barang'] . ' - Harga: Rp ' . number_format($harga_dasar, 0, ',', '.')
                ];

                $stmt = $pdo->prepare("SELECT username FROM users WHERE username = ?");
                $stmt->execute([$transaksiData['pemasok']]);
                $userExists = $stmt->fetch();
                if (!$userExists) $transaksiData['pemasok'] = 'karyawan';

                if ($metode_bayar == 'hutang') {
                    require_once '../hutang/tambah_hutang.php';
                    $dataHutang = [
                        'pemasok' => $transaksiData['pemasok'],
                        'nama_barang' => $barang['nama_barang'],
                        'jumlah' => $jumlah,
                        'harga_dasar' => $harga_dasar,
                        'total_hutang' => $jumlah * $harga_dasar
                    ];
                    tambahHutang($dataHutang);
                    $transaksiData['keterangan'] = 'Multi Restock (HUTANG): ' . $barang['nama_barang'] . ' - Harga: Rp ' . number_format($harga_dasar, 0, ',', '.');
                }

                catatTransaksi($transaksiData);
            } else {
                $all_success = false;
            }
        }
    }

    if ($all_success) {
        $success = "Multi Restock berhasil dilakukan!";
    } else {
        $error = "Beberapa item gagal di-restock!";
    }
}

$stokBarang = getAllStokBarang();
