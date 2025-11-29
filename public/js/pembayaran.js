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
  
  setTimeout(() => {
    $("#inputUangManual").focus();
  }, 500);

  function setupFormNavigation() {
    $("input, select, textarea").on("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();

        const nextElementId = $(this).data("next");
        if (nextElementId) {
          if (nextElementId === "confirmBtn") {
            if (validateForm()) {
              $("#confirmBtn").trigger("click");
            }
          } else {
            $("#" + nextElementId).focus();
          }
        }
      }
    });
  }

  function validateForm() {
    const metode = $("#metodePembayaran").val();
    const pelanggan = $("#namaPelanggan").val().trim();
    const bayarFinal = bayar === "" ? 0 : parseInt(bayar);

    if (metode === "Piutang") {
      if (!pelanggan) {
        alert("Nama pelanggan wajib diisi untuk piutang!");
        $("#namaPelanggan").focus();
        return false;
      }
    } else {
      if (bayarFinal < grandTotal) {
        alert(
          `Uang tidak cukup!\nTotal: Rp ${grandTotal.toLocaleString(
            "id-ID"
          )}\nDibayar: Rp ${bayarFinal.toLocaleString("id-ID")}`
        );
        $("#inputUangManual").focus();
        return false;
      }
    }

    return true;
  }

  function setupManualInput() {
    $("#inputUangManual").on("focus", function () {
      $(this).val(bayar === "" ? "" : parseInt(bayar).toLocaleString("id-ID"));
      $(this).select();
    });

    $("#inputUangManual").on("blur", function () {
      const value = $(this).val().replace(/\./g, "");
      if (value !== "") {
        const numericValue = parseInt(value) || 0;
        bayar = numericValue.toString();
        $(this).val(numericValue.toLocaleString("id-ID"));
        updateInputDisplay();
      }
    });

    $("#inputUangManual").on("input", function () {
      let value = $(this).val().replace(/\./g, "");
      value = value.replace(/\D/g, "");

      if (value !== "") {
        const numericValue = parseInt(value);
        $(this).val(numericValue.toLocaleString("id-ID"));
        bayar = numericValue.toString();
        updateInputDisplay();
      } else {
        bayar = "";
        updateInputDisplay();
      }
    });
  }

  function showSuccessModal(total, dibayar, kembalian, metode) {
    $("#modalTotal").text("Rp " + total.toLocaleString("id-ID"));
    $("#modalDibayar").text("Rp " + dibayar.toLocaleString("id-ID"));
    $("#modalKembalian").text("Rp " + kembalian.toLocaleString("id-ID"));
    $("#modalMetode").text(metode);
    $("#modalWaktu").text(
      new Date().toLocaleString("id-ID", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      })
    );

    $("#successModal").modal("show");
  }

  function showPiutangModal(nama, total, keterangan) {
    $("#modalPiutangNama").text(nama);
    $("#modalPiutangTotal").text("Rp " + total.toLocaleString("id-ID"));
    $("#modalPiutangKet").text(keterangan);
    $("#modalPiutangWaktu").text(
      new Date().toLocaleString("id-ID", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      })
    );

    $("#piutangModal").modal("show");
  }

  function updateInputDisplay() {
    const displayValue =
      bayar === "" ? "0" : parseInt(bayar).toLocaleString("id-ID");
    $("#inputBayar").text(displayValue);
    updatePaymentStatus();
  }

  function updatePaymentStatus() {
    const metode = $("#metodePembayaran").val();
    const bayarFinal = bayar === "" ? 0 : parseInt(bayar);
    const changeDisplay = $("#changeDisplay");

    // Jangan tampilkan change display untuk metode Piutang
    if (metode === "Piutang") {
      changeDisplay.hide();
      return;
    }

    if (bayarFinal === 0) {
      changeDisplay.hide();
    } else if (bayarFinal < grandTotal) {
      changeDisplay.text(
        "Kurang: Rp " + (grandTotal - bayarFinal).toLocaleString("id-ID")
      );
      changeDisplay.addClass("negative");
      changeDisplay.show();
    } else if (bayarFinal === grandTotal) {
      changeDisplay.text("Pembayaran Tepat");
      changeDisplay.removeClass("negative");
      changeDisplay.show();
    } else {
      changeDisplay.text(
        "Kembalian: Rp " + (bayarFinal - grandTotal).toLocaleString("id-ID")
      );
      changeDisplay.removeClass("negative");
      changeDisplay.show();
    }
  }

  if ($("#changeDisplay").length === 0) {
    $('<div class="change-display" id="changeDisplay"></div>').insertAfter(
      "#inputBayar"
    );
  }

  $("#inputBayar").on("click", function () {
    const metode = $("#metodePembayaran").val();
    if (metode === "Piutang") return; // Jangan izinkan input uang untuk Piutang
    
    sedangInput = true;
    bayar = "";
    $(this).addClass("active");
    updateInputDisplay();
    $("#inputUangManual").focus();
  });

  $(".numKey").on("click", function () {
    const metode = $("#metodePembayaran").val();
    if (metode === "Piutang") return; // Jangan izinkan input numpad untuk Piutang
    
    sedangInput = true;
    const val = $(this).text().replace(/\D/g, "");
    bayar += val;
    $("#inputBayar").addClass("active");
    $("#inputUangManual").val(parseInt(bayar).toLocaleString("id-ID"));
    updateInputDisplay();
  });

  $("#clearInput").on("click", function () {
    const metode = $("#metodePembayaran").val();
    if (metode === "Piutang") return; // Jangan izinkan clear input untuk Piutang
    
    bayar = "";
    $("#inputBayar").removeClass("active");
    $("#inputUangManual").val("");
    updateInputDisplay();
    $("#inputUangManual").focus();
  });

  $("#backspaceBtn").on("click", function () {
    const metode = $("#metodePembayaran").val();
    if (metode === "Piutang") return; // Jangan izinkan backspace untuk Piutang
    
    bayar = bayar.slice(0, -1);
    if (bayar === "") {
      $("#inputBayar").removeClass("active");
      $("#inputUangManual").val("");
    } else {
      $("#inputUangManual").val(parseInt(bayar).toLocaleString("id-ID"));
    }
    updateInputDisplay();
  });

  $("#btnPas").on("click", function () {
    const metode = $("#metodePembayaran").val();
    if (metode === "Piutang") return; // Jangan izinkan uang pas untuk Piutang
    
    bayar = grandTotal.toString();
    $("#inputBayar").addClass("active");
    $("#inputUangManual").val(parseInt(bayar).toLocaleString("id-ID"));
    updateInputDisplay();
    $("#namaPelanggan").focus();
  });

  $(".btnQuick").on("click", function () {
    const metode = $("#metodePembayaran").val();
    if (metode === "Piutang") return; // Jangan izinkan quick buttons untuk Piutang
    
    sedangInput = true;
    const value = parseInt($(this).data("value"));
    bayar = (parseInt(bayar || "0") + value).toString();
    $("#inputBayar").addClass("active");
    $("#inputUangManual").val(parseInt(bayar).toLocaleString("id-ID"));
    updateInputDisplay();
  });

  $(document).on("keydown", function (e) {
    const metode = $("#metodePembayaran").val();
    if (metode === "Piutang") return; // Jangan izinkan keyboard input untuk Piutang
    
    const isInputFocused =
      $("#inputUangManual").is(":focus") ||
      $("#namaPelanggan").is(":focus") ||
      $("#keterangan").is(":focus") ||
      $("#metodePembayaran").is(":focus");

    if (isInputFocused) {
      if (e.key === "Enter") {
        return;
      }
    }

    if (!sedangInput) return;

    if (e.key >= "0" && e.key <= "9") {
      bayar += e.key;
      $("#inputBayar").addClass("active");
      $("#inputUangManual").val(parseInt(bayar).toLocaleString("id-ID"));
      updateInputDisplay();
    } else if (["Backspace", "Delete"].includes(e.key)) {
      bayar = bayar.slice(0, -1);
      if (bayar === "") {
        $("#inputBayar").removeClass("active");
        $("#inputUangManual").val("");
      } else {
        $("#inputUangManual").val(parseInt(bayar).toLocaleString("id-ID"));
      }
      updateInputDisplay();
    } else if (e.key === "Escape") {
      bayar = "";
      sedangInput = false;
      $("#inputBayar").removeClass("active");
      $("#inputUangManual").val("");
      updateInputDisplay();
    }
  });

  $("#metodePembayaran").on("change", function () {
    const metode = $(this).val();
    if (metode === "Piutang") {
      $("#namaPelanggan").prop("required", true);
      $("#uangSection").hide();
      $("#uangInputSection").hide(); // Sembunyikan input uang manual
      $("#changeDisplay").hide(); // Sembunyikan display kembalian
      // Reset input uang
      bayar = "";
      $("#inputBayar").text("0");
      $("#inputUangManual").val("");
    } else {
      $("#namaPelanggan").prop("required", false);
      $("#uangSection").show();
      $("#uangInputSection").show(); // Tampilkan input uang manual
      updatePaymentStatus(); // Perbarui status pembayaran
    }
  });

  $("#modalCloseBtn").on("click", function () {
    localStorage.clear();
    window.location.href = "/?q=menu";
  });

  $("#modalPiutangCloseBtn").on("click", function () {
    localStorage.removeItem("cartItems");
    localStorage.removeItem("currentSubtotal");
    localStorage.removeItem("currentDiscount");
    localStorage.removeItem("currentTax");
    localStorage.removeItem("currentTotal");
    window.location.href = "/?q=menu";
  });

  $("#modalPiutangListBtn").on("click", function () {
    localStorage.removeItem("cartItems");
    localStorage.removeItem("currentSubtotal");
    localStorage.removeItem("currentDiscount");
    localStorage.removeItem("currentTax");
    localStorage.removeItem("currentTotal");
    window.location.href = "/?q=piutang_tambahpiutang";
  });

  $("#confirmBtn").click(function (e) {
    e.preventDefault();

    if (!validateForm()) {
      return;
    }

    const metode = $("#metodePembayaran").val();
    const pelanggan = $("#namaPelanggan").val().trim();
    const keterangan = $("#keterangan").val().trim();
    const bayarFinal = metode === "Piutang" ? 0 : (bayar === "" ? 0 : parseInt(bayar));
    const makananList = JSON.parse(localStorage.getItem("cartItems")) || [];

    if (metode === "Piutang") {
      const totalPiutang = grandTotal;

      localStorage.setItem("pendingPiutangNama", pelanggan);
      localStorage.setItem("pendingPiutangTotal", totalPiutang);
      localStorage.setItem("pendingPiutangKet", keterangan || "-");
      localStorage.setItem("pendingPiutangItems", JSON.stringify(makananList));

      const piutangList = JSON.parse(localStorage.getItem("piutangList")) || [];
      const newPiutang = {
        id: Date.now(),
        nama: pelanggan,
        total: totalPiutang,
        tanggal: new Date().toLocaleString("id-ID"),
        keterangan: keterangan || "-",
        items: makananList,
        diskon: disc,
        pajak: tax,
        status: "Belum Lunas",
        created: new Date().toISOString(),
        metode: metode,
      };
      piutangList.push(newPiutang);
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
            showPiutangModal(pelanggan, totalPiutang, keterangan || "-");
          } else {
            alert("Data gagal disimpan di server!");
          }
        },
        error: function (xhr) {
          alert("Gagal menyimpan data piutang!");
          console.error(xhr.responseText);
        },
        complete: function () {
          $("#confirmBtn")
            .prop("disabled", false)
            .text("Konfirmasi Pembayaran");
        },
      });
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
          showSuccessModal(grandTotal, bayarFinal, kembalian, metode);
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

  setupFormNavigation();
  setupManualInput();
  $("#metodePembayaran").trigger("change");
  updatePaymentStatus();
});