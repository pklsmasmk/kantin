<?php
require_once '../Database/config.php';
require_once '../Database/functions.php';

// Set header JSON untuk response
header('Content-Type: application/json');

// Cek koneksi database di awal
$pdo = getDBConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validasi input
        if (empty($_POST['id']) || empty($_POST['jumlah'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Data ID dan Jumlah harus diisi']);
            exit;
        }

        $id = intval($_POST['id']);
        $jumlah = intval($_POST['jumlah']);

        // Validasi nilai
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID barang tidak valid']);
            exit;
        }

        if ($jumlah <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Jumlah harus lebih dari 0']);
            exit;
        }

        // Update stok barang
        if (!updateStokBarang($id, $jumlah)) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal update stok barang']);
            exit;
        }

        // Ambil data barang setelah update
        $stmt = $pdo->prepare("SELECT nama_barang, harga_dasar FROM stok_barang WHERE id = ?");
        $stmt->execute([$id]);
        $barang = $stmt->fetch();

        if (!$barang) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Data barang tidak ditemukan']);
            exit;
        }

        // Siapkan data transaksi
        $transaksiData = [
            'nama_barang' => $barang['nama_barang'],
            'jenis_transaksi' => 'restock',
            'pemasok' => $_SESSION['username'] ?? 'system',
            'penjual' => '',
            'jumlah' => $jumlah,
            'harga' => $barang['harga_dasar'],
            'total' => $jumlah * $barang['harga_dasar'],
            'keterangan' => 'Restock barang: ' . $barang['nama_barang'] 
        ];

        // Catat transaksi
        if (!catatTransaksi($transaksiData)) {
            // Log error tetapi tetap return success karena stok sudah terupdate
            error_log("Gagal mencatat transaksi untuk restock barang ID: $id");
        }

        // Response sukses
        echo json_encode([
            'status' => 'success', 
            'message' => 'Restock berhasil dilakukan',
            'data' => [
                'nama_barang' => $barang['nama_barang'],
                'jumlah' => $jumlah,
                'harga_dasar' => $barang['harga_dasar'],
                'total' => $jumlah * $barang['harga_dasar']
            ]
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        error_log("PDO Error in restock: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan database: ' . $e->getMessage()]);
    } catch (Exception $e) {
        http_response_code(500);
        error_log("General Error in restock: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
}
?>