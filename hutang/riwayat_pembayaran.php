<?php
require_once '../Database/config.php';
require_once '../Database/functions.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function untuk ambil semua riwayat pembayaran hutang
function getAllRiwayatPembayaran($bulan = null) {
    $pdo = getDBConnection();
    
    if ($bulan) {
        $stmt = $pdo->prepare("
            SELECT rph.*, h.nama_barang, h.pemasok, h.total_hutang
            FROM riwayat_pembayaran_hutang rph
            JOIN hutang h ON rph.hutang_id = h.id
            WHERE DATE_FORMAT(rph.created_at, '%Y-%m') = ?
            ORDER BY rph.created_at DESC
        ");
        $stmt->execute([$bulan]);
    } else {
        $stmt = $pdo->query("
            SELECT rph.*, h.nama_barang, h.pemasok, h.total_hutang
            FROM riwayat_pembayaran_hutang rph
            JOIN hutang h ON rph.hutang_id = h.id
            ORDER BY rph.created_at DESC
        ");
    }
    
    return $stmt->fetchAll();
}

// Function untuk ambil daftar bulan yang ada data pembayaran
function getBulanTersediaPembayaran() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') as bulan 
        FROM riwayat_pembayaran_hutang 
        ORDER BY bulan DESC
    ");
    return $stmt->fetchAll();
}

// Function untuk stats pembayaran
function getStatsPembayaran($bulan = null) {
    $pdo = getDBConnection();
    
    if ($bulan) {
        $stmt = $pdo->prepare("
            SELECT 
                SUM(jumlah_bayar) as total_bayar,
                COUNT(*) as total_transaksi,
                COUNT(DISTINCT hutang_id) as total_hutang_dibayar
            FROM riwayat_pembayaran_hutang 
            WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
        ");
        $stmt->execute([$bulan]);
    } else {
        $stmt = $pdo->query("
            SELECT 
                SUM(jumlah_bayar) as total_bayar,
                COUNT(*) as total_transaksi,
                COUNT(DISTINCT hutang_id) as total_hutang_dibayar
            FROM riwayat_pembayaran_hutang
        ");
    }
    
    return $stmt->fetch();
}

// Ambil filter bulan dari URL
$bulan_filter = $_GET['bulan'] ?? date('Y-m');
$riwayatPembayaran = getAllRiwayatPembayaran($bulan_filter);
$stats = getStatsPembayaran($bulan_filter);
$bulanTersedia = getBulanTersediaPembayaran();

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
    <title>Riwayat Pembayaran Hutang - Kantin</title>
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
        .btn-info { 
            background-color: #17a2b8; 
            border-color: #17a2b8; 
        }
        .bg-success { background-color: #28a745 !important; }
        .bg-info { background-color: #17a2b8 !important; }
        .bg-primary { background-color: #007bff !important; }
        .user-info {
            background-color: #e8f5e8;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.9em;
            color: #155724;
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
                <a class="nav-link" href="/?q=hutang">
                    <i class="fas fa-money-bill-wave me-1"></i> Daftar Hutang
                </a>
                <a class="nav-link active" href="#">
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
        <!-- HEADER HALAMAN -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 text-success">
                <i class="fas fa-receipt me-2"></i>Riwayat Pembayaran Hutang
            </h1>
            <a href="/?q=hutang" class="btn btn-outline-success">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Hutang
            </a>
        </div>

        <!-- FILTER BULAN -->
        <div class="filter-section">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="text-success mb-3">
                        <i class="fas fa-calendar me-2"></i>Riwayat Pembayaran 
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
                        <input type="hidden" name="q" value="riwayat_pembayaran">
                    </form>
                </div>
            </div>
        </div>

        <!-- STATS CARD -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Total Pembayaran</h6>
                                <h3>Rp <?= number_format($stats['total_bayar'] ?? 0, 0, ',', '.') ?></h3>
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
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Hutang yang Dibayar</h6>
                                <h3><?= $stats['total_hutang_dibayar'] ?? 0 ?></h3>
                                <small>Jenis Hutang</small>
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
                                <h6 class="card-title">Rata-rata Bayar</h6>
                                <h3>Rp <?= number_format(($stats['total_bayar'] ?? 0) / max(($stats['total_transaksi'] ?? 1), 1), 0, ',', '.') ?></h3>
                                <small>Per Transaksi</small>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-calculator fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABEL RIWAYAT PEMBAYARAN -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 text-success">
                    <i class="fas fa-list me-2"></i>
                    <?= $bulan_filter ? 'Riwayat Pembayaran ' . $nama_bulan_selected : 'Semua Riwayat Pembayaran' ?>
                </h5>
                <div class="badge bg-success">
                    <i class="fas fa-receipt me-1"></i> 
                    Total: <?= count($riwayatPembayaran) ?> Pembayaran
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($riwayatPembayaran)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-receipt fa-4x mb-3"></i>
                        <h5>Belum ada riwayat pembayaran</h5>
                        <p class="mb-4">
                            <?= $bulan_filter ? 
                                'Tidak ada pembayaran untuk ' . $nama_bulan_selected : 
                                'Riwayat pembayaran akan muncul di sini setelah Anda melakukan pembayaran hutang'
                            ?>
                        </p>
                        <a href="/?q=hutang" class="btn btn-success">
                            <i class="fas fa-money-bill-wave me-1"></i> Bayar Hutang
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Tanggal Bayar</th>
                                    <th>Pembayar</th>
                                    <th>Barang</th>
                                    <th>Pemasok</th>
                                    <th>Jumlah Bayar</th>
                                    <th>Sisa Sebelum</th>
                                    <th>Sisa Sesudah</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($riwayatPembayaran as $index => $pembayaran): 
                                    $status = $pembayaran['sisa_hutang_sesudah'] <= 0 ? 'LUNAS' : 'BELUM LUNAS';
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <small>
                                            <?= date('d/m/Y', strtotime($pembayaran['created_at'])) ?><br>
                                            <span class="text-muted"><?= date('H:i', strtotime($pembayaran['created_at'])) ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($pembayaran['pembayar']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($pembayaran['nama_barang']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= htmlspecialchars($pembayaran['pemasok']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-success">Rp <?= number_format($pembayaran['jumlah_bayar'], 0, ',', '.') ?></strong>
                                    </td>
                                    <td>Rp <?= number_format($pembayaran['sisa_hutang_sebelum'], 0, ',', '.') ?></td>
                                    <td>
                                        <?php if ($status == 'LUNAS'): ?>
                                            <span class="badge bg-success">LUNAS</span>
                                        <?php else: ?>
                                            Rp <?= number_format($pembayaran['sisa_hutang_sesudah'], 0, ',', '.') ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $status == 'LUNAS' ? 'success' : 'warning' ?>">
                                            <?= $status ?>
                                        </span>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto close alerts setelah 5 detik
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