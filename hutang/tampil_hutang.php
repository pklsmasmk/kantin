<?php
require_once '../Database/config.php';
require_once '../Database/functions.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function untuk ambil data hutang
function getDaftarHutang() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM hutang ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

// Function untuk bayar hutang
function bayarHutang($id, $jumlahBayar) {
    $pdo = getDBConnection();
    
    // Ambil data hutang saat ini
    $stmt = $pdo->prepare("SELECT * FROM hutang WHERE id = ?");
    $stmt->execute([$id]);
    $hutang = $stmt->fetch();
    
    if ($hutang) {
        $sisaHutangBaru = $hutang['sisa_hutang'] - $jumlahBayar;
        $status = $sisaHutangBaru <= 0 ? 'lunas' : 'belum_lunas';
        
        // Update hutang
        $sql = "UPDATE hutang SET sisa_hutang = ?, status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$sisaHutangBaru, $status, $id]);
    }
    
    return false;
}

// Proses bayar hutang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'bayar_hutang') {
    $id = $_POST['id'];
    $jumlah_bayar = $_POST['jumlah_bayar'];
    
    if (bayarHutang($id, $jumlah_bayar)) {
        $success = "Pembayaran hutang berhasil!";
    } else {
        $error = "Gagal melakukan pembayaran hutang!";
    }
}

$daftarHutang = getDaftarHutang();
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
        .bg-success { background-color: #28a745 !important; }
        .bg-warning { background-color: #ffc107 !important; }
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
            <a href="/?q=stok_barang" class="btn btn-outline-success">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Stok Barang
            </a>
        </div>

        <!-- STATS CARD -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">Total Hutang</h4>
                                <h2>
                                    Rp <?= number_format(array_sum(array_column($daftarHutang, 'total_hutang')), 0, ',', '.') ?>
                                </h2>
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
                                <h4 class="card-title">Sisa Hutang</h4>
                                <h2>
                                    Rp <?= number_format(array_sum(array_column($daftarHutang, 'sisa_hutang')), 0, ',', '.') ?>
                                </h2>
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
                                <h4 class="card-title">Hutang Lunas</h4>
                                <h2>
                                    <?= count(array_filter($daftarHutang, function($h) { return $h['status'] == 'lunas'; })) ?>
                                </h2>
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
                    <i class="fas fa-list me-2"></i>Daftar Semua Hutang
                </h5>
                <div class="badge bg-success">
                    <i class="fas fa-receipt me-1"></i> Total: <?= count($daftarHutang) ?> Hutang
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($daftarHutang)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-money-bill-wave fa-4x mb-3"></i>
                        <h5>Belum ada data hutang</h5>
                        <p class="mb-4">Hutang akan muncul di sini ketika Anda menambah barang dengan metode bayar "Hutang"</p>
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
                                        <?php else: ?>
                                        <span class="badge bg-success">LUNAS</span>
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