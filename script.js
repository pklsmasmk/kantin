let cart = [];

$(document).on("click", ".addCart", function () {
  const card = $(this).closest(".card");
  const nama = card.find(".card-title").text();
  const hargaText = card.find(".card-text").text().replace(/[^\d]/g, "").trim();
  const harga = parseInt(hargaText) || 0;

  const existing = cart.find((i) => i.nama === nama);
  if (existing) {
    existing.qty += 1;
  } else {
    cart.push({ nama, harga, qty: 1 });
  }

  renderCart();
});

function renderCart() {
  const tbody = $("#cartList");
  tbody.empty();
  let subtotal = 0;

  cart.forEach((item, index) => {
    const total = item.harga * item.qty;
    subtotal += total;
    tbody.append(`
      <tr>
        <td>${item.nama}</td>
        <td class="text-center">
          <button class="btn btn-sm btn-outline-secondary minusBtn" data-index="${index}">-</button>
          <span class="mx-2">${item.qty}</span>
          <button class="btn btn-sm btn-outline-secondary plusBtn" data-index="${index}">+</button>
        </td>
        <td class="text-end">Rp${total.toLocaleString("id-ID")}</td>
        <td>
          <button class="btn btn-sm btn-danger removeBtn" data-index="${index}">
            <i class="bi bi-trash"></i>
          </button>
        </td>
      </tr>
    `);
  });

  const discountPercent = parseFloat($("#discount").val()) || 0;
  const taxPercent = parseFloat($("#tax").val()) || 0;

  const discountAmount = Math.round((subtotal * discountPercent) / 100);
  const taxAmount = Math.round(((subtotal - discountAmount) * taxPercent) / 100);
  const totalAfter = subtotal - discountAmount + taxAmount;

  $("#cartSubtotal").text(subtotal.toLocaleString("id-ID"));
  $("#cartDiscount").text(discountAmount.toLocaleString("id-ID"));
  $("#cartTax").text(taxAmount.toLocaleString("id-ID"));
  $("#cartTotal").text(totalAfter.toLocaleString("id-ID"));

  localStorage.setItem("currentSubtotal", subtotal);
  localStorage.setItem("currentDiscount", discountAmount);
  localStorage.setItem("currentTax", taxAmount);
  localStorage.setItem("currentTotal", totalAfter);

  const totalItems = cart.reduce((a, b) => a + b.qty, 0);
  $("#cartCount").text(totalItems);
}

$(document).on("click", ".plusBtn", function () {
  const index = $(this).data("index");
  cart[index].qty++;
  renderCart();
});

$(document).on("click", ".minusBtn", function () {
  const index = $(this).data("index");
  if (cart[index].qty > 1) cart[index].qty--;
  else cart.splice(index, 1);
  renderCart();
});

$(document).on("click", ".removeBtn", function () {
  const index = $(this).data("index");
  cart.splice(index, 1);
  renderCart();
});

$("#payBtn").on("click", function () {
  if (cart.length === 0) {
    alert("Keranjang masih kosong!");
    return;
  }

  // 🔥 Tambahan baru: simpan semua data makanan + total ke localStorage
  localStorage.setItem("cartItems", JSON.stringify(cart));
  localStorage.setItem("currentSubtotal", $("#cartSubtotal").text().replace(/[^\d]/g, ""));
  localStorage.setItem("currentDiscount", $("#cartDiscount").text().replace(/[^\d]/g, ""));
  localStorage.setItem("currentTax", $("#cartTax").text().replace(/[^\d]/g, ""));
  localStorage.setItem("currentTotal", $("#cartTotal").text().replace(/[^\d]/g, ""));

  window.location.href = "pembayaran.php";
});

$("#searchMenu").on("input", function () {
  const keyword = $(this).val().toLowerCase();
  $("#menuList .card").each(function () {
    const nama = $(this).find(".card-title").text().toLowerCase();
    $(this).closest(".col").toggle(nama.includes(keyword));
  });
});

$("#discount, #tax").on("input", function () {
  renderCart();
});

$(document).ready(() => {
  renderCart();
});
