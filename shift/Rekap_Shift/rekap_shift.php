<?php

error_log("=== DEBUG REKAP_SHIFT.PHP ===");
error_log("Session shift: " . print_r($_SESSION['shift'] ?? 'Tidak ada session shift', true));
error_log("Session shift_current: " . print_r($_SESSION['shift_current'] ?? 'Tidak ada session current', true));
error_log("All session: " . print_r($_SESSION, true));

if (!isset($_SESSION['shift'])) {
    error_log("Tidak ada session shift, coba gunakan shift_current");
    
    if (isset($_SESSION['shift_current'])) {
        $_SESSION['shift'] = $_SESSION['shift_current'];
        error_log("Berhasil menggunakan shift_current sebagai session shift");
    } else {
        error_log("Tidak ada shift current, redirect ke index.php");
        echo "<script>
            alert('Tidak ada shift yang aktif. Silakan mulai shift terlebih dahulu.');
            window.location.href = '../index.php';
        </script>";
        exit;
    }
}

date_default_timezone_set('Asia/Jakarta');

$shift = $_SESSION['shift'];
$transaksi = $_SESSION['transaksi'] ?? [];

error_log("Shift data yang digunakan: " . print_r($shift, true));
error_log("Jumlah transaksi: " . count($transaksi));

$total_penjualan_tunai = 0;
$total_pengeluaran_utama = 0;
$total_pemasukan_lain = 0;
$total_pengeluaran_lain = 0;

foreach ($transaksi as $t) {
    if (isset($t['nominal'])) {
        if ($t['tipe'] === 'Penjualan Tunai') {
            $total_penjualan_tunai += $t['nominal'];
        } elseif ($t['tipe'] === 'Pengeluaran') {
            $total_pengeluaran_utama += abs($t['nominal']);
        } elseif ($t['nominal'] >= 0) {
            $total_pemasukan_lain += $t['nominal'];
        } else {
            $total_pengeluaran_lain += abs($t['nominal']);
        }
    }
}

function get_or_create_rekap($pdo, $shift_data, $transaksi_data) {
    $stmt = $pdo->prepare("SELECT * FROM rekap_shift WHERE shift_id = ?");
    $stmt->execute([$shift_data['id']]);
    $current_rekap = $stmt->fetch();
    
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
            } elseif ($t['nominal'] >= 0) {
                $total_pemasukan_lain += $t['nominal'];
            } else {
                $total_pengeluaran_lain += abs($t['nominal']);
            }
        }
    }
    
    $saldo_akhir = $shift_data['saldo_awal'] + $total_penjualan_tunai + $total_pemasukan_lain - $total_pengeluaran_utama - $total_pengeluaran_lain;
    $selisih = $saldo_akhir - $shift_data['saldo_awal'];
    
    if (!$current_rekap) {
        $sql = "INSERT INTO rekap_shift 
                (shift_id, cashdrawer, saldo_awal, saldo_akhir, total_penjualan, 
                 total_pengeluaran, total_pemasukan_lain, total_pengeluaran_lain, 
                 selisih, waktu_mulai, waktu_selesai, kasir, role, last_updated) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $shift_data['id'],
            $shift_data['cashdrawer'],
            $shift_data['saldo_awal'],
            $saldo_akhir,
            $total_penjualan_tunai,
            $total_pengeluaran_utama,
            $total_pemasukan_lain,
            $total_pengeluaran_lain,
            $selisih,
            $shift_data['waktu_mulai'],
            $shift_data['waktu_selesai'] ?? null,
            $shift_data['nama'],
            $shift_data['role']
        ]);
        
        $stmt = $pdo->prepare("SELECT * FROM rekap_shift WHERE shift_id = ?");
        $stmt->execute([$shift_data['id']]);
        return $stmt->fetch();
    } else {
        $sql = "UPDATE rekap_shift SET 
                total_penjualan = ?, total_pengeluaran = ?, 
                total_pemasukan_lain = ?, total_pengeluaran_lain = ?,
                saldo_akhir = ?, selisih = ?, last_updated = NOW()
                WHERE shift_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $total_penjualan_tunai,
            $total_pengeluaran_utama,
            $total_pemasukan_lain,
            $total_pengeluaran_lain,
            $saldo_akhir,
            $selisih,
            $shift_data['id']
        ]);
        
        $stmt = $pdo->prepare("SELECT * FROM rekap_shift WHERE shift_id = ?");
        $stmt->execute([$shift_data['id']]);
        return $stmt->fetch();
    }
}

$current_rekap = get_or_create_rekap($pdo, $shift, $transaksi);

$total_penjualan = $current_rekap['total_penjualan'] ?? 0;
$total_pengeluaran = $current_rekap['total_pengeluaran'] ?? 0;
$total_pemasukan_lain = $current_rekap['total_pemasukan_lain'] ?? 0;
$total_pengeluaran_lain = $current_rekap['total_pengeluaran_lain'] ?? 0;
$saldo_awal = $current_rekap['saldo_awal'] ?? 0;
$saldo_akhir = $current_rekap['saldo_akhir'] ?? $saldo_awal;
$selisih = $current_rekap['selisih'] ?? 0;

