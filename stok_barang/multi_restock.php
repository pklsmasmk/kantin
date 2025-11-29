<?php
require_once '../Database/config.php';
require_once '../Database/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$success = '';
$error = '';

// Cek koneksi database di awal
$pdo = getDBConnection();
if (!$pdo) {
    $error = "Koneksi database gagal. Periksa konfigurasi database.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'multi_restock') {
    // Pastikan koneksi database tersedia
    if (!$pdo) {
        $error = "Tidak dapat terhubung ke database. Multi restock dibatalkan.";
    } else {
        $restock_items = $_POST['restock_items'] ?? [];
        $all_success = true;
        $errors_detail = [];

        // Validasi input
        if (empty($restock_items)) {
            $error = "Tidak ada item yang dipilih untuk restock!";
        } else {
            foreach ($restock_items as $index => $item) {
                // Validasi data required
                if (empty($item['id']) || empty($item['jumlah']) || empty($item['harga_dasar'])) {
                    $errors_detail[] = "Item #" . ($index + 1) . ": Data tidak lengkap";
                    $all_success = false;
                    continue;
                }

                $id = intval($item['id']);
                $jumlah = intval($item['jumlah']);
                $harga_dasar = floatval($item['harga_dasar']);
                $metode_bayar = $item['metode_bayar'] ?? 'cash';
                $penjual_multi = $item['penjual'] ?? '';

                // Validasi nilai
                if ($jumlah <= 0 || $harga_dasar <= 0) {
                    $errors_detail[] = "Item #" . ($index + 1) . ": Jumlah atau harga tidak valid";
                    $all_success = false;
                    continue;
                }

                try {
                    // Update stok barang
                    if (updateStokBarang($id, $jumlah)) {
                        // Ambil info barang
                        $stmt = $pdo->prepare("SELECT nama_barang FROM stok_barang WHERE id = ?");
                        $stmt->execute([$id]);
                        $barang = $stmt->fetch();

                        if (!$barang) {
                            $errors_detail[] = "Item #" . ($index + 1) . ": Barang tidak ditemukan";
                            $all_success = false;
                            continue;
                        }

                        // Siapkan data transaksi
                        $pemasok = $_SESSION['username'] ?? 'karyawan';
                        
                        // Validasi user exists
                        $stmt = $pdo->prepare("SELECT username FROM users WHERE username = ?");
                        $stmt->execute([$pemasok]);
                        $userExists = $stmt->fetch();
                        if (!$userExists) {
                            $pemasok = 'karyawan';
                        }

                        $transaksiData = [
                            'nama_barang' => $barang['nama_barang'],
                            'jenis_transaksi' => 'restock',
                            'pemasok' => $pemasok,
                            'penjual' => $penjual_multi,
                            'jumlah' => $jumlah,
                            'harga' => $harga_dasar,
                            'total' => $jumlah * $harga_dasar,
                            'keterangan' => 'Multi Restock: ' . $barang['nama_barang'] . ' - Harga: Rp ' . number_format($harga_dasar, 0, ',', '.')
                        ];

                        // Handle metode bayar hutang
                        if ($metode_bayar == 'hutang') {
                            // Pastikan file hutang exists
                            $hutang_file = '../hutang/tambah_hutang.php';
                            if (file_exists($hutang_file)) {
                                require_once $hutang_file;
                                
                                if (function_exists('tambahHutang')) {
                                    $dataHutang = [
                                        'pemasok' => $transaksiData['pemasok'],
                                        'nama_barang' => $barang['nama_barang'],
                                        'jumlah' => $jumlah,
                                        'harga_dasar' => $harga_dasar,
                                        'total_hutang' => $jumlah * $harga_dasar
                                    ];
                                    
                                    if (tambahHutang($dataHutang)) {
                                        $transaksiData['keterangan'] = 'Multi Restock (HUTANG): ' . $barang['nama_barang'] . ' - Harga: Rp ' . number_format($harga_dasar, 0, ',', '.');
                                    } else {
                                        $errors_detail[] = "Item #" . ($index + 1) . ": Gagal mencatat hutang";
                                    }
                                } else {
                                    $errors_detail[] = "Item #" . ($index + 1) . ": Fungsi tambahHutang tidak tersedia";
                                }
                            } else {
                                $errors_detail[] = "Item #" . ($index + 1) . ": Sistem hutang tidak tersedia";
                            }
                        }

                        // Catat transaksi
                        if (!catatTransaksi($transaksiData)) {
                            $errors_detail[] = "Item #" . ($index + 1) . ": Gagal mencatat transaksi";
                            $all_success = false;
                        }

                    } else {
                        $errors_detail[] = "Item #" . ($index + 1) . ": Gagal update stok";
                        $all_success = false;
                    }

                } catch (PDOException $e) {
                    $errors_detail[] = "Item #" . ($index + 1) . ": Error database - " . $e->getMessage();
                    $all_success = false;
                    error_log("Multi Restock Error: " . $e->getMessage());
                }
            }

            // Set pesan hasil
            if ($all_success) {
                $success = "Multi Restock berhasil dilakukan! " . count($restock_items) . " item diproses.";
            } else {
                $error = "Beberapa item gagal di-restock!";
                if (!empty($errors_detail)) {
                    $error .= "<br>Detail: " . implode(", ", $errors_detail);
                }
            }
        }
    }
}

// Ambil data stok barang dengan error handling
try {
    $stokBarang = getAllStokBarang();
    if ($stokBarang === false) {
        $stokBarang = [];
        if (empty($error)) {
            $error = "Gagal memuat data stok barang";
        }
    }
} catch (Exception $e) {
    $stokBarang = [];
    $error = "Error memuat data stok: " . $e->getMessage();
}
?>