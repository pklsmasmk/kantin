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

// FUNGSI BARU: Cek struktur tabel penjualan
function get_penjualan_columns($pdo) {
    $stmt = $pdo->query("SHOW COLUMNS FROM penjualan");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $columns;
}

// FUNGSI BARU: Ambil data penjualan dari database dengan menyesuaikan struktur
function get_penjualan_terbaru($pdo)
{
    $tanggal_hari_ini = date('Y-m-d');
    
    // Cek kolom yang ada di tabel penjualan
    $columns = get_penjualan_columns($pdo);
    
    // Buat SELECT berdasarkan kolom yang ada
    $select_columns = [];
    if (in_array('id', $columns)) $select_columns[] = 'id';
    if (in_array('id_penjualan', $columns)) $select_columns[] = 'id_penjualan';
    if (in_array('nama_pembeli', $columns)) $select_columns[] = 'nama_pembeli';
    if (in_array('tanggal', $columns)) $select_columns[] = 'tanggal';
    if (in_array('total', $columns)) $select_columns[] = 'total';
    if (in_array('metode', $columns)) $select_columns[] = 'metode';
    if (in_array('status', $columns)) $select_columns[] = 'status';
    if (in_array('diskon', $columns)) $select_columns[] = 'diskon';
    if (in_array('pajak', $columns)) $select_columns[] = 'pajak';
    if (in_array('uang_masuk', $columns)) $select_columns[] = 'uang_masuk';
    if (in_array('kembalian', $columns)) $select_columns[] = 'kembalian';
    if (in_array('keterangan', $columns)) $select_columns[] = 'keterangan';
    
    if (empty($select_columns)) {
        return [];
    }
    
    // Hanya ambil data yang statusnya "Lunas"
    $sql = "SELECT " . implode(', ', $select_columns) . " 
            FROM penjualan 
            WHERE DATE(tanggal) = ? AND status = 'Lunas'
            ORDER BY tanggal DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tanggal_hari_ini]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// FUNGSI BARU: Sinkronkan data penjualan ke transaksi
