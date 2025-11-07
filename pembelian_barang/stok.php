<?php
require_once '../Database/config.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Stok Barang</title>
    <link rel="stylesheet" href="../../css/table.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <h2 class="text-center my-4">Stok Kantin</h2>

        <!-- Notifikasi Success -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Sukses!</strong> Operasi berhasil dilakukan.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tabStok-tab" data-bs-toggle="tab" data-bs-target="#tabStok"
                    type="button" role="tab" aria-controls="tabStok" aria-selected="true">
                    <strong>Stok Barang</strong>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tabInput-tab" data-bs-toggle="tab" data-bs-target="#tabInput" type="button"
                    role="tab" aria-controls="tabInput" aria-selected="false">
                    <strong>Restock/Tambah Barang</strong>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tabRiwayat-tab" data-bs-toggle="tab" data-bs-target="#tabRiwayat"
                    type="button" role="tab" aria-controls="tabRiwayat" aria-selected="false">
                    <strong>Riwayat Transaksi</strong>
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content mt-3" id="myTabContent">

            <!-- TAB STOK -->
            <div class="tab-pane fade show active" id="tabStok" role="tabpanel" aria-labelledby="tabStok-tab">
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="filterTipe" class="form-label">Filter Tipe:</label>
                            <select class="form-select" id="filterTipe">
                                <option value="Semua">Semua</option>
                                <option value="Makanan">Makanan</option>
                                <option value="Minuman">Minuman</option>
                                <option value="Alat Tulis">Alat Tulis</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-8 text-end">
                        <button class="btn btn-danger mt-3" id="clearAllBtn" style="display: none;">
                            <i class="fas fa-trash"></i> Hapus Semua Data
                        </button>
                    </div>
                </div>
                <hr>
                <div id="dataListContainer" class="row">
                    <!-- Data akan di-load via JavaScript -->
                </div>
            </div>

            <!-- TAB INPUT -->
            <div class="tab-pane fade" id="tabInput" role="tabpanel" aria-labelledby="tabInput-tab">
                <h3><i class="fas fa-plus-circle"></i> Tambah / Restock Barang</h3>
                <div id="modeAksiContainer" class="mb-3">
                    <div class="alert alert-success">
                        <i class="fas fa-plus"></i> <strong>Mode Tambah:</strong> Anda sedang menambah barang baru
                    </div>
                </div>

                <form method="post" id="itemForm">
                    <input type="hidden" id="itemId" name="itemId">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="inputNama" class="form-label">Nama Barang</label>
                                <input type="text" id="inputNama" name="nama" class="form-control"
                                    placeholder="Masukkan nama barang" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="inputTipe" class="form-label">Tipe Barang</label>
                                <select class="form-select" id="inputTipe" name="tipe" required>
                                    <option value="">-- Pilih Tipe --</option>
                                    <option value="Makanan">Makanan</option>
                                    <option value="Minuman">Minuman</option>
                                    <option value="Alat Tulis">Alat Tulis</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="inputPemasok" class="form-label">Pemasok</label>
                                <input type="text" id="inputPemasok" name="pemasok" class="form-control"
                                    placeholder="Masukkan nama pemasok" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="inputStok" class="form-label">Stok</label>
                                <input type="number" id="inputStok" name="stok" class="form-control" min="0"
                                    placeholder="Masukkan jumlah stok" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="inputHargaDasar" class="form-label">Harga Dasar (Modal)</label>
                                <input type="number" id="inputHargaDasar" name="harga_dasar" class="form-control"
                                    min="0" placeholder="Harga modal" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="inputHargaJual" class="form-label">Harga Jual</label>
                                <input type="number" id="inputHargaJual" name="harga_jual" class="form-control" min="0"
                                    placeholder="Harga jual" required>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Data
                        </button>
                        <button type="button" class="btn btn-light" id="resetBtn">
                            <i class="fas fa-redo"></i> Reset Form
                        </button>
                    </div>
                </form>
            </div>

            <!-- TAB RIWAYAT -->
            <div class="tab-pane fade" id="tabRiwayat" role="tabpanel" aria-labelledby="tabRiwayat-tab">
                <div class="row">
                    <h3><i class="fas fa-history"></i> Riwayat Perubahan Stok</h3>
                    <p class="text-muted">Semua update stok, perubahan harga, dan penghapusan barang tercatat di sini.</p>
                    <hr>
                </div>
                <div class="row" id="historyListContainer">
                    <!-- Riwayat akan di-load via JavaScript -->
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Konfirmasi -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmModalLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmMessage">Apakah Anda yakin ingin menghapus data ini?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Notifikasi -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="messageModalLabel">Notifikasi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="modalMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>