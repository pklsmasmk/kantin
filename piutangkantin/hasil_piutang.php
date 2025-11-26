<?php
require_once '../Database/config.php';
require_once '../Database/functions_piutang.php';

function loadPiutangData($filterStatus = 'all', $searchTerm = '')
{
    $database = db_kantin();
    $db = $database->pdo;

    $query = "SELECT r.*, 
                     COALESCE(SUM(p.jumlah), 0) as total_dibayar,
                     COUNT(p.id) as jumlah_pembayaran
              FROM records r 
              LEFT JOIN payments p ON r.id = p.record_id 
              WHERE r.type = 'piutang'";

    $params = [];

    if ($filterStatus !== 'all') {
        $query .= " AND r.status = ?";
        $params[] = str_replace('_', ' ', $filterStatus);
    }

    if (!empty($searchTerm)) {
        $query .= " AND r.name LIKE ?";
        $params[] = "%$searchTerm%";
    }

    $query .= " GROUP BY r.id ORDER BY r.createdAt DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Load payments untuk setiap record
    foreach ($records as &$record) {
        $query = "SELECT * FROM payments WHERE record_id = ? ORDER BY tanggal DESC, waktu DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$record['id']]);
        $record['pembayaran'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return $records;
}

function updateRecordStatus($id, $newStatus)
{
    $database = db_kantin();
    $db = $database->pdo;

    $query = "UPDATE records SET status = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    return $stmt->execute([$newStatus, $id]);
}

function deleteRecord($id)
{
    $database = db_kantin();
    $db = $database->pdo;

    try {
        $db->beginTransaction();

        $query = "DELETE FROM payments WHERE record_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);

        // Delete items jika ada
        $query = "DELETE FROM items WHERE record_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);

        // Delete record
        $query = "DELETE FROM records WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error deleting record: " . $e->getMessage());
        return false;
    }
}

function getStatusClass($status)
{
    return $status === 'lunas' ? 'badge bg-success' : 'badge bg-warning';
}

function hitungPembayaran($record)
{
    $totalDibayar = $record['total_dibayar'] ?? 0;
    $sisaBayar = $record['amount'] - $totalDibayar;

    return [
        'total_dibayar' => $totalDibayar,
        'sisa_bayar' => max(0, $sisaBayar)
    ];
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $id = $_POST['id'] ?? '';
        $newStatus = $_POST['status'] ?? '';

        if (updateRecordStatus($id, $newStatus)) {
            $_SESSION['success'] = 'Status berhasil diubah!';
        } else {
            $_SESSION['error'] = 'Gagal mengubah status!';
        }

    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';

        if (deleteRecord($id)) {
            $_SESSION['success'] = 'Data berhasil dihapus!';
        } else {
            $_SESSION['error'] = 'Gagal menghapus data!';
        }
    }


    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($_GET));
    exit;
}

$filterStatus = $_GET['status'] ?? 'all';
$searchTerm = $_GET['search'] ?? '';

$piutangRecords = loadPiutangData($filterStatus, $searchTerm);

// Hitung statistik
$totalPiutang = 0;
$jumlahPiutang = count($piutangRecords);
$piutangBelumLunas = 0;

foreach ($piutangRecords as $record) {
    $pembayaran = hitungPembayaran($record);
    if ($record['status'] === 'belum lunas') {
        $totalPiutang += $pembayaran['sisa_bayar'];
        $piutangBelumLunas++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Piutang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stats-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 5px solid #007bff;
            height: 100%;
        }

        .stats-card h2 {
            color: #007bff;
            font-weight: 700;
            margin: 1rem 0;
        }

        .stats-card h5 {
            color: #495057;
            font-weight: 600;
        }

        .piutang-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.2s;
        }

        .piutang-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .payment-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin: 1rem 0;
        }

        .total-dibayar {
            color: #28a745;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .sisa-bayar {
            color: #dc3545;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .btn-group-vertical .btn {
            margin-bottom: 0.5rem;
            border-radius: 8px;
        }

        .card-title {
            color: #2c3e50;
            font-weight: 600;
        }

        .badge {
            font-size: 0.8rem;
            padding: 0.5rem 0.8rem;
        }
    </style>
</head>

