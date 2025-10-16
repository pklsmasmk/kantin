<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kantin UAM - Kasir</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
</head>

<body class="bg-light">
  <nav class="navbar navbar-dark bg-dark shadow-sm fixed-top">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center">
        <button class="btn btn-outline-light me-2" type="button" data-bs-toggle="offcanvas"
          data-bs-target="#sidebarMenu">
          <i class="bi bi-list"></i>
        </button>
        <a class="navbar-brand fw-bold text-white" href="#">
          <i class="bi bi-shop me-1 text-info"></i>Kantin<small class="text-info fst-italic">UAM</small>
        </a>
      </div>
      <div>
        <button class="btn btn-outline-light position-relative">
          <i class="bi bi-cart3 fs-5"></i>
          <span id="cartCount"
            class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle">0</span>
        </button>
      </div>
    </div>
  </nav>

  <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu">
    <div class="offcanvas-header bg-dark text-white">
      <h5 class="offcanvas-title fw-bold"><i class="bi bi-grid me-1"></i>Menu Utama</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
      <nav class="nav flex-column">
        <a class="nav-link px-3" href="#"><i class="bi bi-house me-2"></i>Beranda</a>
        <a class="nav-link px-3" href="index.php"><i class="bi bi-receipt me-2"></i>Transaksi Penjualan</a>
        <a class="nav-link px-3" href="#"><i class="bi bi-people me-2"></i>Manajemen</a>
        <a class="nav-link px-3" href="#"><i class="bi bi-clock-history me-2"></i>Shift</a>
        <a class="nav-link px-3" href="#"><i class="bi bi-basket me-2"></i>Barang / Jasa</a>
        <a class="nav-link px-3" href="#"><i class="bi bi-cash-stack me-2"></i>Keuangan</a>
        <a class="nav-link px-3" href="laporan.php"><i class="bi bi-graph-up me-2"></i>Laporan</a>
        <a class="nav-link px-3" href="piutangkantin/index.php"><i class="bi bi-journal-text me-2"></i>Daftar Piutang</a>
        <a class="nav-link px-3" href="#"><i class="bi bi-box-seam me-2"></i>Penitipan Barang</a>
        <a class="nav-link px-3" href="#"><i class="bi bi-bag-check me-2"></i>Pembelian Barang</a>
      </nav>
    </div>
  </div>

  <main class="container-fluid mt-5 pt-3">
    <div class="row g-4">
      <div class="col-md-8">
        <div class="card p-4 border-0 shadow-sm" data-aos="fade-up">
          <h4 class="mb-3 text-dark fw-bold"><i class="bi bi-list-ul me-2 text-primary"></i>Daftar Menu</h4>
          <input type="text" id="searchMenu" class="form-control mb-4 search-input shadow-sm"
            placeholder=" Cari makanan, minuman, atau jajanan...">
          <div class="row row-cols-2 row-cols-lg-4 g-3" id="menuList">

            <div class="col" data-aos="fade-up" data-aos-delay="100">
              <div class="card h-100 text-center border-0 shadow-sm menu-card">
                <img src="img/foto1.avif" class="card-img-top menu-img" alt="Nasi Goreng">
                <div class="card-body d-flex flex-column">
                  <h6 class="card-title fw-bold mb-1">Nasi Goreng</h6>
                  <p class="card-text text-muted mb-2">Rp15.000</p>
                  <div class="mt-auto">
                    <button class="btn btn-success addCart w-100"><i class="bi bi-plus-circle me-1"></i>Tambah</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="col" data-aos="fade-up" data-aos-delay="150">
              <div class="card h-100 text-center border-0 shadow-sm menu-card">
                <img src="img/foto2.avif" class="card-img-top menu-img" alt="Mie Ayam">
                <div class="card-body d-flex flex-column">
                  <h6 class="card-title fw-bold mb-1">Mie Ayam</h6>
                  <p class="card-text text-muted mb-2">Rp12.000</p>
                  <div class="mt-auto">
                    <button class="btn btn-success addCart w-100"><i class="bi bi-plus-circle me-1"></i>Tambah</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="col" data-aos="fade-up" data-aos-delay="200">
              <div class="card h-100 text-center border-0 shadow-sm menu-card">
                <img src="img/foto3.avif" class="card-img-top menu-img" alt="Es Teh Manis">
                <div class="card-body d-flex flex-column">
                  <h6 class="card-title fw-bold mb-1">Es Teh Manis</h6>
                  <p class="card-text text-muted mb-2">Rp5.000</p>
                  <div class="mt-auto">
                    <button class="btn btn-success addCart w-100"><i class="bi bi-plus-circle me-1"></i>Tambah</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="col" data-aos="fade-up" data-aos-delay="250">
              <div class="card h-100 text-center border-0 shadow-sm menu-card">
                <img src="img/foto4.avif" class="card-img-top menu-img" alt="Ayam Geprek">
                <div class="card-body d-flex flex-column">
                  <h6 class="card-title fw-bold mb-1">Ayam Geprek</h6>
                  <p class="card-text text-muted mb-2">Rp15.000</p>
                  <div class="mt-auto">
                    <button class="btn btn-success addCart w-100"><i class="bi bi-plus-circle me-1"></i>Tambah</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="col" data-aos="fade-up" data-aos-delay="300">
              <div class="card h-100 text-center border-0 shadow-sm menu-card">
                <img src="img/foto5.avif" class="card-img-top menu-img" alt="Bakso">
                <div class="card-body d-flex flex-column">
                  <h6 class="card-title fw-bold mb-1">Bakso</h6>
                  <p class="card-text text-muted mb-2">Rp10.000</p>
                  <div class="mt-auto">
                    <button class="btn btn-success addCart w-100"><i class="bi bi-plus-circle me-1"></i>Tambah</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="col" data-aos="fade-up" data-aos-delay="350">
              <div class="card h-100 text-center border-0 shadow-sm menu-card">
                <img src="img/foto6.avif" class="card-img-top menu-img" alt="Sate Ayam">
                <div class="card-body d-flex flex-column">
                  <h6 class="card-title fw-bold mb-1">Sate Ayam</h6>
                  <p class="card-text text-muted mb-2">Rp10.000</p>
                  <div class="mt-auto">
                    <button class="btn btn-success addCart w-100"><i class="bi bi-plus-circle me-1"></i>Tambah</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="col" data-aos="fade-up" data-aos-delay="400">
              <div class="card h-100 text-center border-0 shadow-sm menu-card">
                <img src="img/foto7.avif" class="card-img-top menu-img" alt="Es Jeruk">
                <div class="card-body d-flex flex-column">
                  <h6 class="card-title fw-bold mb-1">Es Jeruk</h6>
                  <p class="card-text text-muted mb-2">Rp6.000</p>
                  <div class="mt-auto">
                    <button class="btn btn-success addCart w-100"><i class="bi bi-plus-circle me-1"></i>Tambah</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="col" data-aos="fade-up" data-aos-delay="450">
              <div class="card h-100 text-center border-0 shadow-sm menu-card">
                <img src="img/foto8.avif" class="card-img-top menu-img" alt="Roti Bakar">
                <div class="card-body d-flex flex-column">
                  <h6 class="card-title fw-bold mb-1">Roti Bakar</h6>
                  <p class="card-text text-muted mb-2">Rp14.000</p>
                  <div class="mt-auto">
                    <button class="btn btn-success addCart w-100"><i class="bi bi-plus-circle me-1"></i>Tambah</button>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-3 border-0 shadow-sm sticky-top" style="top: 80px;" data-aos="fade-left">
          <h4 class="mb-3 text-dark fw-bold"><i class="bi bi-cart4 me-2 text-primary"></i>Keranjang</h4>

          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Item</th>
                  <th class="text-center">Jumlah</th>
                  <th class="text-end">Harga</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="cartList"></tbody>
            </table>
          </div>

          <hr class="my-2">

          <div class="mb-2">
            <label for="discount" class="form-label fw-bold small">Diskon (%)</label>
            <input type="number" id="discount" class="form-control form-control-sm shadow-sm" placeholder="0">
          </div>

          <div class="mb-2">
            <label for="tax" class="form-label fw-bold small">Pajak (%)</label>
            <input type="number" id="tax" class="form-control form-control-sm shadow-sm" placeholder="0">
          </div>

          <hr class="my-2">

          <div class="d-flex justify-content-between mb-1 fw-semibold">
            <span>Subtotal</span>
            <span>Rp<span id="cartSubtotal">0</span></span>
          </div>
          <div class="d-flex justify-content-between mb-1 fw-semibold text-success">
            <span>Diskon</span>
            <span>- Rp<span id="cartDiscount">0</span></span>
          </div>
          <div class="d-flex justify-content-between mb-3 fw-semibold text-danger">
            <span>Pajak</span>
            <span>Rp<span id="cartTax">0</span></span>
          </div>
          <div class="d-flex justify-content-between mb-3 fw-bold fs-5">
            <span>Total</span>
            <span>Rp<span id="cartTotal">0</span></span>
          </div>

          <button id="payBtn" class="btn btn-success w-100 fw-bold mb-2">
            <i class="bi bi-cash-coin me-1"></i>Bayar
          </button>
          <button id="finishBtn" class="btn btn-secondary w-100 fw-bold" style="display:none;">
            <i class="bi bi-check-circle me-1"></i>Selesai Transaksi
          </button>
        </div>
      </div>
    </div>
  </main>

  <div style="height: 50px;"></div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script>AOS.init({ duration: 700, once: true });</script>
  <script src="script.js"></script>
</body>

</html>