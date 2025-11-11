<?php
require_once '../Database/config.php';

function getAllStokBarang() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM stok_barang ORDER BY id DESC");
    return $stmt->fetchAll();
}

function tambahBarang($data) {
    $pdo = getDBConnection();
    $sql = "INSERT INTO stok_barang (nama_barang, tipe, pemasok, stok, harga_dasar, harga_jual) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nama_barang'],
        $data['tipe_barang'],
        $data['pemasok'],
        $data['stok'],
        $data['harga_dasar'],
        $data['harga_jual']
    ]);
}

function updateStokBarang($id, $stok) {
    $pdo = getDBConnection();
    $sql = "UPDATE stok_barang SET stok = stok + ?, updated_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$stok, $id]);
}
function catatTransaksi($data) {
    $pdo = getDBConnection();
    $sql = "INSERT INTO riwayat_transaksi (barang_id, jenis_transaksi, jumlah, harga, total, keterangan) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['barang_id'],
        $data['jenis_transaksi'],
        $data['jumlah'],
        $data['harga'],
        $data['total'],
        $data['keterangan']
    ]);
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'tambah_barang':
                $data = [
                    'nama_barang' => $_POST['nama_barang'],
                    'tipe_barang' => $_POST['tipe_barang'],
                    'pemasok' => $_POST['pemasok'],
                    'stok' => $_POST['stok'],
                    'harga_dasar' => $_POST['harga_dasar'],
                    'harga_jual' => $_POST['harga_jual']
                ];

                if (tambahBarang($data)) {
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
                    $success = "Barang berhasil ditambahkan!";
                } else {
                    $error = "Gagal menambahkan barang!";
                }
                break;
                
            case 'restock':
                // Data dari form restock
                $id = $_POST['id'];
                $jumlah = $_POST['jumlah'];
                
                // Update stok di database
                if (updateStokBarang($id, $jumlah)) {
                    // Catat transaksi restock
                    $pdo = getDBConnection();
                    $stmt = $pdo->prepare("SELECT nama_barang, harga_dasar FROM stok_barang WHERE id = ?");
                    $stmt->execute([$id]);
                    $barang = $stmt->fetch();
                    
                    $transaksiData = [
                        'barang_id' => $id,
                        'jenis_transaksi' => 'restock',
                        'jumlah' => $jumlah,
                        'harga' => $barang['harga_dasar'],
                        'total' => $jumlah * $barang['harga_dasar'],
                        'keterangan' => 'Restock barang: ' . $barang['nama_barang']
                    ];
                    
                    catatTransaksi($transaksiData);
                    $success = "Restock berhasil dilakukan!";
                } else {
                    $error = "Gagal melakukan restock!";
                }
                break;
        }
    }
}

