<?php
function safe_redirect($url)
{
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

function validateInput($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateAmount($amount)
{
    $amount_clean = preg_replace("/[^\d]/", "", $amount);
    return is_numeric($amount_clean) && $amount_clean > 0 ? (int) $amount_clean : false;
}

function sync_rekap_to_database($pdo, $shift_data, $transaksi_data)
{
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
    if (isset($_POST['uang_lain'])) {
        $jenis_transaksi = $_POST['jenis_transaksi'] ?? '';
        $jumlah_input = $_POST['jumlah'] ?? '';
        $catatan = validateInput($_POST['catatan'] ?? '');

        $jumlah = validateAmount($jumlah_input);

        if ($jumlah !== false && !empty($catatan) && in_array($jenis_transaksi, ['masuk', 'keluar'])) {
            $tipe = $jenis_transaksi === 'masuk' ? 'Masuk Lain' : 'Keluar Lain';
            $nominal = $jenis_transaksi === 'masuk' ? $jumlah : -$jumlah;

            $transaksi[] = [
                'id' => uniqid($jenis_transaksi . '_', true),
                'waktu' => date('Y-m-d H:i:s'),
                'tipe' => $tipe,
                'keterangan' => $catatan,
                'nominal' => $nominal,
            ];
            $_SESSION['transaksi'] = $transaksi;

            sync_rekap_to_database($pdo, $shift, $transaksi);

            $success_message = $jenis_transaksi === 'masuk' ? 'added_masuk' : 'added_keluar';
            safe_redirect($_SERVER['PHP_SELF'] . '?success=' . $success_message);
        } else {
            $error = "Data tidak valid. Pastikan jumlah angka positif, jenis transaksi dipilih, dan keterangan diisi.";
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
                        <small>Data tersinkronisasi: <?= date('H:i', strtotime($rekap_data['last_updated'])) ?></small>
                    </div>
                <?php endif; ?>
            </header>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span>
                        <?php if ($success === 'added_masuk'): ?>
                            Uang masuk lain berhasil ditambahkan
                        <?php elseif ($success === 'added_keluar'): ?>
                            Uang keluar lain berhasil ditambahkan
                        <?php endif; ?>
                    </span>
                </div>
            <?php endif; ?>

            <div class="transactions-section">
                <?php if (empty($transaksi)): ?>
                    <div class="empty-state">
                        <h3>Belum ada data keuangan anda</h3>
                        <p>Mulai dengan menambahkan transaksi pertama Anda</p>
                    </div>
                <?php else: ?>
                    <div class="transactions-list" id="transactions-list">
                        <?php foreach ($transaksi as $index => $t): ?>
                            <?php if (!isset($t['id']) || !isset($t['nominal']) || !isset($t['keterangan']))
                                continue; ?>
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
                                    <div
                                        class="transaction-amount <?= $t['nominal'] >= 0 ? 'amount-income' : 'amount-expense' ?>">
                                        <?= $t['nominal'] >= 0 ? '+' : '-' ?> Rp
                                        <?= number_format(abs($t['nominal']), 0, ',', '.') ?>
                                    </div>
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
                        <span class="summary-value income">Rp
                            <?= number_format($total_penjualan_tunai, 0, ',', '.') ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Pengeluaran</span>
                        <span class="summary-value expense">Rp
                            <?= number_format($total_pengeluaran_utama, 0, ',', '.') ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Total Masuk Lain</span>
                        <span class="summary-value income">Rp
                            <?= number_format($total_masuk_lain, 0, ',', '.') ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Total Keluar Lain</span>
                        <span class="summary-value expense">Rp
                            <?= number_format($total_keluar_lain, 0, ',', '.') ?></span>
                    </div>
                    <div class="summary-item total">
                        <span class="summary-label">Saldo Akhir</span>
                        <span class="summary-value total-amount">Rp
                            <?= number_format($saldo_akhir, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <div class="button-group">
                    <button type="button" class="btn-primary" onclick="openAddModal()">
                        <span>+</span>
                        Uang Lainnya
                    </button>
                </div>
                <a href="/?q=shift__Rekap_Shift__rekap_shift" class="btn-secondary">
                    <span>←</span>
                    Kembali ke Rekap Shift
                </a>
            </div>
        </div>
    </div>

    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah Uang Lainnya</h3>
                <button type="button" class="btn-close" onclick="closeAddModal()">×</button>
            </div>
            <form method="post" class="modal-form" id="addForm">
                <input type="hidden" name="uang_lain" value="1">

                <div class="form-group">
                    <label>Jenis Transaksi</label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="jenis_transaksi" value="masuk" checked>
                            <span class="type-indicator type-masuk">Uang Masuk</span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="jenis_transaksi" value="keluar">
                            <span class="type-indicator type-keluar">Uang Keluar</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="jumlah">Jumlah (Rp)</label>
                    <input type="text" id="jumlah" name="jumlah" required placeholder="Contoh: 100000 atau 100.000">
                    <small class="form-hint">Bisa menggunakan titik atau tanpa titik</small>
                </div>
                <div class="form-group">
                    <label for="catatan">Keterangan</label>
                    <textarea id="catatan" name="catatan" rows="3"
                        placeholder="Contoh: penerimaan dari... / pembayaran untuk..." required></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit" id="submitButton">
                        Simpan Uang Masuk
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