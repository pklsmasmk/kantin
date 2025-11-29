<?php
require_once '../Database/config.php';
require_once '../Database/functions.php';

// Start session untuk mendapatkan user yang login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function getRiwayatTransaksi() {
    $pdo = getDBConnection();
    if (!$pdo) {
        error_log("Koneksi database gagal di getRiwayatTransaksi");
        return [];
    }
    
    try {
        $stmt = $pdo->query("
            SELECT * FROM riwayat_transaksi 
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getRiwayatTransaksi: " . $e->getMessage());
        return [];
    }
}

// Ambil data dengan error handling
$riwayatTransaksi = [];
$error_message = '';

try {
    $riwayatTransaksi = getRiwayatTransaksi();
} catch (Exception $e) {
    $error_message = "Terjadi kesalahan saat memuat data transaksi: " . $e->getMessage();
    error_log("Error loading riwayat transaksi: " . $e->getMessage());
}

// Cek jika tabel tidak ada atau kosong
$is_table_empty = empty($riwayatTransaksi);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - Kantin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .navbar-brand { font-weight: bold; }
        .card { 
            border-radius: 10px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            margin-bottom: 20px; 
        }
        .table th { 
            background-color: #f8f9fa; 
        }
        .btn-success { 
            background-color: #28a745; 
            border-color: #28a745; 
        }
        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .btn-outline-success {
            color: #28a745;
            border-color: #28a745;
        }
        .btn-outline-success:hover {
            background-color: #28a745;
            color: white;
        }
        .bg-success { background-color: #28a745 !important; }
        .text-success { color: #28a745 !important; }
        .user-info {
            background-color: #e8f5e8;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.9em;
            color: #155724;
        }
        
        /* Style untuk highlight hutang */
        .hutang-highlight {
            background-color: #ffe6e6 !important;
            border-left: 4px solid #dc3545 !important;
        }
        .badge-hutang {
            background-color: #dc3545;
            color: white;
        }
        .text-hutang {
            color: #dc3545;
            font-weight: bold;
        }
        
        /* Style untuk error */
        .alert-danger {
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <!-- NAVIGASI -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-store me-2"></i>Manajemen Kantin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/?q=stok_barang">
                    <i class="fas fa-boxes me-1"></i> Stok Barang
                </a>
                <a class="nav-link active" href="#">
                    <i class="fas fa-history me-1"></i> Riwayat Transaksi
                </a>
                <a class="nav-link" href="/?q=hutang">
                    <i class="fas fa-money-bill-wave me-1"></i> Daftar Hutang
                </a>
                <?php if (isset($_SESSION['username'])): ?>
                    <span class="nav-link user-info">
                        <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['username']) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- HEADER HALAMAN -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 text-success">
                <i class="fas fa-history me-2"></i>Riwayat Transaksi
            </h1>
            <a href="/?q=stok_barang" class="btn btn-outline-success">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Stok Barang
            </a>
        </div>

        <!-- PESAN ERROR -->
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Error!</strong> <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- TABEL RIWAYAT TRANSAKSI -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 text-success">
                    <i class="fas fa-list me-2"></i>Daftar Transaksi
                </h5>
                <?php if (!$is_table_empty): ?>
                    <div class="badge bg-success">
                        <i class="fas fa-receipt me-1"></i> Total: <?= count($riwayatTransaksi) ?> Transaksi
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($is_table_empty): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-receipt fa-4x mb-3"></i>
                        <h5>Belum ada data transaksi</h5>
                        <p class="mb-4">Transaksi akan muncul di sini setelah Anda menambah atau restock barang</p>
                        <a href="/?q=stok_barang" class="btn btn-success">
                            <i class="fas fa-plus-circle me-1"></i> Tambah Barang
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Tanggal</th>
                                    <th>Barang</th>
                                    <th>Jenis Transaksi</th>
                                    <th>Pemasok</th>
                                    <th>Penjual</th>
                                    <th>Jumlah</th>
                                    <th>Harga</th>
                                    <th>Total</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($riwayatTransaksi as $index => $transaksi): 
                                    // Validasi data untuk mencegah error
                                    $keterangan = $transaksi['keterangan'] ?? '';
                                    $nama_barang = $transaksi['nama_barang'] ?? '-';
                                    $pemasok = $transaksi['pemasok'] ?? '-';
                                    $penjual = $transaksi['penjual'] ?? '-';
                                    $jenis_transaksi = $transaksi['jenis_transaksi'] ?? '-';
                                    $jumlah = $transaksi['jumlah'] ?? 0;
                                    $harga = $transaksi['harga'] ?? 0;
                                    $total = $transaksi['total'] ?? 0;
                                    $created_at = $transaksi['created_at'] ?? date('Y-m-d H:i:s');
                                    
                                    // Cek apakah transaksi ini hutang (case insensitive)
                                    $keteranganLower = strtolower($keterangan);
                                    $isHutang = strpos($keteranganLower, 'hutang') !== false;
                                    $rowClass = $isHutang ? 'hutang-highlight' : '';
                                ?>
                                <tr class="<?= $rowClass ?>">
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <small>
                                            <?= date('d/m/Y', strtotime($created_at)) ?><br>
                                            <span class="text-muted"><?= date('H:i', strtotime($created_at)) ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($nama_barang) ?></strong>
                                        <?php if ($isHutang): ?>
                                            <br><span class="badge badge-hutang">HUTANG</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badge_color = 'bg-secondary';
                                        $jenis_text = $jenis_transaksi;
                                        
                                        if ($jenis_transaksi == 'tambah_barang') {
                                            $badge_color = 'bg-success';
                                            $jenis_text = 'Tambah Barang';
                                        } elseif ($jenis_transaksi == 'restock') {
                                            $badge_color = 'bg-info';
                                            $jenis_text = 'Restock';
                                        } elseif ($jenis_transaksi == 'penjualan') {
                                            $badge_color = 'bg-warning';
                                            $jenis_text = 'Penjualan';
                                        }
                                        ?>
                                        <span class="badge <?= $badge_color ?>">
                                            <?= htmlspecialchars($jenis_text) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($pemasok) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <i class="fas fa-user-tie me-1"></i><?= htmlspecialchars($penjual) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?= intval($jumlah) ?> pcs
                                        </span>
                                    </td>
                                    <td>Rp <?= number_format(floatval($harga), 0, ',', '.') ?></td>
                                    <td>
                                        <?php if ($isHutang): ?>
                                            <strong class="text-hutang">Rp <?= number_format(floatval($total), 0, ',', '.') ?></strong>
                                        <?php else: ?>
                                            <strong class="text-success">Rp <?= number_format(floatval($total), 0, ',', '.') ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($keterangan) ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>