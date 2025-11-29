<?php
require_once '../Database/config.php';
require_once '../Database/functions_piutang.php';

function getDashboardStats()
{
    $database = db_kantin();
    $db = $database->pdo;

    $stats = [
        'total_piutang' => 0,
        'piutang_belum_lunas' => 0,
        'total_records' => 0
    ];

    try {
        // Total Piutang Belum Lunas - PERBAIKAN QUERY
        $query = "SELECT r.id, r.amount, COALESCE(SUM(p.jumlah), 0) as total_dibayar
                  FROM records r 
                  LEFT JOIN payments p ON r.id = p.record_id 
                  WHERE r.type = 'piutang' AND r.status = 'belum lunas'
                  GROUP BY r.id";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $row) {
            $sisa_bayar = $row['amount'] - $row['total_dibayar'];
            if ($sisa_bayar > 0) {
                $stats['total_piutang'] += $sisa_bayar;
            }
        }

        // Count records piutang
        $query = "SELECT COUNT(*) as total FROM records WHERE type = 'piutang'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $countResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_records'] = $countResult['total'];

        // Count piutang belum lunas
        $query = "SELECT COUNT(*) as count FROM records WHERE type = 'piutang' AND status = 'belum lunas'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $countResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['piutang_belum_lunas'] = $countResult['count'];

    } catch (Exception $e) {
        error_log("Error getting dashboard stats: " . $e->getMessage());
    }

    return $stats;
}

$stats = getDashboardStats();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sistem Piutang</title>
    <style>
        .dashboard-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            height: 100%;
            transition: transform 0.3s;
            border: 1px solid #e9ecef;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .piutang-card {
            border-top: 5px solid #dc3545;
        }

        .add-card {
            border-top: 5px solid #28a745;
        }

        .card-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .piutang-card .card-icon {
            color: #dc3545;
        }

        .add-card .card-icon {
            color: #28a745;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 1rem;
        }

        .card-amount {
            font-size: 2rem;
            font-weight: 700;
            margin: 1rem 0;
        }

        .piutang-card .card-amount {
            color: #dc3545;
        }

        .btn-dashboard {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-dashboard:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: white;
            transform: scale(1.05);
        }

        .stats-badge {
            font-size: 0.8rem;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
        }
    </style>
</head>

<body>
    <div class="container my-5">
        <div class="mb-4">
            <a href="/?q=menu" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Penjualan
            </a>
        </div>

        <h1 class="text-center mb-5 text-dark">
            <i class="fas fa-chart-line me-3"></i>Sistem Pencatatan Piutang
        </h1>

        <div class="row justify-content-center">
            <div class="col-md-6 mb-4">
                <div class="dashboard-card piutang-card">
                    <div class="card-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="card-title">Total Piutang</div>
                    <div class="card-amount"><?= formatRupiah($stats['total_piutang']) ?></div>
                    <span class="badge bg-danger stats-badge">
                        <?= $stats['piutang_belum_lunas'] ?> Belum Lunas
                    </span>
                    <p class="my-3 text-muted">Piutang yang belum lunas</p>
                    <a href="/?q=piutang_hasilpiutang" class="btn btn-dashboard">
                        <i class="fas fa-arrow-right me-2"></i> Kelola Piutang
                    </a>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="dashboard-card add-card">
                    <div class="card-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="card-title">Tambah Data Baru</div>
                    <div class="card-amount"><?= $stats['total_records'] ?></div>
                    <span class="badge bg-primary stats-badge">
                        Total Data Piutang
                    </span>
                    <p class="my-3 text-muted">Tambah data piutang baru</p>
                    <a href="/?q=piutang_tambahpiutang" class="btn btn-dashboard">
                        <i class="fas fa-plus me-2"></i> Tambah Data
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cards = document.querySelectorAll('.dashboard-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';

                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
    </script>
</body>

</html>