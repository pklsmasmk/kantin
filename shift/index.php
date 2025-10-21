<?php
    include("PHP/Back.php");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Shift Kasir - UAM</title>
    <link rel="stylesheet" href="../shift/CSS/shift.css" />
    <link rel="stylesheet" href="../shift/CSS/modal.css" />
    <link rel="stylesheet" href="../shift/CSS/tambahan.css" />
    <link rel="stylesheet" href="../shift/CSS/tambahan_histori.css" />
</head>
<body>
    <div class="container" role="main" aria-label="Halaman Shift Kasir">
        <header>
            <div class="logo" aria-label="UAM Logo">
                <svg width="28" height="28" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <rect width="28" height="28" rx="8" ry="8" fill="#5d4e37" />
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
                    <span>Ka</span>
                </div>
                <div class="user-name-role">
                    <strong>User Kasir</strong>
                    <small>Kasir</small>
                </div>
            </section>

            <?php if (!empty($error)): ?>
                <div class="alert error" role="status"><?= htmlspecialchars($error) ?></div>
            <?php elseif (!empty($success)): ?>
                <div class="alert success" role="status"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <section class="tab-panel current-panel <?= $active_tab === 'current' ? 'is-active' : '' ?>" data-tab-panel="current">
                <section class="info">
                    <p>
                        <strong>Sistem Shift Kasir Kantin UAM</strong><br>
                        Aplikasi kasir dengan sistem warisan saldo otomatis dan manajemen setoran terintegrasi.
                    </p>
                    <div style="margin-top: 12px; padding-left: 16px; border-left: 3px solid #8B7355;">
                        <strong>Alur Kerja Sistem:</strong>
                        <ul style="margin: 8px 0; padding-left: 20px; color: #555;">
                            <li><strong>Mulai Shift</strong> - Input saldo awal, sistem otomatis tambah saldo warisan</li>
                            <li><strong>Operasional</strong> - Transaksi penjualan, pengeluaran, pemasukan/pengeluaran lain</li>
                            <li><strong>Setoran Fleksibel</strong> - Setor kapan saja dari saldo akhir yang tersedia</li>
                            <li><strong>Rekap Detail</strong> - Monitoring lengkap transaksi dan saldo</li>
                            <li><strong>Akhiri Shift</strong> - Sistem hitung otomatis, saldo akhir jadi warisan berikutnya</li>
                        </ul>
                        
                        <div style="margin-top: 10px; padding: 8px; background: #fff3cd; border-radius: 6px;">
                            <small><strong>Saldo Warisan:</strong> Saldo akhir shift sebelumnya otomatis menjadi bagian saldo awal shift baru</small>
                        </div>
                    </div>
                </section>
                
                <?php if ($saldo_warisan > 0): ?>
                <div class="saldo-warisan-info">
                    <small>Saldo warisan dari shift sebelumnya: <strong><?= format_rupiah($saldo_warisan) ?></strong> akan ditambahkan ke saldo awal</small>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="shift-form" novalidate>
                    <label for="cashdrawer">Pilih Cashdrawer</label>
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

                    <label for="saldo_awal">Masukkan Saldo Awal</label>
                    <div class="input-rp">
                        <span class="rp-label">Rp</span>
                        <input
                            type="text"
                            id="saldo_awal"
                            name="saldo_awal"
                            placeholder="0"
                            autocomplete="off"
                            inputmode="numeric"
                            pattern="[0-9.,]*"
                            required
                            value=""
                        />
                    </div>
                    <small id="lastData" class="last-data">
                        <?php if (!empty($history)): ?>
                            Data terakhir: <?= htmlspecialchars($history[0]["cashdrawer"]) ?> •
                            Saldo awal: <?= format_rupiah($history[0]["saldo_awal"]) ?> • Saldo akhir: <?= format_rupiah($history[0]["saldo_akhir"]) ?> •
                            <?= format_datetime($history[0]["waktu_mulai"]) ?>
                        <?php else: ?>
                            Silahkan isi Field diatas untuk memulai shift Anda
                        <?php endif; ?>
                    </small>
                    
                    <?php if ($saldo_warisan > 0): ?>
                    <div class="warisan-notice">
                        <small><strong>SALDO WARISAN:</strong> <?= format_rupiah($saldo_warisan) ?> akan ditambahkan ke saldo awal Anda</small>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="submit-btn" id="submitShiftBtn">
                        <?php if ($saldo_warisan > 0): ?>
                            Mulai Shift Anda - Total: <?= format_rupiah($saldo_warisan) ?> + Saldo Awal
                        <?php else: ?>
                            Mulai Shift Anda
                        <?php endif; ?>
                    </button>

                    <div class="action-buttons">
                        <a href="/shift/Rekap_Shift/rekap_shift.php" class="action-btn manage-cash-btn" <?= !$currentShift ? 'style="opacity: 0.6; pointer-events: none;"' : '' ?>>
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
                            <?php if ($saldo_warisan > 0): ?>
                            <div class="item-detail">
                                <span class="label-detail">Saldo Warisan:</span>
                                <span class="nilai-detail" id="modalSaldoWarisan"></span>
                            </div>
                            <div class="item-detail">
                                <span class="label-detail">Total Saldo:</span>
                                <span class="nilai-detail" id="modalTotalSaldo"></span>
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
                    <form method="POST" class="setoran-form" novalidate>
                        <input type="hidden" name="setoran_action" value="add">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="penyetor">Nama Penyetor *</label>
                                <input type="text" id="penyetor" name="penyetor" required 
                                    placeholder="Masukkan nama penyetor" value="<?= htmlspecialchars($currentShift['nama'] ?? 'User Kasir') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="jumlah_setoran">Jumlah Setoran (Rp) *</label>
                                <div class="input-rp">
                                    <input type="text" id="jumlah_setoran" name="jumlah_setoran" required 
                                        placeholder="0" inputmode="numeric" <?= !$can_setor ? 'disabled' : '' ?>>
                                </div>
                                <?php if ($can_setor): ?>
                                    <small class="saldo-tersedia">Saldo tersedia untuk disetor: <?= format_rupiah($saldo_tersedia) ?></small>
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

                        <button type="submit" class="submit-btn setoran-submit" <?= !$can_setor ? 'disabled' : '' ?>>
                            <?php if ($can_setor): ?>
                                Simpan Setoran dari Saldo Akhir
                            <?php else: ?>
                                <?= $saldo_akhir_riwayat > 0 ? 'Tidak Dapat Setoran' : 'Tidak Ada Saldo' ?>
                            <?php endif; ?>
                        </button>
                        
                        <?php if (!$can_setor && $saldo_akhir_riwayat > 0): ?>
                            <div class="isi-ulang-info">
                                <small><strong>Tidak ada saldo tersisa untuk disetor. </strong> Silakan mulai shift baru untuk menambah saldo.</small>
                        </div>
                        <?php elseif (!$can_setor): ?>
                            <div class="warning-message">
                                <small>Tidak ada saldo, silahkan mulai shift terlebih dahulu</small>
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
                            <span class="info-label">Sumber Setoran:</span>
                            <span class="info-value">
                                Saldo akhir dari shift (berjalan/riwayat)
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Rumus Setoran:</span>
                            <span class="info-value">
                                Saldo Tersedia = Saldo Akhir - Total Setoran Hari Ini
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status Setoran:</span>
                            <span class="info-value">
                                <?php if ($saldo_tersedia > 0): ?>
                                    <span style="color: #28a745;">● Masih bisa setor (<?= format_rupiah($saldo_tersedia) ?> tersedia)</span>
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
             
            <section class="tab-panel history-panel <?= $active_tab === 'history' ? 'is-active' : '' ?>" data-tab-panel="history" aria-label="Riwayat shift cashdrawer">
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
                $searchDate = isset($_GET['search_date']) && !empty($_GET['search_date']) ? $_GET['search_date'] : date('Y-m-d');
                
                $filteredHistory = array_filter($history, function($item) use ($searchDate) {
                    return date('Y-m-d', strtotime($item['waktu_mulai'])) === $searchDate;
                });
                $filteredHistory = array_values($filteredHistory);
                
                $displayHistory = $filteredHistory;
                $isToday = $searchDate === date('Y-m-d');
                $isCustomSearch = isset($_GET['search_date']) && $_GET['search_date'] !== date('Y-m-d');
                ?>

                <?php if (count($displayHistory) === 0): ?>
                    <div class="empty-state">
                        <?php if ($isCustomSearch): ?>
                            <strong>Tidak ada riwayat shift pada tanggal <?= htmlspecialchars($_GET['search_date']) ?>.</strong>
                            <p>Coba tanggal lain atau lihat riwayat hari ini</a>.</p>
                        <?php else: ?>
                            <strong>Belum ada riwayat shift hari ini.</strong>
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
                        </div>
                        <?php if ($isCustomSearch): ?>
                            <div class="search-info">
                                Menampilkan shift pada: <?= date('d M Y', strtotime($_GET['search_date'])) ?>
                            </div>
                        <?php else: ?>
                            <div class="search-info">
                                Menampilkan shift hari ini: <?= date('d M Y') ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <ul class="history-list compact-view">
                        <?php foreach ($displayHistory as $item): 
                            $rekap_info = get_rekap_data_for_shift($pdo, $item['id']); // BENAR
                            $is_synced = $rekap_info !== null;
                            
                            $saldo_awal = $rekap_info['saldo_awal'] ?? $item['saldo_awal'] ?? 0;
                            $saldo_akhir = $rekap_info['saldo_akhir'] ?? $item['saldo_akhir'] ?? $saldo_awal;
                            $selisih = $rekap_info['selisih'] ?? ($saldo_akhir - $saldo_awal);
                            $waktu_mulai = $rekap_info['waktu_mulai'] ?? $item['waktu_mulai'] ?? $item['waktu'];
                            $waktu_selesai = $rekap_info['waktu_selesai'] ?? $item['waktu_selesai'] ?? null;
                        ?>

                            <li class="history-card compact-card" 
                                data-shift-id="<?= $item['id'] ?>" 
                                onclick="showShiftDetail('<?= $item['id'] ?>')">
                                
                                <div class="compact-header">
                                    <div class="shift-basic-info">
                                        <span class="cashdrawer-name"><?= htmlspecialchars($item["cashdrawer"]) ?></span>
                                        <span class="shift-date"><?= date('d M', strtotime($waktu_mulai)) ?></span>
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
                                    <small><?= htmlspecialchars($item["nama"]) ?> • <?= htmlspecialchars($item["role"]) ?></small>
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
                        <h3>Detail Shift</h3>
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
    <script src="../shift/JS/shift.js"></script>
    <script src="../shift/JS/shift_history.js"></script>
</body>
</html>