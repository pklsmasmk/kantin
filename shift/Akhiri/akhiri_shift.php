<?php
session_start();

if (!isset($_SESSION['shift'])) {
    header("Location: ../index.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');

$shift = $_SESSION['shift'];
$transaksi = $_SESSION['transaksi'] ?? [];

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
$selisih = $saldo_akhir - $saldo_awal;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['akhiri_shift'])) {
    $catatan = trim($_POST['catatan'] ?? '');
    
    $riwayat_file = '../shift_data.json';
    $rekap_file = '../Rekap_Shift/rekap_data.json';
    
    $riwayat_data = [];
    if (file_exists($riwayat_file)) {
        $riwayat_data = json_decode(file_get_contents($riwayat_file), true) ?: [];
    }
    
    $rekap_data = [];
    if (file_exists($rekap_file)) {
        $rekap_data = json_decode(file_get_contents($rekap_file), true) ?: [];
    }
    
    $shift_updated = false;
    foreach ($riwayat_data as &$item) {
        if ($item['id'] === $shift['id']) {
            if (($item['status'] ?? 'berjalan') === 'berjalan') {
                $item['saldo_akhir'] = $saldo_akhir;
                $item['selisih'] = $selisih;
                $item['status'] = 'selesai';
                $item['waktu_selesai'] = date('Y-m-d H:i:s');
                
                $item['total_penjualan_tunai'] = $total_penjualan_tunai;
                $item['total_pengeluaran_utama'] = $total_pengeluaran_utama;
                $item['total_masuk_lain'] = $total_masuk_lain;
                $item['total_keluar_lain'] = $total_keluar_lain;
                $item['total_transaksi'] = count($transaksi);
                
                if (!empty($catatan)) {
                    $item['catatan'] = $catatan;
                }
                $shift_updated = true;
            }
            break;
        }
    }
    
    $rekap_updated = false;
    foreach ($rekap_data as &$rekap) {
        if (isset($rekap['shift_id']) && $rekap['shift_id'] === $shift['id']) {
            if (($rekap['status'] ?? 'berjalan') === 'berjalan') {
                $rekap['status'] = 'selesai';
                $rekap['waktu_selesai'] = date('Y-m-d H:i:s');
                $rekap['saldo_akhir'] = $saldo_akhir;
                $rekap['selisih'] = $selisih;
                $rekap['last_updated'] = date('Y-m-d H:i:s');
                
                $rekap['total_penjualan'] = $total_penjualan_tunai;
                $rekap['total_pengeluaran'] = $total_pengeluaran_utama;
                $rekap['total_pemasukan_lain'] = $total_masuk_lain;
                $rekap['total_pengeluaran_lain'] = $total_keluar_lain;
                
                if (!empty($catatan)) {
                    $rekap['catatan'] = $catatan;
                }
                $rekap_updated = true;
            }
            break;
        }
    }
    
    if ($shift_updated) {
        file_put_contents($riwayat_file, json_encode($riwayat_data, JSON_PRETTY_PRINT));
    }
    
    if ($rekap_updated) {
        file_put_contents($rekap_file, json_encode($rekap_data, JSON_PRETTY_PRINT));
    }
    
    unset($_SESSION['shift']);
    unset($_SESSION['transaksi']);
    
    header('Location: akhiri_sukses.php?shift_id=' . $shift['id'] . '&saldo_akhir=' . $saldo_akhir);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Akhiri Shift - UAM</title>
    <link rel="stylesheet" href="../CSS/akhiri_shift.css">
</head>
<body>
    <div class="container">
        <div class="akhiri-card">
            <header class="card-header">
                <h1>Akhiri Shift Kasir</h1>
                <div class="shift-info">
                    <span><?= htmlspecialchars($shift['cashdrawer']) ?></span>
                    <span>•</span>
                    <span>Mulai: <?= date('d M Y H:i', strtotime($shift['waktu'])) ?></span>
                </div>
            </header>

            <div class="summary-section">
                <h2>📊 Ringkasan Keuangan Shift</h2>
                
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="summary-label">Saldo Awal</span>
                        <span class="summary-value awal">Rp <?= number_format($saldo_awal, 0, ',', '.') ?></span>
                    </div>
                    
                    <div class="summary-item">
                        <span class="summary-label">Penjualan Tunai</span>
                        <span class="summary-value income">+ Rp <?= number_format($total_penjualan_tunai, 0, ',', '.') ?></span>
                    </div>
                    
                    <div class="summary-item">
                        <span class="summary-label">Pengeluaran</span>
                        <span class="summary-value expense">- Rp <?= number_format($total_pengeluaran_utama, 0, ',', '.') ?></span>
                    </div>
                    
                    <div class="summary-item">
                        <span class="summary-label">Total Masuk Lain</span>
                        <span class="summary-value income">+ Rp <?= number_format($total_masuk_lain, 0, ',', '.') ?></span>
                    </div>
                    
                    <div class="summary-item">
                        <span class="summary-label">Total Keluar Lain</span>
                        <span class="summary-value expense">- Rp <?= number_format($total_keluar_lain, 0, ',', '.') ?></span>
                    </div>
                    
                    <div class="summary-item total">
                        <span class="summary-label">Saldo Akhir</span>
                        <span class="summary-value total-amount">Rp <?= number_format($saldo_akhir, 0, ',', '.') ?></span>
                    </div>
                </div>

                <div class="selisih-info <?= $selisih >= 0 ? 'selisih-positif' : 'selisih-negatif' ?>">
                    <div class="selisih-content">
                        <span class="selisih-label">Perubahan Saldo:</span>
                        <span class="selisih-value">
                            <?= $selisih >= 0 ? '📈' : '📉' ?>
                            <?= $selisih >= 0 ? '+' : '' ?> Rp <?= number_format(abs($selisih), 0, ',', '.') ?>
                        </span>
                    </div>
                </div>

                <div class="rumus-info">
                    <h4>🧮 Rumus Perhitungan:</h4>
                    <div class="rumus-text">
                        <strong>Saldo Akhir = Saldo Awal + Penjualan Tunai + Masuk Lain - Pengeluaran - Keluar Lain</strong>
                    </div>
                    <div class="rumus-detail">
                        Rp <?= number_format($saldo_awal, 0, ',', '.') ?> + 
                        Rp <?= number_format($total_penjualan_tunai, 0, ',', '.') ?> + 
                        Rp <?= number_format($total_masuk_lain, 0, ',', '.') ?> - 
                        Rp <?= number_format($total_pengeluaran_utama, 0, ',', '.') ?> - 
                        Rp <?= number_format($total_keluar_lain, 0, ',', '.') ?> = 
                        <strong>Rp <?= number_format($saldo_akhir, 0, ',', '.') ?></strong>
                    </div>
                </div>
            </div>

            <div class="transaksi-section">
                <h2>📝 Detail Transaksi</h2>
                
                <?php if (empty($transaksi)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📄</div>
                        <p>Tidak ada transaksi</p>
                        <small>Semua transaksi akan tercatat dalam ringkasan di atas</small>
                    </div>
                <?php else: ?>
                    <div class="transaksi-list">
                        <?php foreach ($transaksi as $index => $t): ?>
                            <?php if (!isset($t['id']) || !isset($t['nominal'])) continue; ?>
                            <div class="transaksi-item">
                                <div class="transaksi-info">
                                    <div class="transaksi-meta">
                                        <span class="transaksi-time"><?= date('H:i', strtotime($t['waktu'])) ?></span>
                                        <span class="transaksi-type <?= 
                                            $t['tipe'] === 'Penjualan Tunai' ? 'type-penjualan' : 
                                            ($t['tipe'] === 'Pengeluaran' ? 'type-pengeluaran' :
                                            ($t['nominal'] >= 0 ? 'type-income' : 'type-expense')) 
                                        ?>">
                                            <?= $t['tipe'] ?>
                                        </span>
                                    </div>
                                    <div class="transaksi-desc">
                                        <?= htmlspecialchars($t['keterangan'] ?? 'Transaksi') ?>
                                    </div>
                                </div>
                                <div class="transaksi-amount <?= $t['nominal'] >= 0 ? 'amount-income' : 'amount-expense' ?>">
                                    <?= $t['nominal'] >= 0 ? '+' : '-' ?> Rp <?= number_format(abs($t['nominal']), 0, ',', '.') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="transaksi-total">
                        <span>Total: <?= count($transaksi) ?> transaksi</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="konfirmasi-section">
                <div class="warning-box">
                    <div class="warning-icon">⚠️</div>
                    <div class="warning-content">
                        <h3>Konfirmasi Akhir Shift</h3>
                        <p>Setelah mengakhiri shift:</p>
                        <ul>
                            <li>✅ Saldo akhir: <strong>Rp <?= number_format($saldo_akhir, 0, ',', '.') ?></strong></li>
                            <li>✅ Data akan tersimpan permanen</li>
                            <li>✅ Saldo akan menjadi warisan untuk shift berikutnya</li>
                            <li>❌ Tidak dapat diubah atau dihapus</li>
                        </ul>
                    </div>
                </div>

                <form method="post" class="konfirmasi-form">
                    <div class="form-group">
                        <label for="catatan">Catatan Akhir Shift (Opsional)</label>
                        <textarea id="catatan" name="catatan" rows="3" 
                                  placeholder="Contoh: Ada transaksi khusus, kejadian penting, atau catatan untuk shift berikutnya..."></textarea>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="../Rekap_Shift/rekap_shift.php" class="btn btn-secondary">
                            <span>←</span>
                            Kembali ke Rekap
                        </a>
                        <button type="submit" name="akhiri_shift" class="btn btn-primary">
                            <span>✅</span>
                            Konfirmasi Akhiri Shift
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!confirm('🚨 KONFIRMASI AKHIRI SHIFT\n\nApakah Anda yakin ingin mengakhiri shift ini?\n\n• Saldo akhir: Rp <?= number_format($saldo_akhir, 0, ',', '.') ?>\n• Data akan tersimpan permanen\n• Tidak dapat diubah kembali')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>