<?php
session_start();

function loadData()
{
    $file = 'data.json';
    if (file_exists($file)) {
        $json = file_get_contents($file);
        return json_decode($json, true) ?: [];
    }
    return [];
}

function saveData($records)
{
    file_put_contents('data.json', json_encode($records, JSON_PRETTY_PRINT));
}

function formatRupiah($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function formatTanggal($dateString)
{
    if (empty($dateString) || $dateString == '0000-00-00') {
        return '-';
    }
    try {
        return date('d/m/Y', strtotime($dateString));
    } catch (Exception $e) {
        return '-';
    }
}

function formatTanggalWaktu($dateString)
{
    if (empty($dateString) || $dateString == '0000-00-00 00:00:00') {
        return '-';
    }
    try {
        return date('d/m/Y H:i', strtotime($dateString));
    } catch (Exception $e) {
        return '-';
    }
}

function formatWaktu($timeString)
{
    if (empty($timeString)) {
        return '-';
    }
    try {
        if (preg_match('/^\d{2}:\d{2}$/', $timeString)) {
            return $timeString;
        }
        return date('H:i', strtotime($timeString));
    } catch (Exception $e) {
        return '-';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $records = loadData();
    
    if ($action === 'bayar') {
        $id = $_POST['id'] ?? '';
        $jumlahBayar = (float) ($_POST['jumlah_bayar'] ?? 0);
        $tanggalBayar = $_POST['tanggal_bayar'] ?? date('Y-m-d');
        $waktuBayar = $_POST['waktu_bayar'] ?? date('H:i');
        $metodeBayar = $_POST['metode_bayar'] ?? 'Cash';
        $keterangan = trim($_POST['keterangan'] ?? '');

        if ($jumlahBayar <= 0) {
            $_SESSION['error'] = 'Jumlah bayar harus lebih dari 0!';
        } else {
            foreach ($records as &$record) {
                if ($record['id'] == $id) {

                    if (!isset($record['pembayaran'])) {
                        $record['pembayaran'] = [];
                    }
                    $record['pembayaran'][] = [
                        'jumlah' => $jumlahBayar,
                        'tanggal' => $tanggalBayar,
                        'waktu' => $waktuBayar,
                        'metode' => $metodeBayar,
                        'keterangan' => $keterangan,
                        'timestamp' => date('Y-m-d H:i:s')
                    ];

                    $totalDibayar = array_reduce($record['pembayaran'], function ($sum, $bayar) {
                        return $sum + $bayar['jumlah'];
                    }, 0);

                    if ($totalDibayar >= $record['amount']) {
                        $record['status'] = 'lunas';
                    }
                    
                    $_SESSION['success'] = 'Pembayaran berhasil dicatat!';
                    break;
                }
            }
            
            saveData($records);
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

$id = $_GET['id'] ?? '';
$type = $_GET['type'] ?? '';

$records = loadData();
$record = null;

foreach ($records as $r) {
    if ($r['id'] == $id && $r['type'] === $type) {
        $record = $r;
        break;
    }
}

if (!$record) {
    die('Data tidak ditemukan');
}

$totalDibayar = 0;
if (isset($record['pembayaran'])) {
    $totalDibayar = array_reduce($record['pembayaran'], function ($sum, $bayar) {
        return $sum + $bayar['jumlah'];
    }, 0);
}

$sisaBayar = $record['amount'] - $totalDibayar;

$defaultTanggal = date('Y-m-d');
$defaultWaktu = date('H:i');
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran <?= ucfirst($record['type']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/bayar.css">
</head>

<body>
    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-dark">
                <i class="fas fa-credit-card me-2"></i>
                Pembayaran <?= ucfirst($record['type']) ?>
            </h1>
            <a href="<?= $record['type'] === 'hutang' ? 'hutang.php' : 'piutang.php' ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="header-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3 class="text-dark mb-3"><?= htmlspecialchars($record['name']) ?></h3>
                    
                    <div class="info-row">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Status:</strong><br>
                                <span class="badge <?= $record['status'] === 'lunas' ? 'bg-success' : 'bg-warning' ?> badge-status">
                                    <?= ucwords($record['status']) ?>
                                </span>
                            </div>
                            <div class="col-md-4">
                                <strong>Tanggal Transaksi:</strong><br>
                                <?= formatTanggalWaktu($record['createdAt']) ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Jatuh Tempo:</strong><br>
                                <?= formatTanggal($record['dueDate']) ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Total <?= ucfirst($record['type']) ?>:</strong><br>
                                <span class="fs-5 fw-bold text-primary"><?= formatRupiah($record['amount']) ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong>Total Dibayar:</strong><br>
                                <span class="fs-5 fw-bold text-success"><?= formatRupiah($totalDibayar) ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong>Sisa Bayar:</strong><br>
                                <span class="fs-5 fw-bold text-danger"><?= formatRupiah($sisaBayar) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="total-amount text-primary">
                        <?= formatRupiah($sisaBayar) ?>
                    </div>
                    <div class="text-muted">Sisa yang harus dibayar</div>
                </div>
            </div>
        </div>

        <div class="live-clock">
            <div class="date" id="currentDate">Loading...</div>
            <div class="time" id="currentTime">00:00:00</div>
            <div class="timezone" id="currentTimezone">WIB (UTC+7)</div>
        </div>

        <div class="payment-section">
            <h4 class="mb-4"><i class="fas fa-money-bill-wave me-2"></i>Bayar <?= ucfirst($record['type']) ?></h4>
            
            <form method="POST">
                <input type="hidden" name="action" value="bayar">
                <input type="hidden" name="id" value="<?= $record['id'] ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jumlah Bayar (Rp)</label>
                        <input type="number" name="jumlah_bayar" class="form-control" 
                               min="1" max="<?= $sisaBayar ?>" value="<?= $sisaBayar ?>"
                               placeholder="Masukkan jumlah pembayaran" required>
                        <div class="form-text">Maksimal: <?= formatRupiah($sisaBayar) ?></div>
                    </div>

                    <div class="col-12">
                        <div class="time-form-group">
                            <div class="time-form-label">
                                <i class="fas fa-clock"></i>
                                <span>Waktu Pembayaran</span>
                                <button type="button" class="btn-time-now ms-2" onclick="setCurrentTime()">
                                    <i class="fas fa-sync-alt me-1"></i> Gunakan Waktu Sekarang
                                </button>
                            </div>
                            <div class="datetime-inputs">
                                <div class="flex-grow-1">
                                    <label class="form-label">Tanggal Pembayaran</label>
                                    <input type="date" name="tanggal_bayar" class="form-control" 
                                           value="<?= $defaultTanggal ?>" required>
                                </div>
                                <div class="flex-grow-1">
                                    <label class="form-label">Waktu Pembayaran</label>
                                    <div class="time-input-container">
                                        <input type="time" name="waktu_bayar" class="form-control time-input" 
                                               value="<?= $defaultWaktu ?>" required>
                                        <i class="fas fa-clock time-icon"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <label class="form-label">Zona Waktu</label>
                                    <input type="text" class="form-control" value="WIB (UTC+7)" readonly 
                                           style="background: #e9ecef;">
                                </div>
                            </div>
                            <div class="time-highlight mt-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span>Pembayaran akan dicatat pada:</span>
                                    <span class="current-time" id="displayTime">
                                        <?= date('d/m/Y') ?> | <?= date('H:i') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Metode Pembayaran</label>
                        <select name="metode_bayar" class="form-select" required>
                            <option value="Cash">Cash</option>
                            <option value="Transfer">Transfer Bank</option>
                            <option value="Dana">Dana</option>
                            <option value="Ovo">OVO</option>
                            <option value="Gopay">GoPay</option>
                            <option value="Qris">QRIS</option>
                            <option value="Debit">Kartu Debit</option>
                            <option value="Kredit">Kartu Kredit</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control" 
                               placeholder="Contoh: Masukkan ke kas, Bayar cicilan ke-1, dll.">
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-bayar">
                        <i class="fas fa-save me-2"></i> SIMPAN PEMBAYARAN
                    </button>
                </div>
            </form>
        </div>

        <div class="history-section">
            <h4 class="mb-4"><i class="fas fa-history me-2"></i>History Pembayaran</h4>
            
            <?php if (empty($record['pembayaran'])): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-receipt fa-3x mb-3"></i>
                    <p>Belum ada history pembayaran</p>
                </div>
            <?php else: ?>

                <div class="timeline mb-4">
                    <?php foreach ($record['pembayaran'] as $index => $bayar): ?>
                        <div class="timeline-item <?= $index === count($record['pembayaran']) - 1 ? 'new-payment' : '' ?>">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <span class="payment-time-badge">
                                                Pembayaran #<?= $index + 1 ?>
                                            </span>
                                        </div>
                                        <div class="text-end">
                                            <div class="payment-datetime">
                                                <span class="payment-date"><?= formatTanggal($bayar['tanggal']) ?></span>
                                                <span class="payment-time">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?= formatWaktu($bayar['waktu']) ?>
                                                </span>
                                            </div>
                                            <small class="text-muted"><?= formatTanggalWaktu($bayar['timestamp']) ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="row align-items-center">
                                        <div class="col-md-4">
                                            <h5 class="text-success mb-0"><?= formatRupiah($bayar['jumlah']) ?></h5>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="badge bg-info"><?= $bayar['metode'] ?></span>
                                        </div>
                                        <div class="col-md-4 text-md-end">
                                            <small class="text-muted"><?= htmlspecialchars($bayar['keterangan'] ?: '-') ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Jumlah Bayar</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Metode</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($record['pembayaran'] as $index => $bayar): ?>
                                <tr class="<?= $index === count($record['pembayaran']) - 1 ? 'new-payment' : '' ?>">
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-bold text-success"><?= formatRupiah($bayar['jumlah']) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?= formatTanggal($bayar['tanggal']) ?>
                                            <span class="history-time">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= formatWaktu($bayar['waktu']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="history-time">
                                            <?= formatWaktu($bayar['waktu']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $bayar['metode'] ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($bayar['keterangan'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateClock() {
            const now = new Date();
            const optionsDate = { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric',
                timeZone: 'Asia/Jakarta'
            };
            const date = now.toLocaleDateString('id-ID', optionsDate);
            const time = now.toLocaleTimeString('id-ID', { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit',
                hour12: false,
                timeZone: 'Asia/Jakarta'
            });

            document.getElementById('currentDate').textContent = date;
            document.getElementById('currentTime').textContent = time;
            updateFormTime(now);
        }

        function updateFormTime(now) {
            const dateInput = document.querySelector('input[name="tanggal_bayar"]');
            const timeInput = document.querySelector('input[name="waktu_bayar"]');

            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const currentDate = `${year}-${month}-${day}`;

            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const currentTime = `${hours}:${minutes}`;

            if (!dateInput.hasAttribute('data-manual') && dateInput.value === '<?= $defaultTanggal ?>') {
                dateInput.value = currentDate;
            }
            
            if (!timeInput.hasAttribute('data-manual') && timeInput.value === '<?= $defaultWaktu ?>') {
                timeInput.value = currentTime;
            }

            updateTimeHighlight();
        }

        function updateTimeHighlight() {
            const dateInput = document.querySelector('input[name="tanggal_bayar"]');
            const timeInput = document.querySelector('input[name="waktu_bayar"]');
            const highlightElement = document.getElementById('displayTime');
            
            if (dateInput.value && timeInput.value) {
                const [year, month, day] = dateInput.value.split('-');
                const formattedDate = `${day}/${month}/${year}`;
                highlightElement.textContent = `${formattedDate} | ${timeInput.value}`;
            }
        }

        function setCurrentTime() {
            const now = new Date();

            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const currentDate = `${year}-${month}-${day}`;

            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const currentTime = `${hours}:${minutes}`;

            const dateInput = document.querySelector('input[name="tanggal_bayar"]');
            const timeInput = document.querySelector('input[name="waktu_bayar"]');
            
            dateInput.value = currentDate;
            timeInput.value = currentTime;

            dateInput.setAttribute('data-manual', 'true');
            timeInput.setAttribute('data-manual', 'true');
            
            updateTimeHighlight();
        }

        document.querySelector('input[name="jumlah_bayar"]').addEventListener('input', function() {
            const maxAmount = <?= $sisaBayar ?>;
            const inputAmount = parseFloat(this.value) || 0;
            
            if (inputAmount > maxAmount) {
                this.value = maxAmount;
                alert('Jumlah pembayaran tidak boleh melebihi sisa bayar: <?= formatRupiah($sisaBayar) ?>');
            }
        });

        document.querySelector('input[name="tanggal_bayar"]').addEventListener('change', function() {
            this.setAttribute('data-manual', 'true');
            updateTimeHighlight();
        });

        document.querySelector('input[name="waktu_bayar"]').addEventListener('change', function() {
            this.setAttribute('data-manual', 'true');
            updateTimeHighlight();
        });

        document.addEventListener('DOMContentLoaded', function() {
            updateClock();
            setInterval(updateClock, 1000);
            updateTimeHighlight();
        });
    </script>
</body>

</html>