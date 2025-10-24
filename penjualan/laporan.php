<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan - Kantin UAM</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../public/css/laporan.css">
</head>

<body>
  <nav class="navbar navbar-dark shadow-sm fixed-top">
    <div class="container-fluid">
      <a class="navbar-brand fw-semibold text-white" href="../index.php">
        <i class="bi bi-arrow-left-circle me-2"></i>Kembali ke Kasir
      </a>
      <span class="text-white fw-semibold">Laporan Kantin UAM</span>
    </div>
  </nav>

  <main class="container">
    <div class="page-header">
      <h3><i class="bi bi-bar-chart-line me-2 text-primary"></i>Laporan</h3>
      <p>Pilih jenis laporan yang ingin Anda lihat</p>
    </div>

    <div class="row justify-content-center g-4 mt-4">
      <div class="col-md-4 col-sm-6">
        <a href="laporan_penjualan.php" class="text-decoration-none">
          <div class="card-option text-center p-4">
            <div class="icon-box bg-primary-light">
              <i class="bi bi-receipt-cutoff"></i>
            </div>
            <h5>Laporan Penjualan</h5>
            <p>Lihat semua transaksi penjualan harian dan total pendapatan.</p>
          </div>
        </a>
      </div>
  </main>

  <footer>
    © 2025 Kantin UAM
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