// Ambil data stok barang untuk ditampilkan
$stokBarang = getAllStokBarang();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Barang - Kantin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .navbar-brand { font-weight: bold; }
        .card { 
            border-radius: 10px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            margin-bottom: 20px; 
        }
        .card-header { 
            background-color: #f8f9fa; 
            border-bottom: 1px solid #e3e6f0; 
            font-weight: 600; 
        }
        .mode-indicator { 
            background-color: #e7f3ff; 
            border-left: 4px solid #0d6efd; 
            padding: 10px 15px; 
            margin-bottom: 15px; 
            border-radius: 4px; 
        }
        .btn-primary { 
            background-color: #0d6efd; 
            border-color: #0d6efd; 
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
                <a class="nav-link active" href="#">
                    <i class="fas fa-boxes me-1"></i> Stok Barang
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- NOTIFIKASI -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- HEADER HALAMAN -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Stok Barang</h1>
            <div>
                <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#tambahBarangModal">
                    <i class="fas fa-plus-circle me-1"></i> Restock/Tambah Barang
                </button>
                <a href="/?q=riwayat_transaksi" class="btn btn-outline-secondary">
                    <i class="fas fa-history me-1"></i> Riwayat Transaksi
                </a>
            </div>
        </div>

        <!-- MODAL TAMBAH BARANG -->
        <div class="modal fade" id="tambahBarangModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah / Restock Barang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <div class="mode-indicator">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>+ Mode Tambah:</strong> Anda sedang menambah barang baru
                            </div>
                            <input type="hidden" name="action" value="tambah_barang">
                            
                            <!-- FORM INPUT -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nama_barang" class="form-label">Nama Barang</label>
                                    <input type="text" class="form-control" id="nama_barang" name="nama_barang" 
                                           placeholder="Masukkan nama barang" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="pemasok" class="form-label">Pemasok</label>
                                    <input type="text" class="form-control" id="pemasok" name="pemasok" 
                                           placeholder="Masukkan nama pemasok" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="harga_dasar" class="form-label">Harga Dasar (Modal)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="harga_dasar" name="harga_dasar" 
                                               placeholder="Harga modal" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="tipe_barang" class="form-label">Tipe Barang</label>
                                    <select class="form-select" id="tipe_barang" name="tipe_barang" required>
                                        <option value="">-- Pilih Tipe --</option>
                                        <option value="Makanan">Makanan</option>
                                        <option value="Minuman">Minuman</option>
                                        <option value="Snack">Snack</option>
                                        <option value="ATK">ATK</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="stok" class="form-label">Stok</label>
                                    <input type="number" class="form-control" id="stok" name="stok" 
                                           placeholder="Masukkan jumlah stok" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="harga_jual" class="form-label">Harga Jual</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="harga_jual" name="harga_jual" 
                                               placeholder="Harga jual" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Simpan Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- TABEL STOK BARANG -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Daftar Stok Barang</span>
                <div class="badge bg-primary">
                    Total: <?= count($stokBarang) ?> Barang
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Barang</th>
                                <th>Tipe</th>
                                <th>Pemasok</th>
                                <th>Stok</th>
                                <th>Harga Dasar</th>
                                <th>Harga Jual</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stokBarang)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                                        Tidak ada data stok barang
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($stokBarang as $index => $barang): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($barang['nama_barang'] ?? $barang['nema'] ?? '') ?></td>
                                    <td>
                                        <span class="badge bg-info"><?= htmlspecialchars($barang['tipe']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($barang['pemasok']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= ($barang['stok'] > 10) ? 'success' : (($barang['stok'] > 0) ? 'warning' : 'danger') ?>">
                                            <?= htmlspecialchars($barang['stok']) ?>
                                        </span>
                                    </td>
                                    <td>Rp <?= number_format($barang['harga_dasar'] ?? $barang['harpa_dasar'] ?? 0, 0, ',', '.') ?></td>
                                    <td>Rp <?= number_format($barang['harga_jual'] ?? $barang['harpa_juai'] ?? 0, 0, ',', '.') ?></td>
                                    <td>
                                        <!-- TOMBOL RESTOCK -->
                                        <button class="btn btn-sm btn-outline-primary me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#restockModal<?= $barang['id'] ?>">
                                            <i class="fas fa-plus"></i> Restock
                                        </button>
                                    </td>
                                </tr>

                                <!-- MODAL RESTOCK -->
                                <div class="modal fade" id="restockModal<?= $barang['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Restock <?= htmlspecialchars($barang['nama_barang'] ?? $barang['nema'] ?? '') ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="restock">
                                                    <input type="hidden" name="id" value="<?= $barang['id'] ?>">
                                                    <div class="mb-3">
                                                        <label for="jumlah<?= $barang['id'] ?>" class="form-label">Jumlah Restock</label>
                                                        <input type="number" class="form-control" 
                                                               id="jumlah<?= $barang['id'] ?>" 
                                                               name="jumlah" 
                                                               min="1"
                                                               required
                                                               placeholder="Masukkan jumlah restock">
                                                    </div>
                                                    <div class="alert alert-info">
                                                        <small>
                                                            <i class="fas fa-info-circle"></i>
                                                            Stok saat ini: <strong><?= $barang['stok'] ?></strong>
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save me-1"></i> Simpan Restock
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>