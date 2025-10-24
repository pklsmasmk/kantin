<?php
require "../Database/config.php";

$data = [];

$sql = "SELECT * FROM penjualan ORDER BY id DESC";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
  $id = $row['id'];
  $detail = [];

  $qDetail = $pdo->prepare("SELECT nama_item AS nama, qty, harga FROM detail_penjualan WHERE id_penjualan = ?");
  $qDetail->execute([$id]);
  $detail = $qDetail->fetchAll(PDO::FETCH_ASSOC);

  $row['nama'] = $row['nama_pembeli'] ?? 'Umum';
  $row['items'] = $detail;
  $data[] = $row;
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Laporan Penjualan - Kantin UAM</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../public/css/laporanpen.css">
</head>

<body>
  <nav class="navbar navbar-light bg-white shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold text-success" href="laporan.php"><i class="bi bi-shop"></i> Kantin UAM</a>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="laporan.php"><i class="bi bi-arrow-left"></i> Kembali</a>
      </div>
    </div>
  </nav>

  <div class="container-fluid mt-4">
    <div class="row gx-4">
      <div class="col-lg-4 col-md-5 mb-4">
        <div class="sidebar p-3">
          <h5 class="fw-bold mb-3">Laporan Data Transaksi</h5>
          <div id="listContainer" class="list-group">
            <?php if (empty($data)): ?>
              <div class="text-center text-muted small">Belum ada transaksi</div>
            <?php else: ?>
              <?php foreach ($data as $i => $row):
                $name = htmlspecialchars($row['nama'] ?? 'Umum');
                $total = number_format($row['total'] ?? 0, 0, ',', '.');
                $tgl = htmlspecialchars($row['tanggal'] ?? '');
                $status = htmlspecialchars($row['status'] ?? 'Lunas');
                ?>
                <button type="button"
                  class="list-group-item list-group-item-action d-flex justify-content-between align-items-start trx-item"
                  data-index="<?= $i ?>" data-name="<?= $name ?>" data-tanggal="<?= $tgl ?>"
                  data-total="<?= $row['total'] ?? 0 ?>" data-metode="<?= htmlspecialchars($row['metode'] ?? '-') ?>"
                  data-status="<?= $status ?>" data-keterangan="<?= htmlspecialchars($row['keterangan'] ?? '-') ?>"
                  data-uangmasuk="<?= $row['uang_masuk'] ?? 0 ?>" data-kembalian="<?= $row['kembalian'] ?? 0 ?>"
                  data-diskon="<?= $row['diskon'] ?? 0 ?>" data-pajak="<?= $row['pajak'] ?? 0 ?>">
                  <div class="ms-2 me-auto text-start">
                    <div class="fw-semibold"><?= $name ?></div>
                    <div class="small-muted"><?= $tgl ?></div>
                  </div>
                  <div class="text-end">
                    <div class="fw-bold text-success">Rp <?= $total ?></div>
                    <div class="small-muted">
                      <?= $status === 'Lunas'
                        ? '<span class="small text-success">Lunas</span>'
                        : '<span class="badge bg-warning text-dark">Piutang</span>' ?>
                    </div>
                  </div>
                </button>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-8 col-md-7">
        <div class="receipt-card" id="receiptArea">
          <div id="emptyState" class="text-center text-muted py-5">
            <i class="bi bi-receipt fs-1 text-success"></i>
            <h5 class="mt-3">Pilih transaksi di kiri untuk melihat struk</h5>
            <p class="small-muted">Klik salah satu transaksi untuk menampilkan struk penjualan.</p>
          </div>

          <div id="receiptContent" style="display:none;">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <h5 class="fw-bold mb-0">Kantin UAM</h5>
                <div class="small-muted">Jl By Pass Krian Km 33</div>
              </div>
              <div class="text-end small-muted">
                <div id="rTanggal"></div>
                <div id="rMetode"></div>
              </div>
            </div>
            <hr>
            <div id="itemsList" class="mb-3"></div>
            <hr>
            <div class="d-flex justify-content-between">
              <div class="small-muted">Subtotal</div>
              <div id="rSubtotal" class="fw-semibold"></div>
            </div>
            <div class="d-flex justify-content-between">
              <div class="small-muted">Diskon</div>
              <div id="rDiscount" class="fw-semibold">Rp 0</div>
            </div>
            <div class="d-flex justify-content-between">
              <div class="small-muted">Pajak</div>
              <div id="rTax" class="fw-semibold">Rp 0</div>
            </div>
            <div class="d-flex justify-content-between fs-5 fw-bold mt-2">
              <div>Total</div>
              <div id="rTotal" class="text-success"></div>
            </div>
            <div class="d-flex justify-content-between mt-2">
              <div class="small-muted">Uang Masuk</div>
              <div id="rUangMasuk" class="fw-semibold">Rp 0</div>
            </div>
            <div class="d-flex justify-content-between">
              <div class="small-muted">Kembalian</div>
              <div id="rKembalian" class="fw-semibold">Rp 0</div>
            </div>
            <div class="mt-3 small-muted" id="rKeterangan"></div>
            <div class="mt-4 d-flex gap-2">
              <button id="viewFullBtn" class="btn btn-success ms-auto">
                <i class="bi bi-eye"></i> View Full
              </button>
            </div>
          </div>
        </div>

        <div class="modal fade" id="modalFull" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Struk Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body" id="modalBody"></div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>const transactions = <?= json_encode($data ?: []); ?>;</script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../public/js/laporanpen.js"></script>
</body>
</html>