<body>
    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-dark"><i class="fas fa-money-bill-wave me-2"></i> Kelola Piutang</h1>
            <div>
                <a href="/?q=piutang_tambahpiutang" class="btn btn-primary me-2">
                    <i class="fas fa-plus"></i> Tambah Data
                </a>
                <a href="/?q=piutang" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
            </div>
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

        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="stats-card" style="border-left-color: #dc3545;">
                    <h5><i class="fas fa-money-bill-wave me-2"></i> Total Piutang</h5>
                    <h2><?= formatRupiah($totalPiutang) ?></h2>
                    <small class="text-muted">Belum Lunas</small>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stats-card" style="border-left-color: #28a745;">
                    <h5><i class="fas fa-list me-2"></i> Jumlah Data</h5>
                    <h2><?= $jumlahPiutang ?></h2>
                    <small class="text-muted">Total Record</small>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stats-card" style="border-left-color: #ffc107;">
                    <h5><i class="fas fa-clock me-2"></i> Belum Lunas</h5>
                    <h2><?= $piutangBelumLunas ?></h2>
                    <small class="text-muted">Perlu Penagihan</small>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i> Filter Data</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Cari Nama</label>
                        <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan nama..."
                            value="<?= htmlspecialchars($searchTerm) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Semua Status</option>
                            <option value="belum_lunas" <?= $filterStatus === 'belum_lunas' ? 'selected' : '' ?>>Belum
                                lunas</option>
                            <option value="lunas" <?= $filterStatus === 'lunas' ? 'selected' : '' ?>>Lunas</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i> Daftar Piutang
                    <span class="badge bg-light text-primary"><?= count($piutangRecords) ?> data</span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($piutangRecords)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-4x mb-3"></i>
                        <h5>Tidak ada data piutang yang ditemukan</h5>
                        <p class="mb-4">Silakan tambah data piutang baru</p>
                        <a href="/?q=piutang_tambahpiutang" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i> Tambah Piutang
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($piutangRecords as $record): ?>
                            <?php
                            $pembayaran = hitungPembayaran($record);
                            $totalDibayar = $pembayaran['total_dibayar'];
                            $sisaBayar = $pembayaran['sisa_bayar'];
                            ?>
                            <div class="col-12 mb-3">
                                <div class="card piutang-card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-9">
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="<?= getStatusClass($record['status']) ?> me-2">
                                                        <i
                                                            class="fas fa-<?= $record['status'] === 'lunas' ? 'check' : 'clock' ?> me-1"></i>
                                                        <?= ucwords($record['status']) ?>
                                                    </span>
                                                    <?php if ($totalDibayar > 0): ?>
                                                        <span class="badge bg-info me-2">
                                                            <i class="fas fa-receipt me-1"></i>
                                                            <?= count($record['pembayaran'] ?? []) ?>x Bayar
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (strtotime($record['dueDate']) < time() && $record['status'] === 'belum lunas'): ?>
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                                            Jatuh Tempo
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <h5 class="card-title"><?= htmlspecialchars($record['name']) ?></h5>
                                                <h3 class="text-primary mb-3"><?= formatRupiah($record['amount']) ?></h3>

                                                <div class="payment-info">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <strong>Total Dibayar:</strong><br>
                                                            <span
                                                                class="total-dibayar"><?= formatRupiah($totalDibayar) ?></span>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <strong>Sisa Bayar:</strong><br>
                                                            <span class="sisa-bayar"><?= formatRupiah($sisaBayar) ?></span>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <strong>Progress:</strong><br>
                                                            <div class="progress" style="height: 10px;">
                                                                <div class="progress-bar <?= $record['status'] === 'lunas' ? 'bg-success' : 'bg-warning' ?>"
                                                                    style="width: <?= min(100, ($totalDibayar / $record['amount']) * 100) ?>%">
                                                                </div>
                                                            </div>
                                                            <small class="text-muted">
                                                                <?= number_format(min(100, ($totalDibayar / $record['amount']) * 100), 1) ?>%
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row text-muted mt-3">
                                                    <div class="col-md-3">
                                                        <strong><i class="fas fa-calendar-day me-1"></i>Jatuh
                                                            Tempo:</strong><br>
                                                        <?= formatTanggal($record['dueDate']) ?>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <strong><i class="fas fa-wallet me-1"></i>Metode:</strong><br>
                                                        <?= htmlspecialchars($record['paymentMethod'] ?: '-') ?>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <strong><i class="fas fa-sticky-note me-1"></i>Keterangan:</strong><br>
                                                        <?= htmlspecialchars($record['description'] ?: '-') ?>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <strong><i class="fas fa-calendar-plus me-1"></i>Dibuat:</strong><br>
                                                        <small><?= formatTanggal($record['createdAt']) ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="btn-group-vertical w-100">
                                                    <?php if ($record['status'] === 'belum lunas' && $sisaBayar > 0): ?>
                                                        <a href="/?q=piutang_bayarpiutang&id=<?= $record['id'] ?>&type=piutang"
                                                            class="btn btn-primary btn-sm">
                                                            <i class="fas fa-credit-card me-1"></i> Bayar
                                                        </a>
                                                    <?php endif; ?>

                                                    <form method="POST" class="mb-2">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="id" value="<?= $record['id'] ?>">
                                                        <input type="hidden" name="status"
                                                            value="<?= $record['status'] === 'lunas' ? 'belum lunas' : 'lunas' ?>">
                                                        <button type="submit"
                                                            class="btn <?= $record['status'] === 'lunas' ? 'btn-outline-warning' : 'btn-success' ?> btn-sm w-100">
                                                            <i
                                                                class="fas fa-<?= $record['status'] === 'lunas' ? 'clock' : 'check' ?> me-1"></i>
                                                            <?= $record['status'] === 'lunas' ? 'Tandai Belum Lunas' : 'Tandai Lunas' ?>
                                                        </button>
                                                    </form>

                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $record['id'] ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm w-100"
                                                            onclick="return confirmDelete('<?= htmlspecialchars($record['name']) ?>')">
                                                            <i class="fas fa-trash me-1"></i> Hapus
                                                        </button>
                                                    </form>

                                                    <a href="/?q=piutang_bayarpiutang&id=<?= $record['id'] ?>&type=piutang"
                                                        class="btn btn-outline-info btn-sm mt-2">
                                                        <i class="fas fa-info-circle me-1"></i> Detail
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal untuk detail -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Piutang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <!-- Content akan diisi oleh JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(nama) {
            return confirm(`Yakin ingin menghapus data piutang "${nama}"?\\n\\nData yang dihapus tidak dapat dikembalikan!`);
        }

        function showDetail(id) {
            window.location.href = `/?q=piutang_bayarpiutang?id=${id}&type=piutang`;
        }

        // Auto-hide alerts setelah 5 detik
        document.addEventListener('DOMContentLoaded', function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        // Search real-time (jika diinginkan)
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.form.submit();
                }, 1000);
            });
        }
    </script>
</body>

</html>