function sync_penjualan_to_transaksi($pdo, &$transaksi)
{
    // Ambil data penjualan terbaru (hanya yang Lunas)
    $penjualan_terbaru = get_penjualan_terbaru($pdo);
    
    // Filter transaksi yang bukan Penjualan Tunai (untuk mempertahankan data uang lain)
    $transaksi_lain = array_filter($transaksi, function($t) {
        return isset($t['tipe']) && $t['tipe'] !== 'Penjualan Tunai';
    });
    
    // Konversi penjualan menjadi format transaksi
    $transaksi_penjualan = [];
    foreach ($penjualan_terbaru as $penjualan) {
        // Tentukan ID yang benar
        $id_penjualan = isset($penjualan['id_penjualan']) ? $penjualan['id_penjualan'] : 
                        (isset($penjualan['id']) ? $penjualan['id'] : uniqid());
        
        $nama_pembeli = !empty($penjualan['nama_pembeli']) ? $penjualan['nama_pembeli'] : 'Umum';
        
        $transaksi_penjualan[] = [
            'id' => 'penjualan_' . $id_penjualan,
            'waktu' => $penjualan['tanggal'],
            'tipe' => 'Penjualan Tunai',
            'keterangan' => 'Penjualan kepada ' . $nama_pembeli,
            'nominal' => (int) $penjualan['total'],
            'id_penjualan' => $id_penjualan,
            'nama_pembeli' => $nama_pembeli,
            'status' => $penjualan['status'] ?? 'Lunas',
            'metode' => $penjualan['metode'] ?? 'Tunai',
            'diskon' => $penjualan['diskon'] ?? 0,
            'pajak' => $penjualan['pajak'] ?? 0,
            'uang_masuk' => $penjualan['uang_masuk'] ?? 0,
            'kembalian' => $penjualan['kembalian'] ?? 0
        ];
    }
    
    $transaksi = array_merge($transaksi_penjualan, array_values($transaksi_lain));
    
    usort($transaksi, function($a, $b) {
        $timeA = isset($a['waktu']) ? strtotime($a['waktu']) : 0;
        $timeB = isset($b['waktu']) ? strtotime($b['waktu']) : 0;
        return $timeB - $timeA; 
    });
    
    return $transaksi;
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

$transaksi = sync_penjualan_to_transaksi($pdo, $transaksi);
$_SESSION['transaksi'] = $transaksi;

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

$penjualan_hari_ini = get_penjualan_terbaru($pdo);
$jumlah_penjualan = count($penjualan_hari_ini);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Rekap Kas - UAM</title>
    <style>
        .alert { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .alert-error { background: #fee; border: 1px solid #fcc; color: #c00; }
        .alert-success { background: #efe; border: 1px solid #cfc; color: #0c0; }
        .sync-info { font-size: 12px; color: #666; margin: 5px 0; }
        .summary-note { font-size: 11px; color: #888; display: block; }
        .customer-name { font-weight: bold; color: #333; }
        .transaction-status { 
            display: inline-block; 
            padding: 2px 8px; 
            border-radius: 12px; 
            font-size: 0.8em; 
            margin-left: 8px;
        }
        .status-lunas { background: #e8f5e8; color: #2e7d32; }
        .transaction-details { font-size: 0.8em; color: #666; margin-top: 5px; }
    </style>
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
                <div class="sync-info">
                    <small>Data penjualan otomatis tersinkronisasi | Total transaksi: <?= $jumlah_penjualan ?> penjualan (Lunas)</small>
                </div>
                <?php if ($rekap_data): ?>
                    <div class="sync-info">
                        <small>Terakhir diperbarui: <?= date('H:i', strtotime($rekap_data['last_updated'])) ?></small>
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
                        <h3>Belum ada data keuangan</h3>
                        <p>Mulai dengan melakukan penjualan atau menambahkan transaksi pertama Anda</p>
                    </div>
                <?php else: ?>
                    <div class="transactions-list" id="transactions-list">
                        <?php foreach ($transaksi as $index => $t): ?>
                            <?php if (!isset($t['id']) || !isset($t['nominal']) || !isset($t['keterangan']) || !isset($t['tipe']))
                                continue; ?>
                            <div class="transaction-item" data-index="<?= $index ?>">
                                <div class="transaction-main">
                                    <div class="transaction-info">
                                        <div class="transaction-meta">
                                            <span class="transaction-time">
                                                <?= isset($t['waktu']) ? date('d M H:i', strtotime($t['waktu'])) : 'Waktu tidak tersedia' ?>
                                            </span>
                                            <span class="transaction-type <?=
                                                $t['tipe'] === 'Penjualan Tunai' ? 'type-penjualan' :
                                                ($t['tipe'] === 'Pengeluaran' ? 'type-pengeluaran' :
                                                    ($t['nominal'] >= 0 ? 'type-income' : 'type-expense'))
                                                ?>">
                                                <?= $t['tipe'] ?>
                                            </span>
                                            <?php if ($t['tipe'] === 'Penjualan Tunai' && isset($t['status'])): ?>
                                                <span class="transaction-status status-<?= strtolower($t['status']) ?>">
                                                    <?= $t['status'] ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="transaction-desc">
                                            <?php if ($t['tipe'] === 'Penjualan Tunai' && isset($t['nama_pembeli'])): ?>
                                                <div class="customer-name"><?= htmlspecialchars($t['nama_pembeli']) ?></div>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($t['keterangan']) ?>
                                            
                                            <?php if ($t['tipe'] === 'Penjualan Tunai' && isset($t['id_penjualan'])): ?>
                                                <div class="transaction-details">
                                                    <small>
                                                        ID: <?= $t['id_penjualan'] ?> 
                                                        | <?= $t['metode'] ?? 'Tunai' ?>
                                                        <?php if (isset($t['diskon']) && $t['diskon'] > 0): ?>
                                                            | Diskon: Rp <?= number_format($t['diskon'], 0, ',', '.') ?>
                                                        <?php endif; ?>
                                                        <?php if (isset($t['pajak']) && $t['pajak'] > 0): ?>
                                                            | Pajak: Rp <?= number_format($t['pajak'], 0, ',', '.') ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
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
                        <small class="summary-note"><?= $jumlah_penjualan ?> transaksi (Lunas)</small>
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
                    <button type="button" class="btn-secondary" onclick="refreshData()">
                        <span>↻</span>
                        Refresh Data
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