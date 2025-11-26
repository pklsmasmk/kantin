<?php
require_once '../Database/config.php';
require_once '../Database/functions_piutang.php';

function loadRecordWithPayments($id, $type) {
    $database = db_kantin();
    $db = $database->pdo;
    
    try {
        // Load record data
        $query = "SELECT r.*, 
                         COALESCE(SUM(p.jumlah), 0) as total_dibayar
                  FROM records r 
                  LEFT JOIN payments p ON r.id = p.record_id 
                  WHERE r.id = ? AND r.type = ?
                  GROUP BY r.id";
        $stmt = $db->prepare($query);
        $stmt->execute([$id, $type]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$record) {
            return null;
        }
        
        // Load payments history
        $query = "SELECT * FROM payments WHERE record_id = ? ORDER BY tanggal DESC, waktu DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $record['pembayaran'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $record;
    } catch (Exception $e) {
        error_log("Error loading record: " . $e->getMessage());
        return null;
    }
}

function savePayment($record_id, $paymentData) {
    $database = db_kantin();
    $db = $database->pdo;
    
    try {
        $db->beginTransaction();
        
        // Insert payment
        $query = "INSERT INTO payments (record_id, jumlah, tanggal, waktu, metode, keterangan) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $record_id,
            $paymentData['jumlah'],
            $paymentData['tanggal'],
            $paymentData['waktu'],
            $paymentData['metode'],
            $paymentData['keterangan']
        ]);
        
        // Check if record is fully paid
        $query = "SELECT r.amount, COALESCE(SUM(p.jumlah), 0) as total_dibayar
                  FROM records r 
                  LEFT JOIN payments p ON r.id = p.record_id 
                  WHERE r.id = ? 
                  GROUP BY r.id";
        $stmt = $db->prepare($query);
        $stmt->execute([$record_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['total_dibayar'] >= $result['amount']) {
            $query = "UPDATE records SET status = 'lunas' WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$record_id]);
        }
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error saving payment: " . $e->getMessage());
        return false;
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
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
            if (savePayment($id, [
                'jumlah' => $jumlahBayar,
                'tanggal' => $tanggalBayar,
                'waktu' => $waktuBayar,
                'metode' => $metodeBayar,
                'keterangan' => $keterangan
            ])) {
                $_SESSION['success'] = 'Pembayaran berhasil dicatat!';
            } else {
                $_SESSION['error'] = 'Gagal menyimpan pembayaran!';
            }
        }
        echo '<script>window.location.href = "' . $_SERVER['REQUEST_URI'] . '";</script>';
        exit;
    }
}

$id = $_GET['id'] ?? '';
$type = $_GET['type'] ?? '';

$record = loadRecordWithPayments($id, $type);

if (!$record) {
    die('<div class="container my-5"><div class="alert alert-danger">Data tidak ditemukan</div></div>');
}

