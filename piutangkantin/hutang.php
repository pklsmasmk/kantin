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

function getStatusClass($status)
{
    return $status === 'lunas' ? 'badge bg-success' : 'badge bg-danger';
}

function hitungPembayaran($record)
{
    $totalDibayar = 0;
    if (isset($record['pembayaran']) && is_array($record['pembayaran'])) {
        $totalDibayar = array_reduce($record['pembayaran'], function ($sum, $bayar) {
            return $sum + $bayar['jumlah'];
        }, 0);
    }

    $sisaBayar = $record['amount'] - $totalDibayar;

    return [
        'total_dibayar' => $totalDibayar,
        'sisa_bayar' => $sisaBayar
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $records = loadData();

    if ($action === 'update_status') {
        $id = $_POST['id'] ?? '';
        $newStatus = $_POST['status'] ?? '';
        foreach ($records as &$record) {
            if ($record['id'] == $id) {
                $record['status'] = $newStatus;
                break;
            }
        }
        saveData($records);
        $_SESSION['success'] = 'Status berhasil diubah!';
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        $records = array_filter($records, function ($record) use ($id) {
            return $record['id'] != $id;
        });
        $records = array_values($records);
        saveData($records);
        $_SESSION['success'] = 'Data berhasil dihapus!';
    }

    header('Location: hutang.php');
    exit;
}

$filterStatus = $_GET['status'] ?? 'all';
$searchTerm = $_GET['search'] ?? '';

$records = loadData();

$hutangRecords = array_filter($records, function ($record) use ($filterStatus, $searchTerm) {
    $isHutang = $record['type'] === 'hutang';
    $matchesStatus = $filterStatus === 'all' || $record['status'] === str_replace('_', ' ', $filterStatus);
    $matchesSearch = empty($searchTerm) || stripos($record['name'], $searchTerm) !== false;

    return $isHutang && $matchesStatus && $matchesSearch;
});

$hutangRecords = array_values($hutangRecords);

$totalHutang = 0;
$jumlahHutang = count($hutangRecords);
$hutangBelumLunas = 0;

foreach ($hutangRecords as $record) {
    $pembayaran = hitungPembayaran($record);
    if ($record['status'] === 'belum lunas') {
        $totalHutang += $pembayaran['sisa_bayar'];
        $hutangBelumLunas++;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Hutang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/hutang.css">
</head>

<body>
    <div class="container my-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-dark"><i class="fas fa-hand-holding-usd me-2"></i> Kelola Hutang</h1>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <h5><i class="fas fa-hand-holding-usd me-2"></i> Total Hutang</h5>
                    <h2><?= formatRupiah($totalHutang) ?></h2>
                    <small>Belum Lunas</small>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <h5><i class="fas fa-list me-2"></i> Jumlah Data</h5>
                    <h2><?= $jumlahHutang ?></h2>
                    <small>Total Record</small>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <h5><i class="fas fa-clock me-2"></i> Belum Lunas</h5>
                    <h2><?= $hutangBelumLunas ?></h2>
                    <small>Perlu Tindakan</small>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan nama..."
                            value="<?= htmlspecialchars($searchTerm) ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="status" class="form-select">
                            <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Semua Status</option>
                            <option value="belum_lunas" <?= $filterStatus === 'jatuh_tempo_terdekat' ? 'selected' : '' ?>>
                                Jatuh tempo terdekat</option>
                            <option value="belum_lunas" <?= $filterStatus === 'belum_lunas' ? 'selected' : '' ?>>Belum
                                lunas</option>
                            <option value="lunas" <?= $filterStatus === 'lunas' ? 'selected' : '' ?>>Lunas</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-warning w-100"><i class="fas fa-search"></i>
                            Filter</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> Daftar Hutang (<?= count($hutangRecords) ?> data)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($hutangRecords)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>Tidak ada data hutang yang ditemukan</p>
                        <a href="tambah.php" class="btn btn-warning">Tambah Hutang</a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($hutangRecords as $record): ?>
                            <?php
                            $pembayaran = hitungPembayaran($record);
                            $totalDibayar = $pembayaran['total_dibayar'];
                            $sisaBayar = $pembayaran['sisa_bayar'];
                            ?>
                            <div class="col-12 mb-3">
                                <div class="card hutang-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="<?= getStatusClass($record['status']) ?> me-2">
                                                        <?= ucwords($record['status']) ?>
                                                    </span>
                                                    <?php if ($totalDibayar > 0): ?>
                                                        <span class="badge bg-warning me-2">
                                                            <?= count($record['pembayaran'] ?? []) ?>x Bayar
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <h5 class="card-title text-dark"><?= htmlspecialchars($record['name']) ?></h5>
                                                <h3 class="text-primary mb-3"><?= formatRupiah($record['amount']) ?></h3>

                                                <!-- Informasi Pembayaran -->
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
                                                    </div>
                                                </div>

                                                <div class="row text-muted mt-3">
                                                    <div class="col-md-3">
                                                        <strong>Jatuh Tempo:</strong><br>
                                                        <?= formatTanggal($record['dueDate']) ?>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <strong>Metode:</strong><br>
                                                        <?= htmlspecialchars($record['paymentMethod'] ?: '-') ?>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <strong>Keterangan:</strong><br>
                                                        <?= htmlspecialchars($record['description'] ?: '-') ?>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <strong>Dibuat:</strong><br>
                                                        <small><?= formatTanggal($record['createdAt']) ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-3 mt-md-0 d-flex flex-column gap-2">
                                                <a href="bayar.php?id=<?= $record['id'] ?>&type=hutang"
                                                    class="btn btn-primary btn-sm">
                                                    <i class="fas fa-credit-card"></i> Bayar
                                                </a>
                                                <form method="POST" class="mb-0">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="id" value="<?= $record['id'] ?>">
                                                    <input type="hidden" name="status"
                                                        value="<?= $record['status'] === 'lunas' ? 'belum lunas' : 'lunas' ?>">
                                                    <button type="submit"
                                                        class="btn <?= $record['status'] === 'lunas' ? 'btn-outline-success' : 'btn-success' ?> btn-sm w-100">
                                                        <i class="fas fa-check"></i>
                                                        <?= $record['status'] === 'lunas' ? 'Belum Lunas' : 'Tandai Lunas' ?>
                                                    </button>
                                                </form>
                                                <form method="POST" class="mb-0">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $record['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm w-100"
                                                        onclick="return confirm('Yakin hapus data hutang ini?')">
                                                        <i class="fas fa-trash"></i> Hapus
                                                    </button>
                                                </form>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>