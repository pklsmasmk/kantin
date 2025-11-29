<?php
ob_start(); 
date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("PHP/Back.php");
include("PHP/Back_shift.php");

if (!isUserLoggedIn()) {
    ob_end_clean();
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Akses Ditolak - Shift Kasir UAM</title>
    <link rel="stylesheet" href="../CSS/awal_shift.css" />
</head>
<body>
    <div class="login-required-container">
        <div class="uam-logo">
            <svg class="logo-svg" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                <rect width="40" height="40" rx="8" ry="8" fill="url(#greenGradient)" />
                <text x="20" y="25" text-anchor="middle" fill="white" font-family="Arial" font-size="16" font-weight="bold">UAM</text>
                <defs>
                    <linearGradient id="greenGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#2E8B57" />
                        <stop offset="100%" stop-color="#1F6B45" />
                    </linearGradient>
                </defs>
            </svg>
            <span class="logo-text">Shift Kasir</span>
        </div>
        
        <div class="uam-icon">
            <img src="https://maukuliah.ap-south-1.linodeobjects.com/logo/1714374136-CkCJsaBvSM.jpg" alt="Universitas Anwar Medika">
        </div>
        <h1>Akses Ditolak</h1>
        <p>Anda perlu login untuk melanjutkan.</p>
        
        <a href="?q=login" class="login-btn">Login Sekarang</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="../JS/awal_shift.js"></script>
</body>
</html>
    <?php
    exit;
}

process_shift_requests($pdo);

$display_data = get_shift_display_data($pdo);

extract($display_data);

$active_tab = isset($_GET['tab']) && in_array($_GET['tab'], ['current', 'setoran', 'history']) ? $_GET['tab'] : 'current';
$error = $_SESSION["error"] ?? null;
$success = $_SESSION["success"] ?? null;

