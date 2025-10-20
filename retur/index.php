<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Kantin</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../css/table.css">
</head>

<body>
    <div class="container">
        <h2 class="text-center">Stok Kantin</h2>

        <ul class="nav nav-tabs">
            <li class="active"><a href="#tabStok" data-toggle="tab"><strong>Stok Barang</strong></a></li>
            <li><a href="#tabInput" data-toggle="tab"><strong>Restock/Tambah Barang</strong></a></li>
            <li><a href="#tabRetur" data-toggle="tab"><strong>Retur Barang</strong></a></li>
            <li><a href="#tabRiwayat" data-toggle="tab"><strong>Riwayat Transaksi</strong></a></li>
        </ul>

        <div class="tab-content" style="margin-top:15px;">

            <!-- TAB STOK -->
            <div class="tab-pane fade in active" id="tabStok">
                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="filterTipe">Filter Tipe:</label>
                            <select class="form-control" id="filterTipe">
                                <option value="Semua">Semua</option>
                                <option value="Makanan">Makanan</option>
                                <option value="Minuman">Minuman</option>
                                <option value="Alat Tulis">Alat Tulis</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-8 text-right">
                        <button class="btn btn-danger" id="clearAllBtn" style="margin-top: 25px; display: none;">Hapus
                            Semua Data</button>
                    </div>
                </div>
                <hr>
                <div id="dataListContainer" class="row">
                    <!-- Data akan di-load via JavaScript -->
                </div>
            </div>

            <!-- TAB INPUT -->
            <div class="tab-pane fade" id="tabInput">
                <h3>Tambah / Restock Barang</h3>
                <div id="modeAksiContainer" style="margin-bottom: 15px;"></div>

                <form id="itemForm">
                    <input type="hidden" id="itemId" name="itemId">

                    <div class="form-group">
                        <label>Barang</label>
                        <input type="text" id="tipe" name="tipe" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Pemasok</label>
                        <input type="text" id="pemasok" name="pemasok" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Stok</label>
                        <input type="number" id="stok" name="stok" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Harga Dasar</label>
                        <input type="number" id="harga_dasar" name="harga_dasar" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Harga Jual</label>
                        <input type="number" id="harga_jual" name="harga_jual" class="form-control" required>
                    </div>

                    <div class="form-group text-center mt-3">
                        <button type="submit" class="btn btn-primary">Simpan Data</button>
                        <button type="reset" class="btn btn-light">Reset Form</button>
                    </div>
                </form>

            </div>

            <!-- TAB RETUR BARANG -->
            <div class="tab-pane fade" id="tabRetur">
                <h3>Retur Barang</h3>
                <p class="text-muted">Gunakan fitur ini untuk melakukan retur barang yang rusak atau tidak layak jual.
                </p>
                <hr>

                <form id="returForm">
                    <div class="form-group">
                        <label for="returBarang">Pilih Barang yang Akan Dikembalikan</label>
                        <select class="form-control" id="returBarang" name="barang_id" required>
                            <option value="">-- Pilih Barang --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="returJumlah">Jumlah Barang yang Dikembalikan</label>
                        <input type="number" class="form-control" id="returJumlah" name="jumlah" min="1" required>
                    </div>

                    <div class="form-group">
                        <label for="returAlasan">Alasan Retur</label>
                        <select class="form-control" id="returAlasan" name="alasan" required>
                            <option value="">-- Pilih Alasan --</option>
                            <option value="Barang Rusak">Barang Rusak</option>
                            <option value="Barang Kadaluarsa">Barang Kadaluarsa</option>
                            <option value="Barang Cacat Produksi">Barang Cacat Produksi</option>
                            <option value="Tidak Sesuai Pesanan">Tidak Sesuai Pesanan</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div class="form-group" id="alasanLainnyaContainer" style="display: none;">
                        <label for="returAlasanLainnya">Alasan Lainnya</label>
                        <input type="text" class="form-control" id="returAlasanLainnya" name="alasan_lainnya"
                            placeholder="Jelaskan alasan retur">
                    </div>

                    <div class="form-group">
                        <label for="returKeterangan">Keterangan Tambahan</label>
                        <textarea class="form-control" id="returKeterangan" name="keterangan" rows="3"
                            placeholder="Tambahkan keterangan jika diperlukan"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="returFoto">Upload Foto Bukti (Opsional)</label>
                        <input type="file" class="form-control" id="returFoto" name="foto_bukti" accept="image/*">
                        <small class="text-muted">Upload foto barang yang rusak/cacat sebagai bukti</small>
                        <div id="fotoPreview" style="margin-top: 10px;"></div>
                    </div>

                    <div class="form-group text-center">
                        <button type="button" class="btn btn-warning btn-lg" id="submitReturBtn">Proses Retur</button>
                        <button type="button" class="btn btn-default btn-lg" id="resetReturBtn">Reset Form</button>
                    </div>
                </form>

                <hr>
                <h4>Riwayat Retur Barang</h4>
                <div id="riwayatReturContainer" class="row">
                    <!-- Data retur akan di-load via JavaScript -->
                </div>
            </div>

            <!-- TAB RIWAYAT -->
            <div id="tabRiwayat" class="tab-pane fade">
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

    <!-- Modal -->
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Notifikasi</h4>
                </div>
                <div class="modal-body">
                    <p id="modalMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Konfirmasi Hapus</h4>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus data ini?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Preview Foto -->
    <div class="modal fade" id="fotoPreviewModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Preview Foto Bukti</h4>
                </div>
                <div class="modal-body text-center">
                    <img id="modalFotoPreview" src="" alt="Foto Bukti" style="max-width: 100%; max-height: 500px;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="js/table.js"></script>
</body>

</html>