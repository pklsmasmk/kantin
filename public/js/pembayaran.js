$(document).ready(function () {
  const subtotal = parseInt(localStorage.getItem("currentSubtotal")) || 0;
  const disc = parseInt(localStorage.getItem("currentDiscount")) || 0;
  const tax = parseInt(localStorage.getItem("currentTax")) || 0;
  const grandTotal = parseInt(localStorage.getItem("currentTotal")) || 0;

  let bayar = "";
  let sedangInput = false;

  $("#subtotal").text(subtotal.toLocaleString("id-ID"));
  $("#disc").text(disc.toLocaleString("id-ID"));
  $("#tax").text(tax.toLocaleString("id-ID"));
  $("#total").text(grandTotal.toLocaleString("id-ID"));
  $("#inputBayar").text("0");
  $("#displayTotal").text("Rp " + grandTotal.toLocaleString("id-ID"));
  $("#totalNav").text("Rp " + grandTotal.toLocaleString("id-ID"));

  function updateInputDisplay() {
    const displayValue = bayar === "" ? "0" : parseInt(bayar).toLocaleString("id-ID");
    $("#inputBayar").text(displayValue);
    updatePaymentStatus();
  }

  function updatePaymentStatus() {
    const bayarFinal = bayar === "" ? 0 : parseInt(bayar);
    const changeDisplay = $("#changeDisplay");
    
    if (bayarFinal === 0) {
      changeDisplay.hide();
    } else if (bayarFinal < grandTotal) {
      changeDisplay.text("Kurang: Rp " + (grandTotal - bayarFinal).toLocaleString("id-ID"));
      changeDisplay.addClass("negative");
      changeDisplay.show();
    } else if (bayarFinal === grandTotal) {
      changeDisplay.text("Pembayaran Tepat");
      changeDisplay.removeClass("negative");
      changeDisplay.show();
    } else {
      changeDisplay.text("Kembalian: Rp " + (bayarFinal - grandTotal).toLocaleString("id-ID"));
      changeDisplay.removeClass("negative");
      changeDisplay.show();
    }
  }

  if ($("#changeDisplay").length === 0) {
    $('<div class="change-display" id="changeDisplay"></div>').insertAfter("#inputBayar");
  }

  $("#inputBayar").on("click", function () {
    sedangInput = true;
    bayar = "";
    $(this).addClass("active");
    updateInputDisplay();
  });

  $(".numKey").on("click", function () {
    sedangInput = true;
    const val = $(this).text().replace(/\D/g, "");
    bayar += val;
    $("#inputBayar").addClass("active");
    updateInputDisplay();
  });

  $("#clearInput").on("click", function () {
    bayar = "";
    $("#inputBayar").removeClass("active");
    updateInputDisplay();
  });

  $("#backspaceBtn").on("click", function () {
    bayar = bayar.slice(0, -1);
    if (bayar === "") {
      $("#inputBayar").removeClass("active");
    }
    updateInputDisplay();
  });

  $("#btnPas").on("click", function () {
    bayar = grandTotal.toString();
    $("#inputBayar").addClass("active");
    updateInputDisplay();
  });

  $(".btnQuick").on("click", function () {
    sedangInput = true;
    const value = parseInt($(this).data("value"));
    bayar = (parseInt(bayar || "0") + value).toString();
    $("#inputBayar").addClass("active");
    updateInputDisplay();
  });

  $(document).on("keydown", function (e) {
    const isInputFocused = $("#namaPelanggan").is(":focus") || $("#keterangan").is(":focus");
    
    if (isInputFocused) {
      return;
    }

    if (!sedangInput) return;

    if (e.key >= "0" && e.key <= "9") {
      bayar += e.key;
      $("#inputBayar").addClass("active");
      updateInputDisplay();
    } else if (["Backspace", "Delete"].includes(e.key)) {
      bayar = bayar.slice(0, -1);
      if (bayar === "") {
        $("#inputBayar").removeClass("active");
      }
      updateInputDisplay();
    } else if (e.key === "Enter") {
      $("#confirmBtn").trigger("click");
    } else if (e.key === "Escape") {
      bayar = "";
      sedangInput = false;
      $("#inputBayar").removeClass("active");
      updateInputDisplay();
    }
  });

  $("#metodePembayaran").on("change", function () {
    const metode = $(this).val();
    if (metode === "Piutang") {
      $("#namaPelanggan").prop("required", true);
      $("#uangSection").hide();
    } else {
      $("#namaPelanggan").prop("required", false);
      $("#uangSection").show();
    }
  });

  $("#confirmBtn").click(function (e) {
    e.preventDefault();

    const metode = $("#metodePembayaran").val();
    const pelanggan = $("#namaPelanggan").val().trim();
    const keterangan = $("#keterangan").val().trim();
    const bayarFinal = bayar === "" ? 0 : parseInt(bayar);
    const makananList = JSON.parse(localStorage.getItem("cartItems")) || [];

    if (metode === "Piutang") {
      if (!pelanggan) {
        alert("Nama pelanggan wajib diisi untuk piutang!");
        return;
      }

      const totalPiutang = grandTotal;

      localStorage.setItem("pendingPiutangNama", pelanggan);
      localStorage.setItem("pendingPiutangTotal", totalPiutang);
      localStorage.setItem("pendingPiutangKet", keterangan || "-");

      const piutangList = JSON.parse(localStorage.getItem("piutangList")) || [];
      piutangList.push({
        nama: pelanggan,
        total: totalPiutang,
        tanggal: new Date().toLocaleString("id-ID"),
        keterangan: keterangan || "-",
        makanan: makananList,
        paid: false,
      });
      localStorage.setItem("piutangList", JSON.stringify(piutangList));

      const dataKirim = {
        nama: pelanggan,
        metode,
        total: totalPiutang,
        keterangan: keterangan || "-",
        status: "Piutang",
        items: makananList,
        diskon: disc,
        pajak: tax,
        uang_masuk: 0,
        kembalian: 0,
      };

      $.ajax({
        url: "/?q=full&app=simpan_penjualan",
        type: "POST",
        data: JSON.stringify(dataKirim),
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        beforeSend: function () {
          $("#confirmBtn").prop("disabled", true).text("Menyimpan Piutang...");
        },
        success: function (res) {
          if (res && res.status === "success") {
            alert("Transaksi piutang disimpan untuk: " + pelanggan);
            window.location.href = "/?q=piutang_tambah";
          } else {
            alert("Data gagal disimpan di server!");
          }
        },
        error: function (xhr) {
          alert("Gagal menyimpan data piutang!");
          console.error(xhr.responseText);
        },
        complete: function () {
          $("#confirmBtn").prop("disabled", false).text("Konfirmasi Pembayaran");
        },
      });
      return;
    }

    if (bayarFinal < grandTotal) {
      alert(
        `Uang tidak cukup!\nTotal: Rp ${grandTotal.toLocaleString("id-ID")}\nDibayar: Rp ${bayarFinal.toLocaleString("id-ID")}`
      );
      return;
    }

    const kembalian = bayarFinal - grandTotal;

    const dataKirim = {
      nama: pelanggan || "Umum",
      metode,
      total: grandTotal,
      keterangan: keterangan || "-",
      status: "Lunas",
      items: makananList,
      diskon: disc,
      pajak: tax,
      uang_masuk: bayarFinal,
      kembalian,
    };

    $.ajax({
      url: "/?q=full&app=simpan_penjualan",
      type: "POST",
      data: JSON.stringify(dataKirim),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      beforeSend: function () {
        $("#confirmBtn").prop("disabled", true).text("Menyimpan...");
      },
      success: function (res) {
        if (res && res.status === "success") {
          alert(
            `Pembayaran Berhasil!\nTotal: Rp ${grandTotal.toLocaleString("id-ID")}\nDibayar: Rp ${bayarFinal.toLocaleString("id-ID")}\nKembalian: Rp ${kembalian.toLocaleString("id-ID")}`
          );
          localStorage.clear();
          window.location.href = "/?q=menu";
        } else {
          alert("Data gagal disimpan di server!");
        }
      },
      error: function (xhr) {
        alert("Gagal menyimpan data penjualan!");
        console.error(xhr.responseText);
      },
      complete: function () {
        $("#confirmBtn").prop("disabled", false).text("Konfirmasi Pembayaran");
      },
    });
  });

  $("#metodePembayaran").trigger("change");
  updatePaymentStatus();
});