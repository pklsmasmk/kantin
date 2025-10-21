function formatRp(n) {
  return "Rp " + Number(n || 0).toLocaleString("id-ID");
}

function renderItemsInReceipt(record) {
  const c = $("#itemsList").empty();
  if (Array.isArray(record.items) && record.items.length > 0) {
    record.items.forEach((it) => {
      const n = it.nama || it.name || "-";
      const q = parseFloat(it.qty || it.quantity || 1);
      const p = parseFloat(it.harga || it.price || 0);
      const t = q * p;
      c.append(`
        <div class="item-row d-flex justify-content-between">
          <div>
            <div class="item-name">${n}</div>
            <div class="item-detail">${q} x ${formatRp(p)}</div>
          </div>
          <div class="text-end fw-semibold">${formatRp(t)}</div>
        </div>
      `);
    });
  } else {
    c.append(`<div class="text-muted">Tidak ada detail makanan</div>`);
  }
}

function buildFullReceiptHtml(r) {
  const disc = parseFloat(r.diskon ?? r.discount ?? 0);
  const pajak = parseFloat(r.pajak ?? r.tax ?? 0);
  const uangMasuk = parseFloat(r.uang_masuk ?? r.cash ?? 0);
  const kembalian = parseFloat(r.kembalian ?? r.change ?? 0);
  const total = parseFloat(r.total ?? 0);
  const subtotal = total - pajak + disc;

  let itemsHtml = "";
  if (Array.isArray(r.items) && r.items.length > 0) {
    r.items.forEach((it) => {
      const q = parseFloat(it.qty || it.quantity || 1);
      const p = parseFloat(it.harga || it.price || 0);
      const t = q * p;
      itemsHtml += `
        <div style="display:flex;justify-content:space-between;margin-bottom:6px">
          <div>
            <strong>${it.nama || it.name || "-"}</strong>
            <div style="font-size:12px;color:#666">${q} x ${formatRp(p)}</div>
          </div>
          <div>${formatRp(t)}</div>
        </div>`;
    });
  } else {
    itemsHtml = `<div style="text-align:center;color:#777;">Tidak ada detail makanan</div>`;
  }

  return `
    <div style="font-family:Arial,sans-serif;max-width:720px;margin:0 auto;padding:20px;">
      <h3 style="margin:0 0 8px;">Kantin UAM</h3>
      <div style="color:#666;margin-bottom:12px;">Jl. Pendidikan No. 25</div>
      <div style="display:flex;justify-content:space-between;margin-bottom:8px">
        <div style="color:#666">Tanggal: ${r.tanggal || ""}</div>
        <div style="color:#666">Metode: ${r.metode || "-"}</div>
      </div>
      <hr>${itemsHtml}<hr>
      <div style="display:flex;justify-content:space-between"><div>Subtotal</div><div>${formatRp(subtotal)}</div></div>
      <div style="display:flex;justify-content:space-between"><div>Diskon</div><div>${formatRp(disc)}</div></div>
      <div style="display:flex;justify-content:space-between"><div>Pajak</div><div>${formatRp(pajak)}</div></div>
      <div style="display:flex;justify-content:space-between;font-weight:bold;margin-top:8px">
        <div>Total</div><div>${formatRp(total)}</div>
      </div>
      <div style="display:flex;justify-content:space-between;margin-top:8px"><div>Uang Masuk</div><div>${formatRp(uangMasuk)}</div></div>
      <div style="display:flex;justify-content:space-between"><div>Kembalian</div><div>${formatRp(kembalian)}</div></div>
      <div style="margin-top:16px;color:#666">${r.keterangan || ""}</div>
      <div style="text-align:center;margin-top:20px;color:#999;font-size:12px">
        Terima kasih telah berbelanja
      </div>
    </div>`;
}

$(document).ready(function () {
  function showReceiptByIndex(i) {
    const r = transactions[i];
    if (!r) return;
    $("#emptyState").hide();
    $("#receiptContent").show();

    const disc = parseFloat(r.diskon ?? r.discount ?? 0);
    const pajak = parseFloat(r.pajak ?? r.tax ?? 0);
    const uangMasuk = parseFloat(r.uang_masuk ?? r.cash ?? 0);
    const kembalian = parseFloat(r.kembalian ?? r.change ?? 0);
    const total = parseFloat(r.total ?? 0);
    const subtotal = total - pajak + disc;

    $("#rTanggal").text(r.tanggal || "");
    $("#rMetode").text(r.metode || "-");
    $("#rKeterangan").text("Keterangan: " + (r.keterangan || "-"));
    $("#rSubtotal").text(formatRp(subtotal));
    $("#rDiscount").text(formatRp(disc));
    $("#rTax").text(formatRp(pajak));
    $("#rTotal").text(formatRp(total));
    $("#rUangMasuk").text(formatRp(uangMasuk));
    $("#rKembalian").text(formatRp(kembalian));

    renderItemsInReceipt(r);

    $("#viewFullBtn")
      .off("click")
      .on("click", () => {
        $("#modalBody").html(buildFullReceiptHtml(r));
        new bootstrap.Modal(document.getElementById("modalFull")).show();
      });
  }

  $(".trx-item").on("click", function () {
    $(".trx-item").removeClass("selected");
    $(this).addClass("selected");
    showReceiptByIndex(parseInt($(this).attr("data-index")));
  });
});
