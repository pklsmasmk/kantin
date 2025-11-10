<?php

function loadData()
{
    $file = 'data.json';
    if (file_exists($file)) {
        $json = file_get_contents($file);
        return json_decode($json, true) ?: [];
    }
    return [];
}

function formatRupiah($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

$records = loadData();

$totalHutang = array_reduce(array_filter($records, function ($r) {
    return $r['type'] === 'hutang' && $r['status'] === 'belum lunas';
}), function ($sum, $r) {
    return $sum + $r['amount'];
}, 0);

$totalPiutang = array_reduce(array_filter($records, function ($r) {
    return $r['type'] === 'piutang' && $r['status'] === 'belum lunas';
}), function ($sum, $r) {
    return $sum + $r['amount'];
}, 0);
?>

<div class="container my-5">
    <div class="mb-4">
        <a href="/?q=menu" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Penjualan
        </a>
    </div>
    <div class="container my-5">
        <h1 class="text-center mb-5 text-dark">Sistem Pencatatan Piutang</h1>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="dashboard-card piutang-card">
                    <div class="card-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="card-title">Total Piutang</div>
                    <div class="card-amount"><?= formatRupiah($totalPiutang) ?></div>
                    <p class="mb-4">Piutang yang belum lunas</p>
                    <a href="/?q=piutang_data" class="btn btn-dashboard">
                        <i class="fas fa-arrow-right"></i> Kelola Piutang
                    </a>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="dashboard-card add-card">
                    <div class="card-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="card-title">Tambah Data Baru</div>
                    <p class="mb-4">Tambah data hutang atau piutang baru</p>
                    <a href="/?q=piutang_tambah" class="btn btn-dashboard">
                        <i class="fas fa-plus"></i> Tambah Data
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>