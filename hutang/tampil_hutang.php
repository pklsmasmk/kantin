<?php
require_once '../Database/config.php';
require_once '../Database/functions.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Proses bayar hutang - MODIFIED (gunakan function dari functions.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'bayar_hutang') {
    $id = $_POST['id'];
    $jumlah_bayar = $_POST['jumlah_bayar'];
    $pembayar = $_SESSION['username'] ?? 'karyawan'; // User yang login
    
    if (bayarHutang($id, $jumlah_bayar, $pembayar)) {
        $success = "Pembayaran hutang berhasil!";
    } else {
        $error = "Gagal melakukan pembayaran hutang!";
    }
}

// Function untuk ambil data hutang
function getDaftarHutang($bulan = null) {
    $pdo = getDBConnection();
    
    if ($bulan) {
        $stmt = $pdo->prepare("
            SELECT * FROM hutang 
            WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$bulan]);
    } else {
        $stmt = $pdo->query("SELECT * FROM hutang ORDER BY created_at DESC");
    }
    
    return $stmt->fetchAll();
}

// Function untuk stats hutang
function getStatsHutang($bulan = null) {
    $pdo = getDBConnection();
    
    if ($bulan) {
        $stmt = $pdo->prepare("
            SELECT 
                SUM(total_hutang) as total_hutang,
                SUM(sisa_hutang) as sisa_hutang,
                COUNT(*) as total_transaksi,
                SUM(CASE WHEN status = 'lunas' THEN 1 ELSE 0 END) as lunas_count,
                SUM(CASE WHEN status = 'belum_lunas' THEN 1 ELSE 0 END) as belum_lunas_count
            FROM hutang 
            WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
        ");
        $stmt->execute([$bulan]);
    } else {
        $stmt = $pdo->query("
            SELECT 
                SUM(total_hutang) as total_hutang,
                SUM(sisa_hutang) as sisa_hutang,
                COUNT(*) as total_transaksi,
                SUM(CASE WHEN status = 'lunas' THEN 1 ELSE 0 END) as lunas_count,
                SUM(CASE WHEN status = 'belum_lunas' THEN 1 ELSE 0 END) as belum_lunas_count
            FROM hutang
        ");
    }
    
    return $stmt->fetch();
}

// Function untuk ambil daftar bulan yang ada data hutang
function getBulanTersedia() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') as bulan 
        FROM hutang 
        ORDER BY bulan DESC
    ");
    return $stmt->fetchAll();
}

// Ambil filter bulan dari URL
$bulan_filter = $_GET['bulan'] ?? date('Y-m');
$daftarHutang = getDaftarHutang($bulan_filter);
$stats = getStatsHutang($bulan_filter);
$bulanTersedia = getBulanTersedia();

// Format nama bulan
$nama_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// Handle bulan filter dengan safe check
if ($bulan_filter && strpos($bulan_filter, '-') !== false) {
    $bulan_selected = explode('-', $bulan_filter);
    if (isset($bulan_selected[1]) && isset($nama_bulan[$bulan_selected[1]])) {
        $nama_bulan_selected = $nama_bulan[$bulan_selected[1]] . ' ' . $bulan_selected[0];
    } else {
        $nama_bulan_selected = 'Bulan Tidak Valid';
    }
} else {
    $nama_bulan_selected = 'Semua Bulan';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Hutang - Kantin</title>
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
        .btn-warning { 
            background-color: #ffc107; 
            border-color: #ffc107; 
        }
        .btn-info { 
            background-color: #17a2b8; 
            border-color: #17a2b8; 
        }
        .bg-success { background-color: #28a745 !important; }
        .bg-warning { background-color: #ffc107 !important; }
        .bg-danger { background-color: #dc3545 !important; }
        .user-info {
            background-color: #e8f5e8;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.9em;
            color: #155724;
        }
        .hutang-card {
            border-left: 4px solid #dc3545;
        }
        .lunas-card {
            border-left: 4px solid #28a745;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
                <a class="nav-link" href="/?q=riwayat_transaksi">
                    <i class="fas fa-history me-1"></i> Riwayat Transaksi
                </a>
                <a class="nav-link active" href="#">
                    <i class="fas fa-money-bill-wave me-1"></i> Daftar Hutang
                </a>
                <a class="nav-link" href="/?q=riwayat_pembayaran">
                    <i class="fas fa-receipt me-1"></i> Riwayat Pembayaran
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
        <!-- NOTIFIKASI -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- HEADER HALAMAN -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 text-success">
                <i class="fas fa-money-bill-wave me-2"></i>Daftar Hutang
            </h1>
            <div>
                <a href="/?q=riwayat_pembayaran" class="btn btn-info me-2">
                    <i class="fas fa-history me-1"></i> Riwayat Pembayaran
                </a>
                <a href="/?q=stok_barang" class="btn btn-outline-success">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Stok Barang
                </a>
            </div>
        </div>

        <!-- FILTER BULAN -->
        <div class="filter-section">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="text-success mb-3">
                        <i class="fas fa-calendar me-2"></i>Laporan Hutang 
                        <?= 
                            $bulan_filter == date('Y-m') ? 'Bulan Ini' : 
                            ($bulan_filter ? $nama_bulan_selected : 'Semua Bulan')
                        ?>
                    </h5>
                </div>
                <div class="col-md-6">
                    <form method="GET" class="d-flex">
                        <select name="bulan" class="form-select me-2" onchange="this.form.submit()">
                            <option value="">Semua Bulan</option>
                            <?php foreach ($bulanTersedia as $bulan): ?>
                                <?php 
                                $bulan_parts = explode('-', $bulan['bulan']);
                                $selected = $bulan['bulan'] == $bulan_filter ? 'selected' : '';
                                ?>
                                <option value="<?= $bulan['bulan'] ?>" <?= $selected ?>>
                                    <?= $nama_bulan[$bulan_parts[1]] ?> <?= $bulan_parts[0] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($_GET['q'])): ?>
                            <input type="hidden" name="q" value="hutang">
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- STATS CARD -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Total Hutang</h6>
                                <h4>Rp <?= number_format($stats['total_hutang'] ?? 0, 0, ',', '.') ?></h4>
                                <small><?= $stats['total_transaksi'] ?? 0 ?> Transaksi</small>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-money-bill-wave fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Sisa Hutang</h6>
                                <h4>Rp <?= number_format($stats['sisa_hutang'] ?? 0, 0, ',', '.') ?></h4>
                                <small><?= $stats['belum_lunas_count'] ?? 0 ?> Belum Lunas</small>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-credit-card fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Hutang Lunas</h6>
                                <h4>Rp <?= number_format(($stats['total_hutang'] ?? 0) - ($stats['sisa_hutang'] ?? 0), 0, ',', '.') ?></h4>
                                <small><?= $stats['lunas_count'] ?? 0 ?> Lunas</small>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABEL HUTANG -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 text-success">
                    <i class="fas fa-list me-2"></i>
                    <?= $bulan_filter ? 'Daftar Hutang ' . $nama_bulan_selected : 'Daftar Semua Hutang' ?>
                </h5>
                <div class="badge bg-success">
                    <i class="fas fa-receipt me-1"></i> 
                    Total: <?= count($daftarHutang) ?> Hutang
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($daftarHutang)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-money-bill-wave fa-4x mb-3"></i>
                        <h5>Belum ada data hutang</h5>
                        <p class="mb-4">
                            <?= $bulan_filter ? 
                                'Tidak ada data hutang untuk ' . $nama_bulan_selected : 
                                'Hutang akan muncul di sini ketika Anda menambah barang dengan metode bayar "Hutang"'
                            ?>
                        </p>
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
                                    <th>Pemasok</th>
                                    <th>Barang</th>
                                    <th>Jumlah</th>
                                    <th>Harga Dasar</th>
                                    <th>Total Hutang</th>
                                    <th>Sisa Hutang</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($daftarHutang as $index => $hutang): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <small>
                                            <?= date('d/m/Y', strtotime($hutang['created_at'])) ?><br>
                                            <span class="text-muted"><?= date('H:i', strtotime($hutang['created_at'])) ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($hutang['pemasok']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($hutang['nama_barang']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?= $hutang['jumlah'] ?> pcs
                                        </span>
                                    </td>
                                    <td>Rp <?= number_format($hutang['harga_dasar'], 0, ',', '.') ?></td>
                                    <td>
                                        <strong class="text-danger">Rp <?= number_format($hutang['total_hutang'], 0, ',', '.') ?></strong>
                                    </td>
                                    <td>
                                        <strong class="text-warning">Rp <?= number_format($hutang['sisa_hutang'], 0, ',', '.') ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $hutang['status'] == 'lunas' ? 'success' : 'warning' ?>">
                                            <?= $hutang['status'] == 'lunas' ? 'LUNAS' : 'BELUM LUNAS' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($hutang['status'] == 'belum_lunas'): ?>
                                        <!-- TOMBOL BAYAR HUTANG -->
                                        <button class="btn btn-sm btn-success" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#bayarHutangModal<?= $hutang['id'] ?>"
                                                title="Bayar Hutang">
                                            <i class="fas fa-money-bill me-1"></i> Bayar
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- MODAL BAYAR HUTANG -->
                                <div class="modal fade" id="bayarHutangModal<?= $hutang['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-money-bill me-2"></i>Bayar Hutang
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="bayar_hutang">
                                                    <input type="hidden" name="id" value="<?= $hutang['id'] ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Pemasok</label>
                                                        <input type="text" class="form-control" value="<?= htmlspecialchars($hutang['pemasok']) ?>" readonly>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Barang</label>
                                                        <input type="text" class="form-control" value="<?= htmlspecialchars($hutang['nama_barang']) ?>" readonly>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Sisa Hutang</label>
                                                        <input type="text" class="form-control bg-warning text-white" 
                                                               value="Rp <?= number_format($hutang['sisa_hutang'], 0, ',', '.') ?>" readonly>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="jumlah_bayar<?= $hutang['id'] ?>" class="form-label">Jumlah Bayar <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">Rp</span>
                                                            <input type="number" class="form-control" 
                                                                   id="jumlah_bayar<?= $hutang['id'] ?>" 
                                                                   name="jumlah_bayar" 
                                                                   min="1"
                                                                   max="<?= $hutang['sisa_hutang'] ?>"
                                                                   required
                                                                   placeholder="Masukkan jumlah bayar">
                                                        </div>
                                                        <div class="form-text">Maksimal: Rp <?= number_format($hutang['sisa_hutang'], 0, ',', '.') ?></div>
                                                    </div>

                                                    <div class="alert alert-info">
                                                        <small>
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            Pembayaran akan dicatat atas nama: <strong><?= htmlspecialchars($_SESSION['username'] ?? 'karyawan') ?></strong>
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                                        <i class="fas fa-times me-1"></i> Batal
                                                    </button>
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="fas fa-money-bill me-1"></i> Bayar Hutang
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>