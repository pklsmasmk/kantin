<?php
function safe_redirect($url) {
    if (!headers_sent()) {
        header("Location: " . $url);
        exit;
    } else {
        echo "<script>window.location.href='" . $url . "';</script>";
        exit;
    }
}

if (!isset($_SESSION['shift'])) {
    safe_redirect('../index.php');
}

date_default_timezone_set('Asia/Jakarta');

$shift = $_SESSION['shift'];
$transaksi = $_SESSION['transaksi'] ?? [];

function validateInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateAmount($amount) {
    $amount_clean = preg_replace("/[^\d]/", "", $amount);
    return is_numeric($amount_clean) && $amount_clean > 0 ? (int)$amount_clean : false;
}

function sync_rekap_to_database($pdo, $shift_data, $transaksi_data) {
    $total_penjualan_tunai = 0;
    $total_pengeluaran_utama = 0;
    $total_pemasukan_lain = 0;
    $total_pengeluaran_lain = 0;

    foreach ($transaksi_data as $t) {
        if (isset($t['nominal'])) {
            if ($t['tipe'] === 'Penjualan Tunai') {
                $total_penjualan_tunai += $t['nominal'];
            } elseif ($t['tipe'] === 'Pengeluaran') {
                $total_pengeluaran_utama += abs($t['nominal']);
            } elseif ($t['tipe'] === 'Masuk Lain') {
                $total_pemasukan_lain += $t['nominal'];
            } elseif ($t['tipe'] === 'Keluar Lain') {
                $total_pengeluaran_lain += abs($t['nominal']);
            }
        }
    }

    $saldo_akhir = $shift_data['saldo_awal'] + $total_penjualan_tunai + $total_pemasukan_lain - $total_pengeluaran_utama - $total_pengeluaran_lain;
    $selisih = $saldo_akhir - $shift_data['saldo_awal'];

    $sql = "UPDATE rekap_shift SET 
            total_penjualan = ?, total_pengeluaran = ?, 
            total_pemasukan_lain = ?, total_pengeluaran_lain = ?,
            saldo_akhir = ?, selisih = ?, last_updated = NOW()
            WHERE shift_id = ?";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $total_penjualan_tunai,
        $total_pengeluaran_utama,
        $total_pemasukan_lain,
        $total_pengeluaran_lain,
        $saldo_akhir,
        $selisih,
        $shift_data['id']
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['main_transaction'])) {
        $jumlah_input = $_POST['jumlah'] ?? '';
        $catatan = validateInput($_POST['catatan'] ?? '');
        $aksi = $_POST['aksi'] === 'penjualan' ? 'penjualan' : 'pengeluaran';

        $jumlah = validateAmount($jumlah_input);

        if ($jumlah !== false && !empty($catatan)) {
            $transaksi[] = [
                'id'        => uniqid('main_', true),
                'waktu'     => date('Y-m-d H:i:s'),
                'tipe'      => $aksi === 'penjualan' ? 'Penjualan Tunai' : 'Pengeluaran',
                'keterangan' => $catatan,
                'nominal'   => $aksi === 'penjualan' ? $jumlah : -$jumlah,
            ];
            $_SESSION['transaksi'] = $transaksi;
            
            sync_rekap_to_database($pdo, $shift, $transaksi);
            
            safe_redirect($_SERVER['PHP_SELF'] . '?success=added_main');
        } else {
            $error = "Data tidak valid. Pastikan jumlah angka positif dan keterangan diisi.";
        }
    }
    
    if (isset($_POST['aksi']) && !isset($_POST['edit_id']) && !isset($_POST['main_transaction'])) {
        $jumlah_input = $_POST['jumlah'] ?? '';
        $catatan = validateInput($_POST['catatan'] ?? '');
        $aksi = $_POST['aksi'] === 'masuk' ? 'masuk' : 'keluar';

        $jumlah = validateAmount($jumlah_input);

        if ($jumlah !== false && !empty($catatan)) {
            $transaksi[] = [
                'id'        => uniqid('trx_', true),
                'waktu'     => date('Y-m-d H:i:s'),
                'tipe'      => $aksi === 'masuk' ? 'Masuk Lain' : 'Keluar Lain',
                'keterangan' => $catatan,
                'nominal'   => $aksi === 'masuk' ? $jumlah : -$jumlah,
            ];
            $_SESSION['transaksi'] = $transaksi;
            
            sync_rekap_to_database($pdo, $shift, $transaksi);
            
            safe_redirect($_SERVER['PHP_SELF'] . '?success=added');
        } else {
            $error = "Data tidak valid. Pastikan jumlah angka positif dan keterangan diisi.";
        }
    }
    
    if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        $edit_id = validateInput($_POST['edit_id']);
        $jumlah_input = $_POST['jumlah'] ?? '';
        $catatan = validateInput($_POST['catatan'] ?? '');
        $aksi = $_POST['aksi'] ?? '';

        $jumlah = validateAmount($jumlah_input);

        if ($jumlah !== false && !empty($catatan) && !empty($aksi)) {
            $found = false;
            foreach ($transaksi as &$t) {
                if (isset($t['id']) && $t['id'] === $edit_id) {
                    if ($aksi === 'penjualan') {
                        $t['tipe'] = 'Penjualan Tunai';
                        $t['nominal'] = $jumlah;
                    } elseif ($aksi === 'pengeluaran') {
                        $t['tipe'] = 'Pengeluaran';
                        $t['nominal'] = -$jumlah;
                    } elseif ($aksi === 'masuk') {
                        $t['tipe'] = 'Masuk Lain';
                        $t['nominal'] = $jumlah;
                    } elseif ($aksi === 'keluar') {
                        $t['tipe'] = 'Keluar Lain';
                        $t['nominal'] = -$jumlah;
                    }
                    
                    $t['keterangan'] = $catatan;
                    $t['waktu'] = date('Y-m-d H:i:s');
                    $found = true;
                    break;
                }
            }
            
            if ($found) {
                $_SESSION['transaksi'] = $transaksi;
                
                sync_rekap_to_database($pdo, $shift, $transaksi);
                
                safe_redirect($_SERVER['PHP_SELF'] . '?success=edited');
            } else {
                $error = "Transaksi tidak ditemukan.";
            }
        } else {
            $error = "Data tidak valid. Pastikan jumlah angka positif dan keterangan diisi.";
        }
    }
}

