<?php
date_default_timezone_set('Asia/Jakarta');

$shift_id = $_GET['shift_id'] ?? '';
$saldo_akhir = $_GET['saldo_akhir'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Shift Berhasil Diakhiri - UAM</title>
    <link rel="stylesheet" href="../CSS/akhiri_sukses.css">
</head>
<body>
    <div class="container">
        <div class="success-card">
            <div class="success-icon">🎉</div>
            <h1>Shift Berhasil Diakhiri</h1>
            <p>Shift kasir telah berhasil disimpan ke sistem dan tidak dapat diubah kembali.</p>
            
            <div class="success-info">
                <div class="info-item">
                    <span>ID Shift:</span>
                    <strong><?= htmlspecialchars(substr($shift_id, 0, 8)) ?>...</strong>
                </div>
                <div class="info-item">
                    <span>Saldo Akhir:</span>
                    <strong class="saldo-akhir">Rp <?= number_format($saldo_akhir, 0, ',', '.') ?></strong>
                </div>
                <div class="info-item">
                    <span>Waktu Selesai:</span>
                    <strong><?= date('d M Y H:i:s') ?></strong>
                </div>
            </div>

            <div class="action-buttons">
                <a href="/?q=shift&tab=history" class="btn btn-primary">
                    <span>📊</span>
                    Lihat Riwayat Shift
                </a>
                <a href="../index.php" class="btn btn-secondary">
                    <span>🏠</span>
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</body>
</html>