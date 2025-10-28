<div class="container">
    <h2 class="text-center my-4">Stok Kantin</h2>

    <!-- Notifikasi Success -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Sukses!</strong> Operasi berhasil dilakukan.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Tabs Navigation - YANG SUDAH DIPERBAIKI -->
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
            <button class="nav-link" id="tabRetur-tab" data-bs-toggle="tab" data-bs-target="#tabRetur" type="button"
                role="tab" aria-controls="tabRetur" aria-selected="false">
                <strong>Retur Barang</strong>
            </button>
        </li>
        <!-- TAB RIWAYAT YANG DITAMBAHKAN -->
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tabRiwayat-tab" data-bs-toggle="tab" data-bs-target="#tabRiwayat" type="button"
                role="tab" aria-controls="tabRiwayat" aria-selected="false">
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
                    <button class="btn btn-danger mt-3" id="clearAllBtn" style="display: none;">Hapus Semua
                        Data</button>
                </div>
            </div>
            <hr>
            <div id="dataListContainer" class="row">
                <!-- Data akan di-load via JavaScript -->
            </div>
        </div>

        <!-- TAB INPUT -->
        <div class="tab-pane fade" id="tabInput" role="tabpanel" aria-labelledby="tabInput-tab">
            <h3>Tambah / Restock Barang</h3>
            <div id="modeAksiContainer" class="mb-3">
                <!-- Default mode indicator -->
                <div class="alert alert-success">
                    <i class="fas fa-plus"></i> <strong>Mode Tambah:</strong> Anda sedang menambah barang baru
                </div>
            </div>

            <form id="itemForm">
                <input type="hidden" id="itemId" name="itemId">

                <div class="mb-3">
                    <label for="inputNama" class="form-label">Nama Barang</label>
                    <input type="text" id="inputNama" name="nama" class="form-control"
                        placeholder="Masukkan nama barang" required>
                </div>

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

                <div class="mb-3">
                    <label for="inputPemasok" class="form-label">Pemasok</label>
                    <input type="text" id="inputPemasok" name="pemasok" class="form-control"
                        placeholder="Masukkan nama pemasok" required>
                </div>

                <div class="mb-3">
                    <label for="inputStok" class="form-label">Stok</label>
                    <input type="number" id="inputStok" name="stok" class="form-control" min="0"
                        placeholder="Masukkan jumlah stok" required>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="inputHargaDasar" class="form-label">Harga Dasar (Modal)</label>
                            <input type="number" id="inputHargaDasar" name="harga_dasar" class="form-control" min="0"
                                placeholder="Harga modal" required>
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
                    <button type="submit" class="btn btn-primary">Simpan Data</button>
                    <button type="button" class="btn btn-light" id="resetBtn">Reset Form</button>
                </div>
            </form>
        </div>

        <!-- TAB RETUR BARANG - YANG SUDAH DIPERBAIKI -->
        <div class="tab-pane fade" id="tabRetur" role="tabpanel" aria-labelledby="tabRetur-tab">
            <h3>Retur Barang</h3>
            <p class="text-muted">Gunakan fitur ini untuk melakukan retur barang yang rusak atau tidak layak jual.
            </p>
            <hr>

            <!-- Form retur yang sudah diperbaiki -->
            <form id="returForm">
                <div class="mb-3">
                    <label for="returBarang" class="form-label">Pilih Barang</label>
                    <select class="form-select" id="returBarang" name="barang_id" required>
                        <option value="">-- Pilih Barang --</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="returJumlah" class="form-label">Jumlah Retur</label>
                    <input type="number" class="form-control" id="returJumlah" name="jumlah" min="1" required>
                </div>

                <div class="mb-3">
                    <label for="returAlasan" class="form-label">Alasan Retur</label>
                    <select class="form-select" id="returAlasan" name="alasan" required>
                        <option value="">-- Pilih Alasan --</option>
                        <option value="Barang Rusak">Barang Rusak</option>
                        <option value="Barang Kadaluarsa">Barang Kadaluarsa</option>
                        <option value="Barang Cacat Produksi">Barang Cacat Produksi</option>
                        <option value="Tidak Sesuai Pesanan">Tidak Sesuai Pesanan</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="mb-3" id="alasanLainnyaContainer" style="display: none;">
                    <label for="returAlasanLainnya" class="form-label">Alasan Lainnya</label>
                    <input type="text" class="form-control" id="returAlasanLainnya" name="alasan_lainnya"
                        placeholder="Jelaskan alasan retur">
                </div>

                <div class="mb-3">
                    <label for="returKeterangan" class="form-label">Keterangan</label>
                    <textarea class="form-control" id="returKeterangan" name="keterangan" rows="3"
                        placeholder="Keterangan tambahan"></textarea>
                </div>

                <div class="text-center">
                    <button type="button" class="btn btn-warning btn-lg" id="submitReturBtn">Proses Retur</button>
                    <button type="button" class="btn btn-outline-secondary btn-lg" id="resetReturBtn">Reset
                        Form</button>
                </div>
            </form>

            <hr>
            <h4>Riwayat Retur Barang</h4>
            <div id="riwayatReturContainer" class="row">
                <!-- Data retur akan di-load via JavaScript -->
            </div>
        </div>

        <!-- TAB RIWAYAT -->
        <div class="tab-pane fade" id="tabRiwayat" role="tabpanel" aria-labelledby="tabRiwayat-tab">
            <div class="row">
                <h3>Laporan Riwayat Perubahan</h3>
                <p class="text-muted">Semua update stok, perubahan harga, dan penghapusan barang tercatat di sini.
                </p>
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