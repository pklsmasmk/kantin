<?php
if (isset($_SESSION['username'])) {

  ?>
  <div class="row g-4">
    <div class="col-md-8">
      <div class="card p-4 border-0 shadow-sm" data-aos="fade-up">
        <h4 class="mb-3 text-dark fw-bold"><i class="bi bi-list-ul me-2 text-primary"></i>Daftar Menu</h4>
        <input type="text" id="searchMenu" class="form-control mb-4 search-input shadow-sm"
          placeholder=" Cari makanan, minuman, atau jajanan..." autocomplete="off">

        <div class="menu-horizontal-container" id="menuList">

          <?php
          $strSql = "SELECT * FROM stok_barang";
          $hsl = $pdo->query($strSql);
          $iTung = 0;
          foreach ($hsl as $v) {
            ?>
            <div class="menu-horizontal-item" data-aos="fade-up" data-aos-delay="100">
              <div class="item-number"><?= ++$iTung ?></div>
              <div class="card h-100 border-0 shadow-sm menu-card">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                  <div class="d-flex align-items-center flex-grow-1">
                    <?php if (!empty($v["foto"]) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $v["foto"])): ?>
                      <img src="/uploads/<?= $v["foto"] ?>" class="menu-img-small me-3" alt="<?= $v["nama_barang"] ?>" 
                           style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                    <?php else: ?>
                      <div class="menu-icon-small me-3 d-flex align-items-center justify-content-center" 
                           style="width: 50px; height: 50px; border-radius: 8px; background-color: #f8f9fa;">
                        <i class="bi bi-box text-success fs-4"></i>
                      </div>
                    <?php endif; ?>
                    <div class="menu-info">
                      <h6 class="card-title fw-bold mb-1"><?= $v["nama_barang"] ?></h6>
                      <p class="card-text text-muted mb-0">Rp <?= number_format($v["harga_jual"], 0, ",", ".") ?>,00</p>
                      <p class="card-text text-muted mb-0 small">
                        Stok: <span class="fw-bold <?= $v["stok"] > 0 ? 'text-success' : 'text-danger' ?>">
                          <?= $v["stok"] ?>
                        </span>
                      </p>
                    </div>
                  </div>
                  <div class="menu-action">
                    <button class="btn btn-success addCart btn-sm" data-id="<?= $v["id"] ?>"
                      data-nama="<?= $v["nama_barang"] ?>" data-harga="<?= $v["harga_jual"] ?>"
                      data-stok="<?= $v["stok"] ?>" <?= $v["stok"] <= 0 ? 'disabled' : '' ?>>
                      <i class="bi bi-plus-circle me-1"></i>
                      <?= $v["stok"] > 0 ? 'Tambah' : 'Habis' ?>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <?php
          }
          ?>

        </div>
      </div>
    </div>

    <div class="col-md-4 clkeranjang">
      <div class="card p-3 border-0 shadow-sm sticky-top" style="top: 80px;" data-aos="fade-left">
        <h4 class="mb-3 text-dark fw-bold"><i class="bi bi-cart4 me-2 text-success"></i>Keranjang</h4>

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
      </div>
    </div>
  </div>

  <?php
} else {

  ?>
  <div class="container-fluid d-flex align-items-center justify-content-center" style="min-height: 80vh;">
    <div class="row w-100 justify-content-center">
      <div class="col-11 col-sm-8 col-md-5 col-lg-4 col-xl-3">
        <div class="card shadow border-0 rounded-3" data-aos="fade-up">
          <div class="card-body p-4 p-md-4">
            <div class="text-center mb-3">
              <div
                class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                style="width: 60px; height: 60px;">
                <i class="bi bi-lock-fill text-success fs-5"></i>
              </div>
              <h5 class="fw-bold text-dark mb-1">Akses Dibatasi</h5>
            </div>

            <div class="alert alert-warning border-0 rounded-2 mb-3 py-2" role="alert">
              <div class="d-flex align-items-center justify-content-center">
                <i class="bi bi-exclamation-triangle-fill text-warning me-2 small"></i>
                <span class="small">Anda perlu login untuk melanjutkan</span>
              </div>
            </div> 

            <div class="d-grid">
              <a href="/?q=login" class="btn btn-success fw-semibold py-2 rounded-2">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php
}  