$total_penjualan_tunai = 0;
$total_pengeluaran_utama = 0;
$total_masuk_lain = 0;
$total_keluar_lain = 0;

foreach ($transaksi as $t) {
    if (isset($t['nominal'])) {
        if ($t['tipe'] === 'Penjualan Tunai') {
            $total_penjualan_tunai += $t['nominal'];
        } elseif ($t['tipe'] === 'Pengeluaran') {
            $total_pengeluaran_utama += abs($t['nominal']);
        } elseif ($t['tipe'] === 'Masuk Lain') {
            $total_masuk_lain += $t['nominal'];
        } elseif ($t['tipe'] === 'Keluar Lain') {
            $total_keluar_lain += abs($t['nominal']);
        }
    }
}

$saldo_awal = $shift['saldo_awal'] ?? 0;
$saldo_akhir = $saldo_awal + $total_penjualan_tunai + $total_masuk_lain - $total_pengeluaran_utama - $total_keluar_lain;
$success = $_GET['success'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM rekap_shift WHERE shift_id = ?");
$stmt->execute([$shift['id']]);
$rekap_data = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Rekap Kas - UAM</title>
</head>
<body>
    <div class="container">
        <div class="rekap-card">
            <header class="card-header">
                <h1>Detail Rekap Kas</h1>
                <div class="saldo-awal">
                    <span>Saldo Awal:</span>
                    <strong>Rp <?= number_format($saldo_awal, 0, ',', '.') ?></strong>
                </div>
                <?php if ($rekap_data): ?>
                <div class="sync-info">
                    <small>🔄 Data tersinkronisasi: <?= date('H:i', strtotime($rekap_data['last_updated'])) ?></small>
                </div>
                <?php endif; ?>
            </header>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <span>❌</span>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span>✅</span>
                    <span>
                        <?php if ($success === 'edited'): ?>
                            Transaksi berhasil diedit
                        <?php elseif ($success === 'added'): ?>
                            Catatan kas berhasil ditambahkan
                        <?php elseif ($success === 'added_main'): ?>
                            Keterangan harian berhasil disimpan
                        <?php endif; ?>
                    </span>
                </div>
            <?php endif; ?>

            <div class="transactions-section">
                <?php if (empty($transaksi)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">💼</div>
                        <h3>Belum ada catatan kas</h3>
                        <p>Mulai dengan menambahkan transaksi pertama Anda</p>
                    </div>
                <?php else: ?>
                    <div class="transactions-list" id="transactions-list">
                        <?php foreach ($transaksi as $index => $t): ?>
                            <?php if (!isset($t['id']) || !isset($t['nominal']) || !isset($t['keterangan'])) continue; ?>
                            <div class="transaction-item" data-index="<?= $index ?>">
                                <div class="transaction-main">
                                    <div class="transaction-info">
                                        <div class="transaction-meta">
                                            <span class="transaction-time">
                                                <?= date('d M H:i', strtotime($t['waktu'])) ?>
                                            </span>
                                            <span class="transaction-type <?= 
                                                $t['tipe'] === 'Penjualan Tunai' ? 'type-penjualan' : 
                                                ($t['tipe'] === 'Pengeluaran' ? 'type-pengeluaran' :
                                                ($t['nominal'] >= 0 ? 'type-income' : 'type-expense')) 
                                            ?>">
                                                <?= $t['tipe'] ?>
                                            </span>
                                        </div>
                                        <div class="transaction-desc">
                                            <?= htmlspecialchars($t['keterangan']) ?>
                                        </div>
                                    </div>
                                    <div class="transaction-amount <?= $t['nominal'] >= 0 ? 'amount-income' : 'amount-expense' ?>">
                                        <?= $t['nominal'] >= 0 ? '+' : '-' ?> Rp <?= number_format(abs($t['nominal']), 0, ',', '.') ?>
                                    </div>
                                </div>
                                <div class="transaction-actions">
                                    <button type="button" class="btn-action btn-edit" onclick="openEditModal(<?= $index ?>)">
                                        <span>✏️</span>
                                        Edit
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="summary-section">
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="summary-label">Penjualan Tunai</span>
                        <span class="summary-value income">Rp <?= number_format($total_penjualan_tunai, 0, ',', '.') ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Pengeluaran</span>
                        <span class="summary-value expense">Rp <?= number_format($total_pengeluaran_utama, 0, ',', '.') ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Total Masuk Lain</span>
                        <span class="summary-value income">Rp <?= number_format($total_masuk_lain, 0, ',', '.') ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Total Keluar Lain</span>
                        <span class="summary-value expense">Rp <?= number_format($total_keluar_lain, 0, ',', '.') ?></span>
                    </div>
                    <div class="summary-item total">
                        <span class="summary-label">Saldo Akhir</span>
                        <span class="summary-value total-amount">Rp <?= number_format($saldo_akhir, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <div class="button-group">
                    <button type="button" class="btn-primary" onclick="openAddModal()">
                        <span>+</span>
                        Tambah Catatan Kas Lain
                    </button>
                    <button type="button" class="btn-primary main-transaction" onclick="openMainTransactionModal()">
                        <span>💰</span>
                        Keterangan Harian
                    </button>
                </div>
                <a href="/?q=shift__Rekap_Shift__rekap_shift" class="btn-secondary">
                    <span>⬅</span>
                    Kembali ke Rekap Shift
                </a>
            </div>
        </div>
    </div>

    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah Catatan Kas Lain</h3>
                <button type="button" class="btn-close" onclick="closeAddModal()">×</button>
            </div>
            <form method="post" class="modal-form" id="addForm">
                <div class="form-group">
                    <label for="jumlah">Jumlah (Rp)</label>
                    <input type="text" id="jumlah" name="jumlah" required 
                           placeholder="Contoh: 100000 atau 100.000">
                    <small class="form-hint">Bisa menggunakan titik atau tanpa titik</small>
                </div>
                <div class="form-group">
                    <label for="catatan">Keterangan</label>
                    <textarea id="catatan" name="catatan" rows="3" 
                              placeholder="Contoh: belanja kebutuhan kantin..." required></textarea>
                </div>
                <div class="form-group">
                    <label for="aksi">Jenis Aksi</label>
                    <select id="aksi" name="aksi" required>
                        <option value="masuk">Kas Masuk Lain</option>
                        <option value="keluar">Kas Keluar Lain</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <span>💾</span>
                        Simpan Catatan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="mainTransactionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Keterangan Harian</h3>
                <button type="button" class="btn-close" onclick="closeMainTransactionModal()">×</button>
            </div>
            <form method="post" class="modal-form" id="mainTransactionForm">
                <input type="hidden" name="main_transaction" value="1">
                <div class="form-group">
                    <label for="main_jumlah">Jumlah (Rp)</label>
                    <input type="text" id="main_jumlah" name="jumlah" required 
                           placeholder="Contoh: 100000 atau 100.000">
                    <small class="form-hint">Bisa menggunakan titik atau tanpa titik</small>
                </div>
                <div class="form-group">
                    <label for="main_catatan">Keterangan</label>
                    <textarea id="main_catatan" name="catatan" rows="3" 
                              placeholder="Contoh: Penjualan hari ini..." required></textarea>
                </div>
                <div class="form-group">
                    <label for="main_aksi">Jenis Transaksi</label>
                    <select id="main_aksi" name="aksi" required>
                        <option value="penjualan">Penjualan Tunai</option>
                        <option value="pengeluaran">Pengeluaran</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <span>💾</span>
                        Simpan Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Catatan Kas</h3>
                <button type="button" class="btn-close" onclick="closeEditModal()">×</button>
            </div>
            <form method="post" class="modal-form" id="editForm">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="form-group">
                    <label for="edit_jumlah">Jumlah (Rp)</label>
                    <input type="text" id="edit_jumlah" name="jumlah" required>
                    <small class="form-hint">Bisa menggunakan titik atau tanpa titik</small>
                </div>
                <div class="form-group">
                    <label for="edit_catatan">Keterangan</label>
                    <textarea id="edit_catatan" name="catatan" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_aksi">Jenis Transaksi</label>
                    <select id="edit_aksi" name="aksi" required>
                        <option value="penjualan">Penjualan Tunai</option>
                        <option value="pengeluaran">Pengeluaran</option>
                        <option value="masuk">Kas Masuk Lain</option>
                        <option value="keluar">Kas Keluar Lain</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">
                        Batal
                    </button>
                    <button type="submit" class="btn-submit">
                        <span>💾</span>
                        Update Catatan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.transaksiData = <?= json_encode(array_values($transaksi), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
    </script>
</body>
</html>