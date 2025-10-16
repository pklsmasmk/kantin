<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan - Kantin UAM</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: "Poppins", sans-serif;
    }
    .card {
      transition: 0.3s;
      border-radius: 1rem;
    }
    .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
    }
    .icon-box {
      width: 60px;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-dark bg-dark shadow-sm fixed-top">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold text-white" href="index.php">
        <i class="bi bi-arrow-left-circle me-2"></i>Kembali ke Kasir
      </a>
      <span class="text-white fw-semibold">Laporan Kantin UAM</span>
    </div>
  </nav>

  <main class="container mt-5 pt-5">
    <div class="text-center mb-4">
      <h3 class="fw-bold text-dark"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Laporan</h3>
      <p class="text-muted">Pilih jenis laporan yang ingin dilihat</p>
    </div>

    <div class="row justify-content-center g-4">
      <div class="col-md-4">
        <a href="laporan_penjualan.php" class="text-decoration-none text-dark">
          <div class="card border-0 shadow-sm p-4 text-center">
            <div class="icon-box bg-primary bg-opacity-10 text-primary mx-auto mb-3">
              <i class="bi bi-receipt-cutoff fs-2"></i>
            </div>
            <h5 class="fw-bold">Laporan Transaksi Penjualan</h5>
            <p class="text-muted small mb-0">Lihat semua transaksi penjualan harian</p>
          </div>
        </a>
      </div>
  </main>

  <div style="height: 80px;"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