$subtotal = $total_penjualan + $total_pemasukan_lain - $total_pengeluaran - $total_pengeluaran_lain;
$penerimaan_sistem = $saldo_awal + $subtotal;

$count_pemasukan_lain = count(array_filter($transaksi, fn($t) => isset($t['nominal']) && $t['nominal'] >= 0 && $t['tipe'] !== 'Penjualan Tunai'));
$count_pengeluaran_lain = count(array_filter($transaksi, fn($t) => isset($t['nominal']) && $t['nominal'] < 0 && $t['tipe'] !== 'Pengeluaran'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Shift</title>
</head>
<body>
    <div class="container">
        <header class="header">
            <h2>Rekap Shift Berjalan</h2>
            <div class="sync-status">
                <div class="sync-ok">✅ Data tersinkronisasi dengan Database</div>
                <?php if (count($transaksi) > 0): ?>
                    <div class="transaksi-info">
                        📊 <?= count($transaksi) ?> catatan kas terdaftar
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <div class="card">
            <h1>Shift - <?= htmlspecialchars($current_rekap['cashdrawer']) ?></h1>

            <div class="info-section">
                <div class="row">
                    <span>Nama Staff</span>
                    <span><?= htmlspecialchars($current_rekap['kasir']) ?></span>
                </div>
                <div class="row">
                    <span>Sebagai</span>
                    <span><?= htmlspecialchars($current_rekap['role']) ?></span>
                </div>
                <div class="row">
                    <span>Mulai Shift</span>
                    <span><?= date('d M Y H:i', strtotime($current_rekap['waktu_mulai'])) ?></span>
                </div>
                <div class="row">
                    <span>Status</span>
                    <span class="status-berjalan">
                        BERJALAN
                    </span>
                </div>
            </div>

            <hr>

            <div class="transaction-section">
                <div class="row">
                    <span>Penjualan Tunai</span>
                    <span class="income">Rp <?= number_format($total_penjualan, 0, ',', '.') ?></span>
                </div>
                
                <?php if ($total_pemasukan_lain > 0): ?>
                <div class="row">
                    <span>Pemasukan Lain</span>
                    <span class="income">Rp <?= number_format($total_pemasukan_lain, 0, ',', '.') ?></span>
                    <?php if ($count_pemasukan_lain > 0): ?>
                        <small style="color: #666; font-size: 12px;">(<?= $count_pemasukan_lain ?> transaksi)</small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($total_pengeluaran > 0): ?>
                <div class="row">
                    <span>Pengeluaran</span>
                    <span class="expense">Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($total_pengeluaran_lain > 0): ?>
                <div class="row">
                    <span>Pengeluaran Lain</span>
                    <span class="expense">Rp <?= number_format($total_pengeluaran_lain, 0, ',', '.') ?></span>
                    <?php if ($count_pengeluaran_lain > 0): ?>
                        <small style="color: #666; font-size: 12px;">(<?= $count_pengeluaran_lain ?> transaksi)</small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="row subtotal">
                    <strong>Subtotal</strong>
                    <strong class="<?= $subtotal >= 0 ? 'income' : 'expense' ?>">
                        Rp <?= number_format($subtotal, 0, ',', '.') ?>
                    </strong>
                </div>

                <div class="row">
                    <span>Kas Awal</span>
                    <span class="cash-awal">Rp <?= number_format($saldo_awal, 0, ',', '.') ?></span>
                </div>

                <div class="highlight penerimaan-sistem">
                    Penerimaan Sistem: <strong>Rp <?= number_format($penerimaan_sistem, 0, ',', '.') ?></strong>
                </div>

                <div class="summary">
                    <div class="summary-item">
                        <small>Saldo Akhir:</small>
                        <strong>Rp <?= number_format($saldo_akhir, 0, ',', '.') ?></strong>
                    </div>
                    <div class="summary-item">
                        <small>Selisih:</small>
                        <strong class="<?= $selisih >= 0 ? 'income' : 'expense' ?>">
                            <?= $selisih >= 0 ? '+' : '' ?>Rp <?= number_format(abs($selisih), 0, ',', '.') ?>
                        </strong>
                    </div>
                </div>
            </div>

            <div class="btns">
                <a href="/?q=shift__Rekap_Shift__rekap_detail" class="btn btn-green">
                    REKAP DETAIL 
                    <?php if (count($transaksi) > 0): ?>
                        <span class="badge"><?= count($transaksi) ?></span>
                    <?php endif; ?>
                </a>
                <a href="/?q=shift__Akhiri__akhiri_shift" class="btn btn-red">AKHIRI SHIFT</a>
                <a href="/?q=shift" class="btn btn-secondary">KEMBALI</a>
            </div>

            <div class="last-updated">
                <small>Terakhir diperbarui: <?= date('d M Y H:i', strtotime($current_rekap['last_updated'])) ?></small>
                <?php if (count($transaksi) > 0): ?>
                    <br><small>📝 <?= count($transaksi) ?> catatan kas terdaftar</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../JS/rekap.js"></script>
</body>
</html>