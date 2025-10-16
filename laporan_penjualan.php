<?php
$file = 'penjualan.json';
$data = [];

if (file_exists($file)) {
  $json = file_get_contents($file);
  $data = json_decode($json, true) ?: [];
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
  <style>
    body {
      background: #f5f7fa;
      font-family: "Poppins", sans-serif;
    }

    .sidebar {
      background: #fff;
      border-right: 1px solid #e9ecef;
      min-height: 70vh;
    }

    .sidebar .list-group-item {
      border: 0;
      border-bottom: 1px solid #f1f3f5;
    }

    .sidebar .list-group-item:hover {
      background: #f8fafb;
      cursor: pointer;
    }

    .selected {
      background: #eaf8ef !important;
      border-left: 4px solid #28a745 !important;
    }

    .receipt-card {
      background: #fff;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 18px rgba(0, 0, 0, .04);
    }

    .small-muted {
      color: #6c757d;
      font-size: .9rem;
    }

    .badge-piutang {
      background: #ffc107;
      color: #212529;
    }

    .item-row {
      border-bottom: 1px dashed #dee2e6;
      padding-bottom: 6px;
      margin-bottom: 6px;
    }

    .item-name {
      font-weight: 600;
    }

    .item-detail {
      font-size: 13px;
      color: #6c757d;
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-light bg-white shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold text-success" href="#"><i class="bi bi-shop"></i> Kantin UAM</a>
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
              <?php foreach (array_reverse($data) as $idx => $row):
                $displayIdx = count($data) - $idx;
                $name = htmlspecialchars($row['nama'] ?? ($row['nama_pembeli'] ?? 'Umum'));
                $total = number_format($row['total'] ?? 0, 0, ',', '.');
                $tgl = htmlspecialchars($row['tanggal'] ?? '');
                $status = htmlspecialchars($row['status'] ?? 'Lunas');
                ?>
                <button type="button"
                  class="list-group-item list-group-item-action d-flex justify-content-between align-items-start trx-item"
                  data-index="<?= $displayIdx - 1 ?>" data-name="<?= $name ?>" data-tanggal="<?= $tgl ?>"
                  data-total="<?= ($row['total'] ?? 0) ?>" data-metode="<?= htmlspecialchars($row['metode'] ?? '-') ?>"
                  data-status="<?= $status ?>" data-keterangan="<?= htmlspecialchars($row['keterangan'] ?? '-') ?>">
                  <div class="ms-2 me-auto text-start">
                    <div class="fw-semibold"><?= $name ?></div>
                    <div class="small-muted"><?= $tgl ?></div>
                  </div>
                  <div class="text-end">
                    <div class="fw-bold text-success">Rp <?= $total ?></div>
                    <div class="small-muted">
                      <?= $status === 'Lunas' ? '<span class="small text-success">Lunas</span>' : '<span class="badge badge-piutang">Piutang</span>' ?>
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
              <button id="viewFullBtn" class="btn btn-success ms-auto"><i class="bi bi-eye"></i> View Full</button>
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

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const transactions = <?php echo json_encode($data ?: []); ?> || [];
    function formatRp(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); }

    function renderItemsInReceipt(record) {
      const c = $('#itemsList').empty();
      if (Array.isArray(record.items) && record.items.length > 0) {
        record.items.forEach(it => {
          const n = it.nama || it.name || '-';
          const q = it.qty || it.quantity || 1;
          const p = it.harga || it.price || 0;
          const t = p * q;
          c.append(`<div class="item-row d-flex justify-content-between">
            <div><div class="item-name">${n}</div><div class="item-detail">${q} x ${formatRp(p)}</div></div>
            <div class="text-end fw-semibold">${formatRp(t)}</div>
          </div>`);
        });
      } else {
        c.append(`<div class="text-muted">Tidak ada detail makanan</div>`);
      }
    }

    $(document).ready(function () {
      function showReceiptByIndex(i) {
        const r = transactions[i]; if (!r) return;
        $('#emptyState').hide(); $('#receiptContent').show();
        const disc = r.diskon ?? r.discount ?? 0;
        const pajak = r.pajak ?? r.tax ?? 0;
        const uangMasuk = r.uang_masuk ?? r.cash ?? 0;
        const kembalian = r.kembalian ?? r.change ?? 0;
        $('#rTanggal').text(r.tanggal || '');
        $('#rMetode').text(r.metode || '-');
        $('#rKeterangan').text('Keterangan: ' + (r.keterangan || '-'));
        $('#rTotal').text(formatRp(r.total));
        $('#rSubtotal').text(formatRp(r.total - pajak + disc));
        $('#rDiscount').text(formatRp(disc));
        $('#rTax').text(formatRp(pajak));
        $('#rUangMasuk').text(formatRp(uangMasuk));
        $('#rKembalian').text(formatRp(kembalian));
        renderItemsInReceipt(r);
        $('#viewFullBtn').off('click').on('click', () => {
          $('#modalBody').html(buildFullReceiptHtml(r));
          new bootstrap.Modal(document.getElementById('modalFull')).show();
        });
      }

      $('.trx-item').on('click', function () {
        $('.trx-item').removeClass('selected');
        $(this).addClass('selected');
        showReceiptByIndex(parseInt($(this).attr('data-index')));
      });
    });

    function buildFullReceiptHtml(r) {
      const disc = r.diskon ?? r.discount ?? 0;
      const pajak = r.pajak ?? r.tax ?? 0;
      const uangMasuk = r.uang_masuk ?? r.cash ?? 0;
      const kembalian = r.kembalian ?? r.change ?? 0;
      let itemsHtml = '';
      if (Array.isArray(r.items) && r.items.length > 0) {
        r.items.forEach(it => {
          const q = it.qty || it.quantity || 1;
          const p = it.harga || it.price || 0;
          const t = p * q;
          itemsHtml += `<div style="display:flex;justify-content:space-between;margin-bottom:6px">
            <div><strong>${it.nama || it.name || '-'}</strong>
              <div style="font-size:12px;color:#666">${q} x ${formatRp(p)}</div>
            </div>
            <div>${formatRp(t)}</div>
          </div>`;
        });
      } else {
        itemsHtml = `<div style="text-align:center;color:#777;">Tidak ada detail makanan</div>`;
      }
      return `<div style="font-family:Arial,sans-serif;max-width:720px;margin:0 auto;padding:20px;">
        <h3 style="margin:0 0 8px;">Kantin UAM</h3>
        <div style="color:#666;margin-bottom:12px;">Jl. Pendidikan No. 25</div>
        <div style="display:flex;justify-content:space-between;margin-bottom:8px">
          <div style="color:#666">Tanggal: ${r.tanggal || ''}</div>
          <div style="color:#666">Metode: ${r.metode || '-'}</div>
        </div>
        <hr>${itemsHtml}<hr>
        <div style="display:flex;justify-content:space-between"><div>Subtotal</div><div>${formatRp(r.total - pajak + disc)}</div></div>
        <div style="display:flex;justify-content:space-between"><div>Diskon</div><div>${formatRp(disc)}</div></div>
        <div style="display:flex;justify-content:space-between"><div>Pajak</div><div>${formatRp(pajak)}</div></div>
        <div style="display:flex;justify-content:space-between;font-weight:bold;margin-top:8px"><div>Total</div><div>${formatRp(r.total)}</div></div>
        <div style="display:flex;justify-content:space-between;margin-top:8px"><div>Uang Masuk</div><div>${formatRp(uangMasuk)}</div></div>
        <div style="display:flex;justify-content:space-between"><div>Kembalian</div><div>${formatRp(kembalian)}</div></div>
        <div style="margin-top:16px;color:#666">${r.keterangan || ''}</div>
        <div style="text-align:center;margin-top:20px;color:#999;font-size:12px">Terima kasih telah berbelanja</div>
      </div>`;
    }
  </script>

</body>
</html>
