let cart = [];

function addToCart(nama, harga) {
  const existing = cart.find((i) => i.nama === nama);
  if (existing) {
    existing.qty += 1;
  } else {
    cart.push({ nama, harga, qty: 1 });
  }
  renderCart();
}

$(document).on("click", ".addCart", function () {
  const card = $(this).closest(".menu-card");
  const nama = card.find(".card-title").text().trim();
  const hargaText = card.find(".card-text").text().replace(/[^\d]/g, "").trim();
  const harga = parseInt(hargaText) || 0;

  addToCart(nama, harga);
});

function renderCart() {
  const tbody = $("#cartList");
  const cartSection = $(".col-md-4");  
  const cartCard = $(".col-md-4 .card");
  tbody.empty();
  let subtotal = 0;

  if (cart.length === 0) {

    cartSection.hide();
    
    if (!$("#emptyCartMessage").length) {
      $(".col-md-8").after(`
        <div class="col-md-4" id="emptyCartMessage">
          <div class="card p-4 border-0 shadow-sm text-center">
            <i class="bi bi-cart-x display-4 text-muted"></i>
            <h5 class="mt-3 text-muted">Keranjang Kosong</h5>
            <p class="text-muted small">Tambahkan item dari menu untuk memulai pesanan</p>
          </div>
        </div>
      `);
    }
  } else {

    cartSection.show();
    $("#emptyCartMessage").remove();
    
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
    const taxAmount = Math.round(
      ((subtotal - discountAmount) * taxPercent) / 100
    );
    const totalAfter = subtotal - discountAmount + taxAmount;

    $("#cartSubtotal").text(subtotal.toLocaleString("id-ID"));
    $("#cartDiscount").text(discountAmount.toLocaleString("id-ID"));
    $("#cartTax").text(taxAmount.toLocaleString("id-ID"));
    $("#cartTotal").text(totalAfter.toLocaleString("id-ID"));
  }

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
  if (cart[index].qty > 1) {
    cart[index].qty--;
  } else {
    cart.splice(index, 1);
  }
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

  let subtotal = 0;
  cart.forEach((item) => {
    subtotal += item.harga * item.qty;
  });

  const discountPercent = parseFloat($("#discount").val()) || 0;
  const taxPercent = parseFloat($("#tax").val()) || 0;

  const discountAmount = Math.round((subtotal * discountPercent) / 100);
  const taxAmount = Math.round(
    ((subtotal - discountAmount) * taxPercent) / 100
  );
  const totalAfter = subtotal - discountAmount + taxAmount;

  localStorage.setItem("cartItems", JSON.stringify(cart));
  localStorage.setItem("currentSubtotal", subtotal);
  localStorage.setItem("currentDiscount", discountAmount);
  localStorage.setItem("currentTax", taxAmount);
  localStorage.setItem("currentTotal", totalAfter);

  console.log("Data disimpan ke localStorage:");
  console.log("Subtotal:", subtotal);
  console.log("Discount:", discountAmount);
  console.log("Tax:", taxAmount);
  console.log("Total:", totalAfter);

  window.location.href = "/?q=penjualan";
});

$("#searchMenu").on("input", function () {
  const keyword = $(this).val().toLowerCase();
  $("#menuList .col").each(function () {
    const nama = $(this).find(".card-title").text().toLowerCase();
    $(this).toggle(nama.includes(keyword));
  });
});

$("#discount, #tax").on("input", function () {
  renderCart();
});

$(document).ready(function () {
  const currentUrl = window.location.href;
  const isPaymentPage =
    currentUrl.includes("q=penjualan") || currentUrl.includes("pembayaran");

  if (!isPaymentPage) {
    console.log("Reset cart karena berada di halaman menu");
    localStorage.removeItem("cartItems");
    localStorage.removeItem("currentSubtotal");
    localStorage.removeItem("currentDiscount");
    localStorage.removeItem("currentTax");
    localStorage.removeItem("currentTotal");
    cart = [];
  } else {
    console.log("Load cart untuk halaman pembayaran");
    const savedCart = localStorage.getItem("cartItems");
    if (savedCart) {
      cart = JSON.parse(savedCart);
    }
  }

  renderCart();
});