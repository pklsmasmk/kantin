<?php
require_once '../Database/config.php';

function getRiwayatTransaksi() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT rt.*, sb.nama_barang, sb.nama 
                         FROM riwayat_transaksi rt 
                         LEFT JOIN stok_barang sb ON rt.barang_id = sb.id 
                         ORDER BY rt.created_at DESC");
    return $stmt->fetchAll();
}

$riwayatTransaksi = getRiwayatTransaksi();
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
    </style>
</head>
<body>
    <!-- NAVIGASI -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
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
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- HEADER HALAMAN -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Riwayat Transaksi</h1>
            <a href="/?q=stok_barang" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Stok Barang
            </a>
        </div>

        <!-- TABEL RIWAYAT TRANSAKSI -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Transaksi</h5>
                <div class="badge bg-primary">
                    Total: <?= count($riwayatTransaksi) ?> Transaksi
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($riwayatTransaksi)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-receipt fa-3x mb-3"></i>
                        <p>Tidak ada data transaksi</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Tanggal</th>
                                    <th>Barang</th>
                                    <th>Jenis Transaksi</th>
                                    <th>Jumlah</th>
                                    <th>Harga</th>
                                    <th>Total</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($riwayatTransaksi as $index => $transaksi): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <small>
                                            <?= date('d/m/Y', strtotime($transaksi['created_at'])) ?><br>
                                            <span class="text-muted"><?= date('H:i', strtotime($transaksi['created_at'])) ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($transaksi['nama_barang'] ?? $transaksi['nema'] ?? '-') ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $transaksi['jenis_transaksi'] == 'tambah_barang' ? 'success' : 'info' ?>">
                                            <?= $transaksi['jenis_transaksi'] == 'tambah_barang' ? 'Tambah Barang' : 'Restock' ?>
                                        </span>
                                    </td>
                                    <td><?= $transaksi['jumlah'] ?></td>
                                    <td>Rp <?= number_format($transaksi['harga'], 0, ',', '.') ?></td>
                                    <td>
                                        <strong>Rp <?= number_format($transaksi['total'], 0, ',', '.') ?></strong>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($transaksi['keterangan']) ?></small>
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