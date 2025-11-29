<?php
require_once '../Database/config.php';
require_once '../Database/functions.php';

// Start session untuk mendapatkan user yang login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
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
                    'stok' => $_POST['stok'],
                    'harga_dasar' => $_POST['harga_dasar'],
                    'harga_jual' => $_POST['harga_jual']
                ];

                if (tambahBarang($data)) {
                    $pdo = getDBConnection();
                    $lastId = $pdo->lastInsertId();

                    // Ambil data barang yang baru ditambahkan
                    $stmt = $pdo->prepare("SELECT nama_barang FROM stok_barang WHERE id = ?");
                    $stmt->execute([$lastId]);
                    $barang = $stmt->fetch();

                    $transaksiData = [
                        'nama_barang' => $barang['nama_barang'],
                        'jenis_transaksi' => 'tambah_barang',
                        'pemasok' => $_SESSION['username'] ?? 'karyawan',
                        'penjual' => $_POST['penjual'],
                        'jumlah' => $data['stok'],
                        'harga' => $data['harga_dasar'],
                        'total' => $data['stok'] * $data['harga_dasar'],
                        'keterangan' => 'Penambahan barang baru: ' . $data['nama_barang']
                    ];

                    // INTEGRASI HUTANG UNTUK TAMBAH BARANG
                    if ($_POST['metode_bayar'] == 'hutang') {
                        require_once '../hutang/tambah_hutang.php';

                        $dataHutang = [
                            'pemasok' => $_POST['penjual'],
                            'nama_barang' => $data['nama_barang'],
                            'jumlah' => $data['stok'],
                            'harga_dasar' => $data['harga_dasar'],
                            'total_hutang' => $data['stok'] * $data['harga_dasar']
                        ];

                        // Tambahkan ke tabel hutang
                        tambahHutang($dataHutang);

                        // Update keterangan transaksi
                        $transaksiData['keterangan'] = 'Penambahan barang baru (HUTANG): ' . $data['nama_barang'];
                    }

                    catatTransaksi($transaksiData);
                    $success = "Barang berhasil ditambahkan!" . ($_POST['metode_bayar'] == 'hutang' ? " (Mode Hutang)" : " (Mode Cash)");
                } else {
                    $error = "Gagal menambahkan barang!";
                }
                break;

            case 'restock':
                $id = $_POST['id'];
                $jumlah = $_POST['jumlah'];
                $harga_dasar_restock = $_POST['harga_dasar_restock'];
                $metode_bayar_restock = $_POST['metode_bayar_restock'];
                $penjual_restock = $_POST['penjual_restock'];

                if (updateStokBarang($id, $jumlah)) {
                    $pdo = getDBConnection();
                    $stmt = $pdo->prepare("SELECT nama_barang FROM stok_barang WHERE id = ?");
                    $stmt->execute([$id]);
                    $barang = $stmt->fetch();

                    $transaksiData = [
                        'nama_barang' => $barang['nama_barang'],
                        'jenis_transaksi' => 'restock',
                        'pemasok' => $_SESSION['username'] ?? 'karyawan',
                        'penjual' => $penjual_restock,
                        'jumlah' => $jumlah,
                        'harga' => $harga_dasar_restock,
                        'total' => $jumlah * $harga_dasar_restock,
                        'keterangan' => 'Restock barang: ' . $barang['nama_barang'] . ' - Harga: Rp ' . number_format($harga_dasar_restock, 0, ',', '.')
                    ];

                    // INTEGRASI HUTANG UNTUK RESTOCK
                    if ($metode_bayar_restock == 'hutang') {
                        require_once '../hutang/tambah_hutang.php';

                        $dataHutang = [
                            'pemasok' => $penjual_restock,
                            'nama_barang' => $barang['nama_barang'],
                            'jumlah' => $jumlah,
                            'harga_dasar' => $harga_dasar_restock,
                            'total_hutang' => $jumlah * $harga_dasar_restock
                        ];

                        // Tambahkan ke tabel hutang
                        tambahHutang($dataHutang);

                        // Update keterangan transaksi
                        $transaksiData['keterangan'] = 'Restock barang (HUTANG): ' . $barang['nama_barang'] . ' - Harga: Rp ' . number_format($harga_dasar_restock, 0, ',', '.');
                    }

                    catatTransaksi($transaksiData);
                    $success = "Restock berhasil dilakukan!" . ($metode_bayar_restock == 'hutang' ? " (Mode Hutang)" : " (Mode Cash)");
                } else {
                    $error = "Gagal melakukan restock!";
                }
                break;

            case 'multi_restock':
                $restock_items = $_POST['restock_items'];
                $penjual_multi = $_POST['penjual_multi'];
                $processed_count = 0;
                $total_items = count($restock_items);

                $total_pembelian = 0;

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

                            $subtotal = $jumlah * $harga_dasar;
                            $total_pembelian += $subtotal;

                            $transaksiData = [
                                'nama_barang' => $barang['nama_barang'],
                                'jenis_transaksi' => 'restock',
                                'pemasok' => $_SESSION['username'] ?? 'karyawan',
                                'penjual' => $penjual_multi,
                                'jumlah' => $jumlah,
                                'harga' => $harga_dasar,
                                'total' => $subtotal,
                                'keterangan' => 'Multi Restock: ' . $barang['nama_barang']
                            ];

                            // Integrasi hutang
                            if ($metode_bayar == 'hutang') {
                                require_once '../hutang/tambah_hutang.php';
                                $dataHutang = [
                                    'pemasok' => $penjual_multi,
                                    'nama_barang' => $barang['nama_barang'],
                                    'jumlah' => $jumlah,
                                    'harga_dasar' => $harga_dasar,
                                    'total_hutang' => $subtotal
                                ];
                                tambahHutang($dataHutang);
                                $transaksiData['keterangan'] = 'Multi Restock (HUTANG): ' . $barang['nama_barang'];
                            }

                            catatTransaksi($transaksiData);
                            $processed_count++;
                        }
                    }
                }

                if ($processed_count > 0) {
                    $success = "Multi Restock berhasil! " . $processed_count . " dari " . $total_items . " barang berhasil di-restock.";
                } else {
                    $error = "Tidak ada barang yang berhasil di-restock!";
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
        .navbar-brand {
            font-weight: bold;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e3e6f0;
            font-weight: 600;
        }

        .mode-indicator {
            background-color: #e8f5e8;
            border-left: 4px solid #28a745;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }

        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        .btn-info:hover {
            background-color: #138496;
            border-color: #117a8b;
        }

        .btn-outline-success {
            color: #28a745;
            border-color: #28a745;
        }

        .btn-outline-success:hover {
            background-color: #28a745;
            color: white;
        }

        .table th {
            background-color: #f8f9fa;
        }

        .badge-stok-tinggi {
            background-color: #28a745;
        }

        .badge-stok-sedang {
            background-color: #ffc107;
            color: #000;
        }

        .badge-stok-rendah {
            background-color: #dc3545;
        }

        .user-info {
            background-color: #e8f5e8;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.9em;
            color: #155724;
        }

        .bg-success {
            background-color: #28a745 !important;
        }

        .text-success {
            color: #28a745 !important;
        }

        .border-success {
            border-color: #28a745 !important;
        }

        .alert-info {
            background-color: #e8f5e8;
            border-color: #28a745;
            color: #155724;
        }

        .multi-restock-table input,
        .multi-restock-table select {
            font-size: 0.9em;
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
                <a class="nav-link active" href="#">
                    <i class="fas fa-boxes me-1"></i> Stok Barang
                </a>
                <a class="nav-link" href="/?q=riwayat_transaksi">
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
            <h1 class="h3">
                <i class="fas fa-boxes text-success me-2"></i>Stok Barang
            </h1>
            <div>
                <button class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#multiRestockModal">
                    <i class="fas fa-layer-group me-1"></i> Multi Restock
                </button>
                <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#tambahBarangModal">
                    <i class="fas fa-plus-circle me-1"></i> Tambah Barang
                </button>
                <a href="/?q=riwayat_transaksi" class="btn btn-outline-success">
                    <i class="fas fa-history me-1"></i> Riwayat Transaksi
                </a>
            </div>
        </div>

        <!-- INFO USER -->
        <?php if (isset($_SESSION['username'])): ?>
            <div class="alert alert-info d-flex align-items-center">
                <i class="fas fa-info-circle me-2"></i>
                <div>
                    <strong>Info:</strong> Anda login sebagai <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>.
                    Nama Anda akan tercatat sebagai pemasok di riwayat transaksi.
                </div>
            </div>
        <?php endif; ?>

        <!-- MODAL TAMBAH BARANG -->
        <div class="modal fade" id="tambahBarangModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus-circle me-2"></i>Tambah Barang Baru
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <div class="mode-indicator">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Mode Tambah Barang:</strong> Anda sedang menambah barang baru ke sistem
                            </div>
                            <input type="hidden" name="action" value="tambah_barang">

                            <!-- FORM INPUT -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nama_barang" class="form-label">Nama Barang <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama_barang" name="nama_barang"
                                        placeholder="Masukkan nama barang" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="tipe_barang" class="form-label">Tipe Barang <span class="text-danger">*</span></label>
                                    <select class="form-select" id="tipe_barang" name="tipe_barang" required>
                                        <option value="">-- Pilih Tipe Barang --</option>
                                        <option value="Makanan">Makanan</option>
                                        <option value="Minuman">Minuman</option>
                                        <option value="Snack">Snack</option>
                                        <option value="ATK">ATK (Alat Tulis Kantor)</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="stok" class="form-label">Stok Awal <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="stok" name="stok"
                                        placeholder="Masukkan jumlah stok awal" min="0" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="harga_dasar" class="form-label">Harga Dasar (Modal) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="harga_dasar" name="harga_dasar"
                                            placeholder="Harga modal dari pemasok" min="0" required>
                                    </div>
                                    <div class="form-text">Harga beli dari pemasok</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="harga_jual" class="form-label">Harga Jual <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="harga_jual" name="harga_jual"
                                            placeholder="Harga jual ke customer" min="0" required>
                                    </div>
                                    <div class="form-text">Harga jual ke customer</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="penjual" class="form-label">Penjual <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="penjual" name="penjual"
                                        placeholder="Masukkan nama penjual" required
                                        value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="metode_bayar" class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                                    <select class="form-select" id="metode_bayar" name="metode_bayar" required>
                                        <option value="cash">Cash</option>
                                        <option value="hutang">Hutang</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i> Batal
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i> Simpan Barang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL MULTI RESTOCK -->
        <div class="modal fade" id="multiRestockModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-layer-group me-2"></i>Multi Restock
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" id="multiRestockForm">
                        <div class="modal-body">
                            <div class="mode-indicator">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Mode Multi Restock:</strong> Restock beberapa barang sekaligus
                            </div>
                            <input type="hidden" name="action" value="multi_restock">

                            <!-- INPUT PENJUAL -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="penjual_multi" class="form-label">Penjual <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="penjual_multi" name="penjual_multi"
                                        placeholder="Masukkan nama penjual" required
                                        value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped multi-restock-table">
                                    <thead>
                                        <tr>
                                            <th width="50">#</th>
                                            <th>Nama Barang</th>
                                            <th width="120">Stok Saat Ini</th>
                                            <th width="150">Jumlah Restock</th>
                                            <th width="200">Harga Dasar</th>
                                            <th width="150">Metode Bayar</th>
                                            <th width="120">Subtotal</th>
                                            <th width="50">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="multiRestockTable">
                                        <!-- Rows akan ditambahkan via JavaScript -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="6" class="text-end"><strong>Total:</strong></td>
                                            <td><strong id="totalMultiRestock">Rp 0</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between mt-3">
                                <button type="button" class="btn btn-outline-primary" id="addRestockRow">
                                    <i class="fas fa-plus me-1"></i> Tambah Barang
                                </button>
                                <div class="text-muted">
                                    <small>Pilih barang dari dropdown untuk mulai restock</small>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i> Batal
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i> Simpan Semua Restock
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- TABEL STOK BARANG -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-list me-2"></i>Daftar Stok Barang
                </span>
                <div class="badge bg-success">
                    <i class="fas fa-box me-1"></i> Total: <?= count($stokBarang) ?> Barang
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($stokBarang)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-box-open fa-4x mb-3"></i>
                        <h5>Belum ada data stok barang</h5>
                        <p class="mb-4">Silakan tambah barang baru untuk memulai</p>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#tambahBarangModal">
                            <i class="fas fa-plus-circle me-1"></i> Tambah Barang Pertama
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th width="50">#</th>
                                    <th>Nama Barang</th>
                                    <th>Tipe</th>
                                    <th>Stok</th>
                                    <th>Harga Dasar</th>
                                    <th>Harga Jual</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stokBarang as $index => $barang): ?>
                                    <?php
                                    $stok_class = 'badge-stok-tinggi';
                                    if ($barang['stok'] <= 10) {
                                        $stok_class = 'badge-stok-rendah';
                                    } elseif ($barang['stok'] <= 15) {
                                        $stok_class = 'badge-stok-sedang';
                                    }
                                    ?>
                                    <tr>
                                        <td class="text-center"><?= $index + 1 ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($barang['nama_barang']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-success"><?= htmlspecialchars($barang['tipe']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $stok_class ?>">
                                                <?= htmlspecialchars($barang['stok']) ?> pcs
                                            </span>
                                        </td>
                                        <td>Rp <?= number_format($barang['harga_dasar'], 0, ',', '.') ?></td>
                                        <td>
                                            <strong>Rp <?= number_format($barang['harga_jual'], 0, ',', '.') ?></strong>
                                        </td>
                                        <td>
                                            <!-- TOMBOL RESTOCK -->
                                            <button class="btn btn-sm btn-outline-success"
                                                data-bs-toggle="modal"
                                                data-bs-target="#restockModal<?= $barang['id'] ?>"
                                                title="Restock Barang">
                                                <i class="fas fa-plus"></i> Restock
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- MODAL RESTOCK -->
                                    <div class="modal fade" id="restockModal<?= $barang['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-plus me-2"></i>Restock Barang
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="restock">
                                                        <input type="hidden" name="id" value="<?= $barang['id'] ?>">

                                                        <div class="mb-3">
                                                            <label class="form-label">Nama Barang</label>
                                                            <input type="text" class="form-control" value="<?= htmlspecialchars($barang['nama_barang']) ?>" readonly>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="penjual_restock<?= $barang['id'] ?>" class="form-label">Penjual <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control"
                                                                id="penjual_restock<?= $barang['id'] ?>"
                                                                name="penjual_restock"
                                                                placeholder="Masukkan nama penjual" required
                                                                value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>">
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="jumlah<?= $barang['id'] ?>" class="form-label">Jumlah Restock <span class="text-danger">*</span></label>
                                                            <input type="number" class="form-control"
                                                                id="jumlah<?= $barang['id'] ?>"
                                                                name="jumlah"
                                                                min="1"
                                                                required
                                                                placeholder="Masukkan jumlah restock">
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="harga_dasar_restock<?= $barang['id'] ?>" class="form-label">Harga Dasar Restock <span class="text-danger">*</span></label>
                                                            <div class="input-group">
                                                                <span class="input-group-text">Rp</span>
                                                                <input type="number" class="form-control"
                                                                    id="harga_dasar_restock<?= $barang['id'] ?>"
                                                                    name="harga_dasar_restock"
                                                                    min="0"
                                                                    required
                                                                    placeholder="Harga beli untuk restock ini">
                                                            </div>
                                                            <div class="form-text">Harga beli untuk restock ini</div>
                                                        </div>

                                                        <!-- PILIHAN METODE BAYAR RESTOCK -->
                                                        <div class="mb-3">
                                                            <label for="metode_bayar_restock<?= $barang['id'] ?>" class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                                                            <select class="form-select" id="metode_bayar_restock<?= $barang['id'] ?>" name="metode_bayar_restock" required>
                                                                <option value="cash">Cash</option>
                                                                <option value="hutang">Hutang</option>
                                                            </select>
                                                        </div>

                                                        <div class="alert alert-info">
                                                            <div class="d-flex">
                                                                <i class="fas fa-info-circle me-2 mt-1"></i>
                                                                <div>
                                                                    <small>
                                                                        <strong>Informasi Stok:</strong><br>
                                                                        Stok saat ini: <strong><?= $barang['stok'] ?> pcs</strong><br>
                                                                        Harga dasar saat ini: <strong>Rp <?= number_format($barang['harga_dasar'], 0, ',', '.') ?></strong>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                                            <i class="fas fa-times me-1"></i> Batal
                                                        </button>
                                                        <button type="submit" class="btn btn-success">
                                                            <i class="fas fa-save me-1"></i> Simpan Restock
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
        // Data barang untuk dropdown
        const barangList = <?= json_encode($stokBarang) ?>;

        // Counter untuk row
        let restockRowCount = 0;

        // Tambah row baru
        document.getElementById('addRestockRow').addEventListener('click', function() {
            addRestockRow();
        });

        // Fungsi tambah row
        function addRestockRow() {
            const table = document.getElementById('multiRestockTable');
            const rowId = restockRowCount++;

            const row = document.createElement('tr');
            row.id = `restockRow_${rowId}`;
            row.innerHTML = `
                <td>${rowId + 1}</td>
                <td>
                    <select class="form-select barang-select" name="restock_items[${rowId}][id]" required onchange="updateBarangInfo(${rowId})">
                        <option value="">-- Pilih Barang --</option>
                        ${barangList.map(barang => `
                            <option value="${barang.id}" data-stok="${barang.stok}" data-harga="${barang.harga_dasar}">
                                ${barang.nama_barang} (Stok: ${barang.stok})
                            </option>
                        `).join('')}
                    </select>
                </td>
                <td id="currentStock_${rowId}">-</td>
                <td>
                    <input type="number" class="form-control" name="restock_items[${rowId}][jumlah]" 
                           min="1" required placeholder="Jumlah" oninput="calculateSubtotal(${rowId})">
                </td>
                <td>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control harga-input" name="restock_items[${rowId}][harga_dasar]" 
                               min="0" required placeholder="Harga" oninput="calculateSubtotal(${rowId})">
                    </div>
                </td>
                <td>
                    <select class="form-select" name="restock_items[${rowId}][metode_bayar]" required>
                        <option value="cash">Cash</option>
                        <option value="hutang">Hutang</option>
                    </select>
                </td>
                <td>
                    <span id="subtotal_${rowId}" class="text-success fw-bold">Rp 0</span>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRestockRow(${rowId})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            table.appendChild(row);
            updateTotal();
        }

        // Update info barang
        function updateBarangInfo(rowId) {
            const select = document.querySelector(`#restockRow_${rowId} .barang-select`);
            const selectedOption = select.options[select.selectedIndex];
            const currentStock = document.getElementById(`currentStock_${rowId}`);
            const hargaInput = document.querySelector(`#restockRow_${rowId} .harga-input`);

            if (selectedOption && selectedOption.value) {
                currentStock.textContent = selectedOption.getAttribute('data-stok');
                hargaInput.value = selectedOption.getAttribute('data-harga');
            } else {
                currentStock.textContent = '-';
                hargaInput.value = '';
            }
            calculateSubtotal(rowId);
        }

        // Hitung subtotal
        function calculateSubtotal(rowId) {
            const jumlahInput = document.querySelector(`input[name="restock_items[${rowId}][jumlah]"]`);
            const hargaInput = document.querySelector(`input[name="restock_items[${rowId}][harga_dasar]"]`);
            const subtotalElement = document.getElementById(`subtotal_${rowId}`);

            if (jumlahInput && hargaInput && jumlahInput.value && hargaInput.value) {
                const subtotal = parseInt(jumlahInput.value) * parseInt(hargaInput.value);
                subtotalElement.textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
            } else {
                subtotalElement.textContent = 'Rp 0';
            }
            updateTotal();
        }

        // Update total
        function updateTotal() {
            let total = 0;
            document.querySelectorAll('[id^="subtotal_"]').forEach(element => {
                const subtotalText = element.textContent.replace('Rp ', '').replace(/\./g, '');
                total += parseInt(subtotalText) || 0;
            });
            document.getElementById('totalMultiRestock').textContent = 'Rp ' + total.toLocaleString('id-ID');
        }

        // Hapus row
        function removeRestockRow(rowId) {
            const row = document.getElementById(`restockRow_${rowId}`);
            if (row) {
                row.remove();
                updateTotal();
                renumberRows();
            }
        }

        // Renumber rows
        function renumberRows() {
            const rows = document.querySelectorAll('#multiRestockTable tr');
            rows.forEach((row, index) => {
                if (row.cells[0]) {
                    row.cells[0].textContent = index + 1;
                }
            });
        }

        // Auto tambah row pertama saat modal dibuka
        document.getElementById('multiRestockModal').addEventListener('show.bs.modal', function() {
            // Clear existing rows
            const table = document.getElementById('multiRestockTable');
            if (table) {
                table.innerHTML = '';
            }
            restockRowCount = 0;
            addRestockRow();
        });

        // Auto close alerts setelah 5 detik
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    if (alert && alert.parentNode) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, 5000);
            });
        });
    </script>
</body>

</html>