<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Retur Barang</title>
    <style>
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .table-container {
            max-height: 500px;
            overflow-y: auto;
        }

        .alert {
            border-radius: 8px;
        }

        .form-label {
            font-weight: 600;
        }

        #loading {
            display: none;
        }

        .nav-tabs .nav-link {
            font-weight: 600;
            color: #495057;
        }

        .nav-tabs .nav-link.active {
            font-weight: 700;
            color: #0d6efd;
        }
    </style>
</head>

<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mb-4">
                    <i class="fas fa-undo-alt me-2"></i>Sistem Retur Barang
                </h1>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="row mb-4">
            <div class="col-12">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="form-tab" data-bs-toggle="tab" data-bs-target="#form"
                            type="button" role="tab" aria-controls="form" aria-selected="true">
                            <i class="fas fa-exchange-alt me-2"></i>Form Retur Barang
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="riwayat-tab" data-bs-toggle="tab" data-bs-target="#riwayat"
                            type="button" role="tab" aria-controls="riwayat" aria-selected="false">
                            <i class="fas fa-history me-2"></i>Riwayat Retur Terbaru
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="transaksi-tab" data-bs-toggle="tab" data-bs-target="#transaksi"
                            type="button" role="tab" aria-controls="transaksi" aria-selected="false">
                            <i class="fas fa-list-alt me-2"></i>Riwayat Transaksi Lengkap
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="myTabContent">
            <!-- Tab 1: Form Retur Barang -->
            <!-- Form Retur Barang -->
            <div class="tab-pane fade show active" id="form" role="tabpanel" aria-labelledby="form-tab">
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Form Retur Barang</h5>
                            </div>
                            <div class="card-body">
                                <form id="formRetur">
                                    <!-- Barang -->
                                    <div class="mb-3">
                                        <label for="barang_id" class="form-label">Pilih Barang</label>
                                        <select class="form-select" id="barang_id" name="barang_id" required
                                            aria-required="true">
                                            <option value="">-- Pilih Barang --</option>
                                            <?php
                                            try {
                                                $conn = getDBConnection();
                                                $sql = "SELECT id, nama, stok FROM stok_barang WHERE stok > 0 ORDER BY nama";
                                                $stmt = $conn->prepare($sql);
                                                $stmt->execute();
                                                $barang = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                foreach ($barang as $item) {
                                                    echo "<option value='{$item['id']}' data-stok='{$item['stok']}'>{$item['nama']} (Stok: {$item['stok']})</option>";
                                                }
                                            } catch (Exception $e) {
                                                echo "<option value=''>Error loading data</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <!-- Jumlah -->
                                    <div class="mb-3">
                                        <label for="jumlah" class="form-label">Jumlah Retur</label>
                                        <input type="number" class="form-control" id="jumlah" name="jumlah" min="1"
                                            required aria-required="true" aria-describedby="stokHelp">
                                        <div id="stokHelp" class="form-text">Stok tersedia: <span
                                                id="stokTersedia">0</span></div>
                                    </div>

                                    <!-- Alasan Retur -->
                                    <div class="mb-3">
                                        <label class="form-label" id="alasanLabel">Alasan Retur</label>
                                        <div role="radiogroup" aria-labelledby="alasanLabel">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="alasan" id="rusak"
                                                    value="Rusak" required aria-required="true">
                                                <label class="form-check-label" for="rusak">Rusak</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="alasan"
                                                    id="kadaluarsa" value="Kadaluarsa">
                                                <label class="form-check-label" for="kadaluarsa">Kadaluarsa</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="alasan" id="lainnya"
                                                    value="Lainnya">
                                                <label class="form-check-label" for="lainnya">Lainnya</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Alasan Lainnya -->
                                    <div class="mb-3" id="alasanLainnyaContainer" style="display: none;">
                                        <label for="alasan_lainnya" class="form-label">Alasan Lainnya</label>
                                        <input type="text" class="form-control" id="alasan_lainnya"
                                            name="alasan_lainnya" aria-describedby="alasanLainnyaHelp">
                                        <div id="alasanLainnyaHelp" class="form-text">Jelaskan alasan retur lainnya
                                        </div>
                                    </div>

                                    <!-- Keterangan -->
                                    <div class="mb-3">
                                        <label for="keterangan" class="form-label">Keterangan Tambahan</label>
                                        <textarea class="form-control" id="keterangan" name="keterangan" rows="3"
                                            aria-describedby="keteranganHelp"></textarea>
                                        <div id="keteranganHelp" class="form-text">Tambahkan keterangan jika diperlukan
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-paper-plane me-2"></i>Proses Retur
                                    </button>
                                </form>

                                <div id="loading" class="text-center mt-3">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2">Memproses retur...</p>
                                </div>

                                <div id="alertContainer"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Riwayat Retur Terbaru -->
            <!-- Riwayat Retur Terbaru -->
            <div class="tab-pane fade" id="riwayat" role="tabpanel" aria-labelledby="riwayat-tab">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Riwayat Retur Terbaru</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <table class="table table-striped table-hover" aria-label="Riwayat Retur Barang">
                                        <thead>
                                            <tr>
                                                <th scope="col">Barang</th>
                                                <th scope="col">Jumlah</th>
                                                <th scope="col">Alasan</th>
                                                <th scope="col">Tanggal</th>
                                            </tr>
                                        </thead>
                                        <tbody id="riwayatReturBody">
                                            <tr>
                                                <td colspan="4" class="text-center">Memuat data...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="transaksi" role="tabpanel" aria-labelledby="transaksi-tab">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>Riwayat Transaksi Lengkap</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <table class="table table-striped table-hover"
                                        aria-label="Riwayat Transaksi Lengkap">
                                        <thead>
                                            <tr>
                                                <th scope="col">Tanggal</th>
                                                <th scope="col">Jenis Transaksi</th>
                                                <th scope="col">Nama Barang</th>
                                                <th scope="col">Keterangan</th>
                                                <th scope="col">Perubahan Stok</th>
                                            </tr>
                                        </thead>
                                        <tbody id="riwayatTransaksiBody">
                                            <tr>
                                                <td colspan="5" class="text-center">Memuat data...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
</body>
</html>