unset($_SESSION["error"], $_SESSION["success"]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Shift Kasir - UAM</title>
</head>
<body>
    <div class="container" role="main" aria-label="Halaman Shift Kasir">
        <header>
            <div class="logo" aria-label="UAM Logo">
                <svg width="28" height="28" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <rect width="28" height="28" rx="8" ry="8" fill="var(--primary-color)" />
                </svg>
                <h1>Shift Kasir Kantin</h1>
            </div>
        </header>

        <main>
            <h2>Kasir Kantin Universitas Anwar Medika</h2>

            <nav class="tabs" role="tablist" aria-label="Navigasi Shift">
                <button role="tab" aria-selected="<?= $active_tab === 'current' ? 'true' : 'false' ?>" 
                        class="<?= $active_tab === 'current' ? 'active' : '' ?>" 
                        tabindex="0" data-tab="current">Cashdrawer</button>
                <button role="tab" aria-selected="<?= $active_tab === 'setoran' ? 'true' : 'false' ?>" 
                        class="<?= $active_tab === 'setoran' ? 'active' : '' ?>" 
                        tabindex="-1" data-tab="setoran">Setoran</button>
                <button role="tab" aria-selected="<?= $active_tab === 'history' ? 'true' : 'false' ?>" 
                        class="<?= $active_tab === 'history' ? 'active' : '' ?>" 
                        tabindex="-1" data-tab="history">Riwayat</button>
            </nav>

            <section class="user-info">
                <div class="avatar">
                    <span>
                        <?php 
                        $nama = $_SESSION['namalengkap'];
                        $inisial = substr($nama, 0, 2);
                        echo $inisial;
                        ?>
                    </span>
                </div>
                <div class="user-name-role">
                    <strong><?=$_SESSION['namalengkap']?></strong>
                    <span><?=$_SESSION['nama']?></span>
                </div>
            </section>

            <?php if (!empty($error)): ?>
                <div class="alert error" role="status"><?= htmlspecialchars($error) ?></div>
            <?php elseif (!empty($success)): ?>
                <div class="alert success" role="status"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <section class="tab-panel current-panel <?= $active_tab === 'current' ? 'is-active' : '' ?>" data-tab-panel="current">
                <section class="info">
                    <?php if ($is_shift_pertama): ?>
                            <strong>SHIFT PERTAMA</strong><br>
                            Ini adalah shift pertama sistem. Silakan pilih cashdrawer dan input saldo awal modal.
                    <?php else: ?>
                            <strong>SHIFT BERJALAN</strong><br>
                            Jika belum memulai Shift sudah bisa akses Rekap Shift harap login ulang!<br>
                            Saldo awal otomatis dari saldo akhir shift sebelumnya: <strong><?= format_rupiah($history[0]["saldo_akhir"]) ?></strong>
                    <?php endif; ?>
                </section>
                
                <form method="POST" class="shift-form" novalidate>
                    <?php if ($is_shift_pertama): ?>
                        <label for="cashdrawer">Pilih Cashdrawer *</label>
                        <div class="refresh-wrapper">
                            <select id="cashdrawer" name="cashdrawer" required>
                                <option value="">-- Pilih Cashdrawer --</option>
                                <?php foreach ($cashdrawers as $option): ?>
                                    <option value="<?= htmlspecialchars($option) ?>">
                                        <?= htmlspecialchars($option) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="refresh-btn" title="Refresh cashdrawer" id="refreshCashdrawer">&#x21bb;</button>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="cashdrawer" value="Cashdrawer-Otomatis">
                        <div class="auto-cashdrawer-notice">
                            <strong>Cashdrawer Otomatis</strong><br>
                            Selalu pastikan untuk memulai shift terlebih dahulu!
                        </div>
                    <?php endif; ?>

                    <label for="saldo_awal">
                        Masukkan Saldo Awal 
                        <?php if (!$is_shift_pertama): ?>
                            <small>(Rekomendasi: <?= format_rupiah($history[0]["saldo_akhir"]) ?>)</small>
                        <?php endif; ?>
                    </label>
                    <div class="input-rp">
                        <span class="rp-label">Rp</span>
                        <input
                            type="text"
                            id="saldo_awal"
                            name="saldo_awal"
                            placeholder="<?= $is_shift_pertama ? '0' : number_format($saldo_awal_rekomendasi, 0, ',', '.') ?>"
                            autocomplete="off"
                            inputmode="numeric"
                            pattern="[0-9.,]*"
                            required
                            value="<?= !$is_shift_pertama && $saldo_awal_rekomendasi > 0 ? number_format($saldo_awal_rekomendasi, 0, ',', '.') : '' ?>"
                        />
                    </div>
                    
                    <?php if (!$is_shift_pertama && $saldo_awal_rekomendasi > 0): ?>
                        <div class="saldo-rekomendasi">
                            <strong>Rekomendasi:</strong> Gunakan saldo dari shift sebelumnya: <?= format_rupiah($saldo_awal_rekomendasi) ?>
                        </div>
                    <?php endif; ?>
                    
                    <small id="lastData" class="last-data">
                        <?php if (!empty($history)): ?>
                            Data terakhir: <?= htmlspecialchars($history[0]["cashdrawer"]) ?> •
                            Saldo awal: <?= format_rupiah($history[0]["saldo_awal"]) ?> • Saldo akhir: <?= format_rupiah($history[0]["saldo_akhir"]) ?> •
                            <?= format_datetime($history[0]["waktu_mulai"]) ?>
                        <?php else: ?>
                            Silahkan isi field diatas untuk memulai shift Anda
                        <?php endif; ?>
                    </small>
                    
                    <button type="submit" class="submit-btn" id="submitShiftBtn">
                        <?php if ($is_shift_pertama): ?>
                            Mulai Shift Pertama
                        <?php else: ?>
                            Mulai Shift Baru (Saldo: <?= format_rupiah($history[0]["saldo_akhir"]) ?>)
                        <?php endif; ?>
                    </button>

                    <div class="action-buttons">
                        <a href="/?q=shift__Rekap_Shift__rekap_shift" 
                        class="action-btn manage-cash-btn <?= !$currentShift ? 'disabled' : '' ?>" 
                        <?= !$currentShift ? 'onclick="return false;" style="opacity: 0.6; pointer-events: none;"' : '' ?>>
                            <span>Rekap Shift</span>
                        </a>
                        <a href="../index.php" class="action-btn cart-btn">
                            <span>Pergi ke Menu Utama</span>
                        </a>
                    </div>
                </form>
            </section>

            <div id="confirmationModal" class="selimut-modal">
                <div class="konten-modal-konfirmasi">
                    <div class="header-modal-konfirmasi">
                        <div class="judul-modal">Konfirmasi Mulai Shift</div>
                        <p>Apakah Anda yakin ingin memulai shift dengan data berikut?</p>
                    </div>
                    
                    <div class="body-modal-konfirmasi">
                        <div class="detail-konfirmasi">
                            <div class="item-detail">
                                <span class="label-detail">Cashdrawer:</span>
                                <span class="nilai-detail" id="modalCashdrawer"></span>
                            </div>
                            <div class="item-detail">
                                <span class="label-detail">Saldo Awal:</span>
                                <span class="nilai-detail" id="modalSaldoAwal"></span>
                            </div>
                            <?php if (!$is_shift_pertama): ?>
                            <div class="item-detail">
                                <span class="label-detail">Sumber Saldo:</span>
                                <span class="nilai-detail">Saldo akhir shift sebelumnya</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="catatan-warisan">
                            <small><strong>PERHATIAN:</strong> Setelah shift dimulai, data akan tersimpan di sistem dan masuk ke riwayat shift.</small>
                        </div>
                    </div>
                    
                    <div class="footer-modal-konfirmasi">
                        <button type="button" class="tombol tombol-batal" id="cancelBtn">Tidak, Kembali</button>
                        <button type="button" class="tombol tombol-konfirmasi" id="confirmBtn">Ya, Mulai Shift</button>
                    </div>
                </div>
            </div>
            
            <form id="confirmedForm" method="POST" class="hidden-form">
                <input type="hidden" name="cashdrawer" id="confirmedCashdrawer">
                <input type="hidden" name="saldo_awal" id="confirmedSaldoAwal">
                <input type="hidden" name="confirmed" value="true">
            </form>


            <section class="tab-panel setoran-panel <?= $active_tab === 'setoran' ? 'is-active' : '' ?>" data-tab-panel="setoran" aria-label="Manajemen setoran kas">
                <div class="setoran-header">
                    <h3>Manajemen Setoran Keuangan</h3>
                    <p>Sistem setoran terintegrasi dengan saldo akhir</p>
                </div>

                <div class="shift-status-info">
                    <?php if ($currentShift): ?>
                        <div class="status-active">
                            <span class="status-badge">🟢 Shift Aktif</span>
                            <small>
                                <?= htmlspecialchars($currentShift['cashdrawer']) ?> • 
                                Saldo awal: <?= format_rupiah($currentShift['saldo_awal']) ?> • 
                                Saldo akhir: <?= format_rupiah($currentShift['saldo_akhir']) ?> •
                                Saldo tersedia untuk setor: <?= format_rupiah($saldo_tersedia) ?>
                            </small>
                        </div>
                    <?php else: ?>
                        <div class="status-inactive">
                            <span class="status-badge"><?= $saldo_akhir_riwayat > 0 ? '🟡 Saldo Tersedia' : '🔴 Tidak Ada Saldo' ?></span>
                            <small>
                                <?php if ($saldo_akhir_riwayat > 0): ?>
                                    Saldo akhir dari riwayat: <?= format_rupiah($saldo_akhir_riwayat) ?> • 
                                    Saldo tersedia untuk setor: <?= format_rupiah($saldo_tersedia) ?> •
                                    <?= $can_setor ? 'Dapat melakukan setoran' : 'Tidak dapat setoran' ?>
                                <?php else: ?>
                                    Tidak ada saldo silahkan mulai shift terlebih dahulu
                                <?php endif; ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="setoran-form-section">
                    <form method="POST" class="setoran-form" novalidate enctype="multipart/form-data">
                        <input type="hidden" name="setoran_action" value="add">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="penyetor">Nama Penyetor *</label>
                                <strong><?=$_SESSION['namalengkap']?></strong>
                            </div>
                            
                            <div class="form-group">
                                <label for="jumlah_setoran">Jumlah Setoran (Rp) *</label>
                                <div class="input-rp">
                                    <input type="text" id="jumlah_setoran" name="jumlah_setoran" required 
                                        placeholder="0" inputmode="numeric" <?= !$can_setor ? 'disabled' : '' ?>>
                                </div>
                                <?php if ($can_setor): ?>
                                    <small class="saldo-tersedia">Saldo tersedia untuk disetor: <?= format_rupiah($saldo_tersedia) ?></small>
                                    <small class="saldo-minimum">Minimal sisa setelah setor: Rp 100.000</small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="jenis_setoran">Jenis Setoran *</label>
                                <select id="jenis_setoran" name="jenis_setoran" required <?= !$can_setor ? 'disabled' : '' ?>>
                                    <option value="">-- Pilih Jenis Setoran --</option>
                                    <option value="kantor_pusat">Setoran ke Pusat</option>
                                    <option value="lainnya">Lainnya</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="metode_setoran">Metode Setoran *</label>
                                <select id="metode_setoran" name="metode_setoran" required <?= !$can_setor ? 'disabled' : '' ?>>
                                    <option value="">-- Pilih Metode --</option>
                                    <option value="tunai">Tunai</option>
                                    <option value="transfer">Transfer Bank</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="keterangan_setoran">Keterangan Setoran *</label>
                            <textarea id="keterangan_setoran" name="keterangan_setoran" rows="3" 
                                    placeholder="Contoh: Setoran penjualan harian tanggal..." required <?= !$can_setor ? 'disabled' : '' ?>></textarea>
                        </div>

                        <div class="form-group" id="detail_lainnya_group" style="display: none;">
                            <label for="detail_lainnya">Detail Tambahan</label>
                            <input type="text" id="detail_lainnya" name="detail_lainnya" 
                                placeholder="Masukkan detail tambahan..." <?= !$can_setor ? 'disabled' : '' ?>>
                        </div>

                        <div class="form-group" id="bukti_transfer_group" style="display: none;">
                            <label for="bukti_transfer">Upload Bukti Transfer *</label>
                            <div class="file-input-wrapper">
                                <input type="file" id="bukti_transfer" name="bukti_transfer" 
                                    accept="image/*,.pdf,.doc,.docx" <?= !$can_setor ? 'disabled' : '' ?>>
                                <label for="bukti_transfer" class="file-input-label" id="fileInputLabel">
                                    Klik untuk upload bukti transfer
                                    <div class="file-name" id="fileName"></div>
                                </label>
                            </div>
                            <small>Format: JPG, PNG, PDF, DOC (Maks. 5MB)</small>
                        </div>

                        <button type="submit" class="submit-btn setoran-submit" <?= !$can_setor ? 'disabled' : '' ?>>
                            <?php if ($can_setor): ?>
                                Simpan Setoran (Sisa min. Rp 100.000)
                            <?php else: ?>
                                <?php if ($saldo_tersedia <= 100000): ?>
                                Saldo tidak cukup untuk setor (Min. sisa Rp 100.000)
                                <?php else: ?>
                                Tidak Dapat Setoran
                                <?php endif; ?>
                            <?php endif; ?>
                        </button>
                        
                        <?php if (!$can_setor && $saldo_akhir_riwayat > 0): ?>
                            <div class="isi-ulang-info">
                                <small><strong>Tidak ada saldo tersisa untuk disetor. </strong> Silakan mulai shift baru untuk menambah saldo.</small>
                            </div>
                        <?php elseif (!$can_setor): ?>
                            <div class="warning-message">
                                <small>Saldo tidak mencukupi untuk setor (minimal Rp 100.000+). Saldo tersedia: <?= format_rupiah($saldo_tersedia) ?></small>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="saldo-summary">
                    <h4>Ringkasan Saldo untuk Setoran</h4>
                    <div class="saldo-cards">
                        <div class="saldo-card">
                            <div class="saldo-info">
                                <span class="saldo-label">Saldo Awal</span>
                                <strong class="saldo-value"><?= format_rupiah($saldo_awal_hari_ini) ?></strong>
                            </div>
                        </div>
                        <div class="saldo-card">
                            <div class="saldo-info">
                                <span class="saldo-label">Total Setoran</span>
                                <strong class="saldo-value"><?= format_rupiah($total_setoran_hari_ini) ?></strong>
                                <small><?= count($setoran_hari_ini) ?> transaksi</small>
                            </div>
                        </div>
                        <div class="saldo-card highlight">
                            <div class="saldo-info">
                                <span class="saldo-label">Saldo Akhir</span>
                                <strong class="saldo-value"><?= format_rupiah($saldo_akhir_display) ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="warisan-info">
                        <div class="info-item">
                            <span class="info-label">Status Setoran:</span>
                            <span class="info-value">
                                <?php if ($saldo_tersedia > 100000): ?>
                                    <span style="color: #28a745;">● Bisa setor (<?= format_rupiah($saldo_tersedia - 100000) ?> max)</span>
                                <?php elseif ($saldo_tersedia > 0): ?>
                                    <span style="color: #ffc107;">● Tidak bisa setor (minimal sisa Rp 100.000)</span>
                                <?php else: ?>
                                    <span style="color: #dc3545;">● Saldo habis, mulai shift baru</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="setoran-hari-ini">
                    <h4>Setoran Hari Ini (<?= date('d M Y') ?>)</h4>
                    <?php if (empty($setoran_hari_ini)): ?>
                        <div class="empty-state">
                            <strong>Belum ada setoran hari ini</strong>
                            <p>Mulai dengan menambahkan setoran pertama dari saldo akhir</p>
                        </div>
                    <?php else: ?>
                        <div class="setoran-summary">
                            <div class="summary-item">
                                <small>Total Setoran:</small>
                                <strong><?= format_rupiah($total_setoran_hari_ini) ?></strong>
                            </div>
                            <div class="summary-item">
                                <small>Jumlah Setoran:</small>
                                <strong><?= count($setoran_hari_ini) ?></strong>
                            </div>
                            <div class="summary-item">
                                <small>Saldo Tersisa:</small>
                                <strong><?= format_rupiah($saldo_tersedia) ?></strong>
                            </div>
                        </div>

                        <div class="setoran-list">
                            <?php foreach ($setoran_hari_ini as $index => $setoran): ?>
                                <div class="setoran-item">
                                    <div class="setoran-info">
                                        <div class="setoran-header-info">
                                            <strong><?= htmlspecialchars($setoran['penyetor']) ?></strong>
                                            <span class="setoran-amount"><?= format_rupiah($setoran['jumlah']) ?></span>
                                        </div>
                                        <div class="setoran-details">
                                            <span class="setoran-type"><?= $setoran['jenis_display'] ?></span>
                                            <span class="setoran-method">• <?= $setoran['metode_display'] ?></span>
                                            <span class="setoran-time">• <?= date('H:i', strtotime($setoran['waktu'])) ?></span>
                                        </div>
                                        <div class="setoran-desc">
                                            <?= htmlspecialchars($setoran['keterangan']) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="setoran-statistics">
                    <h4>Statistik Setoran</h4>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-info">
                                <span class="stat-label">Total Setoran Bulan Ini</span>
                                <strong class="stat-value"><?= format_rupiah($total_setoran_bulan_ini) ?></strong>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-info">
                                <span class="stat-label">Rata-rata Harian</span>
                                <strong class="stat-value"><?= format_rupiah($rata_rata_setoran) ?></strong>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-info">
                                <span class="stat-label">Setoran Hari Ini</span>
                                <strong class="stat-value"><?= count($setoran_hari_ini) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
             
            <section class="tab-panel history-panel <?= $active_tab === 'history' ? 'is-active' : '' ?>" data-tab-panel="history" aria-label="Riwayat shift">
                <div class="search-date">
                    <form method="GET" class="date-search-form">
                        <input type="hidden" name="tab" value="history">
                        <div class="search-input-group">
                            <label for="search_date">Cari Berdasarkan Tanggal:</label>
                            <div class="date-input-wrapper">
                                <input 
                                    type="date" 
                                    id="search_date" 
                                    name="search_date" 
                                    value="<?= isset($_GET['search_date']) ? htmlspecialchars($_GET['search_date']) : date('Y-m-d') ?>"
                                >
                                <button type="submit" class="search-btn">Cari</button>
                                <?php if (isset($_GET['search_date']) && $_GET['search_date'] !== date('Y-m-d')): ?>
                                    <a href="<?= $_SERVER['PHP_SELF'] ?>?tab=history" class="clear-btn">Tampilkan Hari Ini</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <?php
                $searchDate = isset($_GET['search_date']) && !empty($_GET['search_date']) ? $_GET['search_date'] : null;
                
                if ($searchDate) {
                    $filteredHistory = array_filter($history, function($item) use ($searchDate) {
                        return date('Y-m-d', strtotime($item['waktu_mulai'])) === $searchDate;
                    });
                } else {
                    $filteredHistory = $history;
                }
                $filteredHistory = array_values($filteredHistory);
                
                $displayHistory = $filteredHistory;
                $isToday = !$searchDate || $searchDate === date('Y-m-d');
                $isCustomSearch = $searchDate && $searchDate !== date('Y-m-d');
                ?>

                <?php if (count($displayHistory) === 0): ?>
                    <div class="empty-state">
                        <?php if ($isCustomSearch): ?>
                            <strong>Tidak ada riwayat shift pada tanggal <?= htmlspecialchars($searchDate) ?>.</strong>
                            <p>Coba tanggal lain atau <a href="<?= $_SERVER['PHP_SELF'] ?>?tab=history">lihat semua riwayat</a>.</p>
                        <?php else: ?>
                            <strong>Belum ada riwayat shift.</strong>
                            <p>Mulai shift pertama Anda untuk melihat rekam jejak di sini.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="history-summary">
                        <div class="summary-grid">
                            <div class="summary-item">
                                <span class="summary-label">Total Shift</span>
                                <strong><?= count($displayHistory) ?></strong>
                            </div>
                            <?php if ($searchDate): ?>
                                <div class="summary-item">
                                    <span class="summary-label">Tanggal</span>
                                    <strong><?= date('d M Y', strtotime($searchDate)) ?></strong>
                                </div>
                            <?php else: ?>
                                <div class="summary-item">
                                    <span class="summary-label">Periode</span>
                                    <strong>Semua Riwayat</strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <ul class="history-list compact-view">
                        <?php foreach ($displayHistory as $item): 
                            $rekap_info = get_rekap_data_for_shift($pdo, $item['id']);
                            $is_synced = $rekap_info !== null;
                            
                            $saldo_awal = $rekap_info['saldo_awal'] ?? $item['saldo_awal'] ?? 0;
                            $saldo_akhir = $rekap_info['saldo_akhir'] ?? $item['saldo_akhir'] ?? $saldo_awal;
                            $selisih = $rekap_info['selisih'] ?? ($saldo_akhir - $saldo_awal);
                            $waktu_mulai = $rekap_info['waktu_mulai'] ?? $item['waktu_mulai'] ?? $item['waktu'];
                            $waktu_selesai = $rekap_info['waktu_selesai'] ?? $item['waktu_selesai'] ?? null;
                            
                            $cashdrawer_display = $item["cashdrawer"];
                            if ($cashdrawer_display === "Cashdrawer-Otomatis") {
                                $cashdrawer_display = "Riwayat Shift";
                            }
                        ?>

                            <li class="history-card compact-card" 
                                data-shift-id="<?= $item['id'] ?>" 
                                onclick="showShiftDetail('<?= $item['id'] ?>')">
                                
                                <div class="compact-header">
                                    <div class="shift-basic-info">
                                        <span class="cashdrawer-name"><?= htmlspecialchars($cashdrawer_display) ?></span>
                                        <span class="shift-date"><?= date('d M Y', strtotime($waktu_mulai)) ?></span>
                                    </div>
                                    <div class="shift-status">
                                        <?php if ($is_synced): ?>
                                            <span class="sync-indicator">🔄</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="compact-saldo">
                                    <div class="saldo-item">
                                        <small>Saldo Awal</small>
                                        <span class="saldo-awal"><?= format_rupiah($saldo_awal) ?></span>
                                    </div>
                                    <div class="saldo-item">
                                        <small>Saldo Akhir</small>
                                        <span class="saldo-akhir"><?= format_rupiah($saldo_akhir) ?></span>
                                    </div>
                                    <div class="saldo-item">
                                        <small>Selisih</small>
                                        <span class="<?= $selisih >= 0 ? 'selisih-positif' : 'selisih-negatif' ?>">
                                            <?= $selisih >= 0 ? '+' : '' ?><?= format_rupiah(abs($selisih)) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="compact-time">
                                    <small>
                                        Mulai: <?= format_time($waktu_mulai) ?>
                                        <?php if ($waktu_selesai): ?>
                                            • Selesai: <?= format_time($waktu_selesai) ?>
                                        <?php endif; ?>
                                    </small>
                                </div>

                                <div class="compact-user">
                                    <small><?= htmlspecialchars($item['nama'] ?? $_SESSION["namalengkap"]) ?> • <?= htmlspecialchars($item['role'] ?? $_SESSION["nama"]) ?></small>
                                </div>

                                <div class="view-detail-btn">
                                    <span>Lihat Detail →</span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <div id="shiftDetailModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modalShiftTitle">Detail Shift</h3>
                        <button type="button" class="btn-close" onclick="shiftHistory.close()">&times;</button>
                    </div>
                    <div class="modal-body" id="shiftDetailContent"></div>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 Universitas Anwar Medika</p>
        </footer>
    </div>
    <script>
        window.shiftHistoryData = <?= json_encode($displayHistory, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
        window.rekapData = <?= 
            json_encode(
                array_combine(
                    array_column($displayHistory, 'id'),
                    array_map(function($item) use ($pdo) {
                        return get_rekap_data_for_shift($pdo, $item['id']);
                    }, $displayHistory)
                ),
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
            ) 
        ?>;
    </script>
</body>
</html>