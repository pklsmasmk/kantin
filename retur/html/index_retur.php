<?php
require_once '../Database/config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Retur Barang</title>
    <?php include 'index_css.php'; ?>
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

        <!-- Form Retur Barang -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Form Retur Barang</h5>
                    </div>
                    <div class="card-body">
                        <form id="formRetur">
                            <div class="mb-3">
                                <label for="barang_id" class="form-label">Pilih Barang</label>
                                <select class="form-select" id="barang_id" name="barang_id" required>
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

                            <div class="mb-3">
                                <label for="jumlah" class="form-label">Jumlah Retur</label>
                                <input type="number" class="form-control" id="jumlah" name="jumlah" min="1" required>
                                <small class="form-text text-muted">Stok tersedia: <span id="stokTersedia">0</span></small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Alasan Retur</label>
                                <div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="alasan" id="rusak" value="Rusak" required>
                                        <label class="form-check-label" for="rusak">Rusak</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="alasan" id="kadaluarsa" value="Kadaluarsa">
                                        <label class="form-check-label" for="kadaluarsa">Kadaluarsa</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="alasan" id="lainnya" value="Lainnya">
                                        <label class="form-check-label" for="lainnya">Lainnya</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3" id="alasanLainnyaContainer" style="display: none;">
                                <label for="alasan_lainnya" class="form-label">Alasan Lainnya</label>
                                <input type="text" class="form-control" id="alasan_lainnya" name="alasan_lainnya">
                            </div>

                            <div class="mb-3">
                                <label for="keterangan" class="form-label">Keterangan Tambahan</label>
                                <textarea class="form-control" id="keterangan" name="keterangan" rows="3"></textarea>
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

            <!-- Riwayat Retur -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Riwayat Retur Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Barang</th>
                                        <th>Jumlah</th>
                                        <th>Alasan</th>
                                        <th>Tanggal</th>
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

        <!-- Riwayat Transaksi Lengkap -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>Riwayat Transaksi Lengkap</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Jenis Transaksi</th>
                                        <th>Nama Barang</th>
                                        <th>Keterangan</th>
                                        <th>Perubahan Stok</th>
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

    <?php include 'index_js.php'; ?>
    
    <script>
        $(document).ready(function() {
            // Update stok tersedia ketika barang dipilih
            $('#barang_id').change(function() {
                const selectedOption = $(this).find('option:selected');
                const stok = selectedOption.data('stok') || 0;
                $('#stokTersedia').text(stok);
                $('#jumlah').attr('max', stok);
            });

            // Toggle alasan lainnya
            $('input[name="alasan"]').change(function() {
                if ($(this).val() === 'Lainnya') {
                    $('#alasanLainnyaContainer').show();
                    $('#alasan_lainnya').prop('required', true);
                } else {
                    $('#alasanLainnyaContainer').hide();
                    $('#alasan_lainnya').prop('required', false);
                }
            });

            // Proses form retur
            $('#formRetur').submit(function(e) {
                e.preventDefault();
                
                const formData = $(this).serialize();
                const jumlah = parseInt($('#jumlah').val());
                const stokTersedia = parseInt($('#stokTersedia').text());

                if (jumlah > stokTersedia) {
                    showAlert('Jumlah retur melebihi stok tersedia!', 'danger');
                    return;
                }

                $('#loading').show();
                $('button[type="submit"]').prop('disabled', true);

                $.ajax({
                    url: 'proses_retur.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            showAlert(response.message, 'success');
                            $('#formRetur')[0].reset();
                            $('#stokTersedia').text('0');
                            loadRiwayatRetur();
                            loadRiwayatTransaksi();
                            updateBarangOptions();
                        } else {
                            showAlert(response.message, 'danger');
                        }
                    },
                    error: function() {
                        showAlert('Terjadi kesalahan saat memproses retur!', 'danger');
                    },
                    complete: function() {
                        $('#loading').hide();
                        $('button[type="submit"]').prop('disabled', false);
                    }
                });
            });

            // Fungsi untuk menampilkan alert
            function showAlert(message, type) {
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show mt-3" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                $('#alertContainer').html(alertHtml);
                
                // Auto dismiss setelah 5 detik
                setTimeout(() => {
                    $('.alert').alert('close');
                }, 5000);
            }

            // Load riwayat retur
            function loadRiwayatRetur() {
                $.ajax({
                    url: 'riwayat_retur.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        let html = '';
                        if (data.message) {
                            html = `<tr><td colspan="4" class="text-center">${data.message}</td></tr>`;
                        } else {
                            data.forEach(item => {
                                html += `
                                    <tr>
                                        <td>${item.nama_barang}</td>
                                        <td>${item.jumlah}</td>
                                        <td>${item.alasan}</td>
                                        <td>${formatDate(item.tanggal)}</td>
                                    </tr>
                                `;
                            });
                        }
                        $('#riwayatReturBody').html(html);
                    },
                    error: function() {
                        $('#riwayatReturBody').html('<tr><td colspan="4" class="text-center text-danger">Error loading data</td></tr>');
                    }
                });
            }

            // Load riwayat transaksi
            function loadRiwayatTransaksi() {
                $.ajax({
                    url: 'tampil_riwayat.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        let html = '';
                        if (data.message) {
                            html = `<tr><td colspan="5" class="text-center">${data.message}</td></tr>`;
                        } else {
                            data.forEach(item => {
                                const perubahanStok = item.perubahan_stok > 0 ? 
                                    `<span class="text-success">+${item.perubahan_stok}</span>` : 
                                    `<span class="text-danger">${item.perubahan_stok}</span>`;
                                
                                html += `
                                    <tr>
                                        <td>${formatDate(item.tanggal)}</td>
                                        <td>${item.jenis_transaksi}</td>
                                        <td>${item.nama_barang}</td>
                                        <td>${item.keterangan}</td>
                                        <td>${perubahanStok}</td>
                                    </tr>
                                `;
                            });
                        }
                        $('#riwayatTransaksiBody').html(html);
                    },
                    error: function() {
                        $('#riwayatTransaksiBody').html('<tr><td colspan="5" class="text-center text-danger">Error loading data</td></tr>');
                    }
                });
            }

            // Update opsi barang
            function updateBarangOptions() {
                $.ajax({
                    url: 'get_barang.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        let options = '<option value="">-- Pilih Barang --</option>';
                        data.forEach(item => {
                            options += `<option value="${item.id}" data-stok="${item.stok}">${item.nama} (Stok: ${item.stok})</option>`;
                        });
                        $('#barang_id').html(options);
                    }
                });
            }

            // Format tanggal
            function formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            // Load data saat halaman pertama kali dibuka
            loadRiwayatRetur();
            loadRiwayatTransaksi();
        });
    </script>
</body>
</html>