$totalDibayar = $record['total_dibayar'] ?? 0;
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
    <style>
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        .live-clock {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 2rem;
            border: 2px solid #e9ecef;
        }
        .live-clock .date {
            font-size: 1.2rem;
            font-weight: 600;
            color: #495057;
        }
        .live-clock .time {
            font-size: 2.5rem;
            font-weight: 700;
            color: #dc3545;
            font-family: 'Courier New', monospace;
        }
        .live-clock .timezone {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .payment-section, .history-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .total-amount {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
        }
        .btn-bayar {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .btn-bayar:hover {
            background: linear-gradient(135deg, #218838, #1e9e8a);
            color: white;
        }
        .time-form-group {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }
        .time-form-label {
            display: flex;
            align-items: center;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #495057;
        }
        .datetime-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
        }
        .time-input-container {
            position: relative;
        }
        .time-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .time-highlight {
            background: #e7f3ff;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .timeline-item {
            position: relative;
            padding-left: 2rem;
            margin-bottom: 1.5rem;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #007bff;
            border-radius: 3px;
        }
        .new-payment .card {
            border: 2px solid #28a745;
            background: #f8fff9;
        }
        .payment-time-badge {
            background: #007bff;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .history-time {
            background: #e9ecef;
            padding: 0.2rem 0.5rem;
            border-radius: 5px;
            font-size: 0.8rem;
        }
        .badge-status {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
    </style>
</head>
<body>
    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-dark">
                <i class="fas fa-credit-card me-2"></i>
                Pembayaran <?= ucfirst($record['type']) ?>
            </h1>
            <a href="/?q=piutang_hasilpiutang" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="header-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3 class="mb-3"><?= htmlspecialchars($record['name']) ?></h3>
                    
                    <div class="info-row mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Status:</strong><br>
                                <span class="badge <?= $record['status'] === 'lunas' ? 'bg-success' : 'bg-warning' ?> badge-status">
                                    <i class="fas fa-<?= $record['status'] === 'lunas' ? 'check' : 'clock' ?> me-1"></i>
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
                                <span class="fs-5 fw-bold text-warning"><?= formatRupiah($record['amount']) ?></span>
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
                    <div class="total-amount text-white">
                        <?= formatRupiah($sisaBayar) ?>
                    </div>
                    <div class="text-light">Sisa yang harus dibayar</div>
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
            
            <form method="POST" id="paymentForm">
                <input type="hidden" name="action" value="bayar">
                <input type="hidden" name="id" value="<?= $record['id'] ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Jumlah Bayar (Rp)</label>
                        <input type="number" name="jumlah_bayar" class="form-control" 
                               min="1" max="<?= $sisaBayar ?>" value="<?= $sisaBayar ?>"
                               placeholder="Masukkan jumlah pembayaran" required step="1000">
                        <div class="form-text">Maksimal: <?= formatRupiah($sisaBayar) ?></div>
                    </div>

                    <div class="col-12 mb-4">
                        <div class="time-form-group">
                            <div class="time-form-label">
                                <i class="fas fa-clock text-primary me-2"></i>
                                <span>Waktu Pembayaran</span>
                                <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="setCurrentTime()">
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
                                    <span class="fw-semibold">Pembayaran akan dicatat pada:</span>
                                    <span class="current-time fw-bold text-primary" id="displayTime">
                                        <?= date('d/m/Y') ?> | <?= date('H:i') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Metode Pembayaran</label>
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
                        <label class="form-label fw-semibold">Keterangan</label>
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
            <h4 class="mb-4"><i class="fas fa-history me-2"></i>Riwayat Pembayaran</h4>
            
            <?php if (empty($record['pembayaran'])): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-receipt fa-3x mb-3"></i>
                    <p class="fs-5">Belum ada riwayat pembayaran</p>
                </div>
            <?php else: ?>
                <div class="timeline mb-4">
                    <?php foreach ($record['pembayaran'] as $index => $bayar): ?>
                        <div class="timeline-item <?= $index === 0 ? 'new-payment' : '' ?>">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <span class="payment-time-badge">
                                                <i class="fas fa-receipt me-1"></i>
                                                Pembayaran #<?= count($record['pembayaran']) - $index ?>
                                            </span>
                                        </div>
                                        <div class="text-end">
                                            <div class="payment-datetime">
                                                <span class="payment-date fw-semibold"><?= formatTanggal($bayar['tanggal']) ?></span>
                                                <span class="payment-time badge bg-secondary ms-2">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?= formatWaktu($bayar['waktu']) ?>
                                                </span>
                                            </div>
                                            <small class="text-muted"><?= formatTanggalWaktu($bayar['timestamp']) ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="row align-items-center">
                                        <div class="col-md-4">
                                            <h5 class="text-success mb-0">
                                                <i class="fas fa-money-bill-wave me-2"></i>
                                                <?= formatRupiah($bayar['jumlah']) ?>
                                            </h5>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="badge bg-info fs-6">
                                                <i class="fas fa-wallet me-1"></i>
                                                <?= $bayar['metode'] ?>
                                            </span>
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
                    <table class="table table-hover table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Jumlah Bayar</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Metode</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($record['pembayaran'] as $index => $bayar): ?>
                                <tr class="<?= $index === 0 ? 'table-success' : '' ?>">
                                    <td class="fw-bold"><?= $index + 1 ?></td>
                                    <td class="fw-bold text-success"><?= formatRupiah($bayar['jumlah']) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?= formatTanggal($bayar['tanggal']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="history-time">
                                            <i class="fas fa-clock me-1"></i>
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
            
            // Show confirmation
            const toast = document.createElement('div');
            toast.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
            toast.style.zIndex = '1050';
            toast.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                Waktu berhasil diatur ke waktu sekarang
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        // Validasi jumlah bayar
        document.querySelector('input[name="jumlah_bayar"]').addEventListener('input', function() {
            const maxAmount = <?= $sisaBayar ?>;
            const inputAmount = parseFloat(this.value) || 0;
            
            if (inputAmount > maxAmount) {
                this.value = maxAmount;
                showAlert('Jumlah pembayaran tidak boleh melebihi sisa bayar: <?= formatRupiah($sisaBayar) ?>', 'warning');
            }
        });

        // Update highlight ketika input berubah
        document.querySelector('input[name="tanggal_bayar"]').addEventListener('change', function() {
            this.setAttribute('data-manual', 'true');
            updateTimeHighlight();
        });

        document.querySelector('input[name="waktu_bayar"]').addEventListener('change', function() {
            this.setAttribute('data-manual', 'true');
            updateTimeHighlight();
        });

        // Validasi form
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const jumlahBayar = parseFloat(document.querySelector('input[name="jumlah_bayar"]').value);
            if (jumlahBayar <= 0) {
                e.preventDefault();
                showAlert('Jumlah bayar harus lebih dari 0!', 'error');
                return false;
            }
        });

        function showAlert(message, type = 'info') {
            const alertClass = type === 'error' ? 'alert-danger' : 
                             type === 'warning' ? 'alert-warning' : 'alert-info';
            
            const alert = document.createElement('div');
            alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
            alert.style.zIndex = '1050';
            alert.innerHTML = `
                <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateClock();
            setInterval(updateClock, 1000);
            updateTimeHighlight();
        });
    </script>
</body>
</html>