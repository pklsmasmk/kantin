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
    $("#inputBayar").text(
      bayar === "" ? "0" : parseInt(bayar).toLocaleString("id-ID")
    );
  }

  $("#inputBayar").on("click", function () {
    sedangInput = true;
    bayar = "";
    updateInputDisplay();
  });

  $(".numKey").on("click", function () {
    sedangInput = true;
    const val = $(this).text().replace(/\D/g, "");
    bayar += val;
    updateInputDisplay();
  });

  $("#clearInput").on("click", function () {
    bayar = "";
    updateInputDisplay();
  });

  $("#backspaceBtn").on("click", function () {
    bayar = bayar.slice(0, -1);
    updateInputDisplay();
  });

  $("#btnPas").on("click", function () {
    bayar = grandTotal.toString();
    updateInputDisplay();
  });

  $(".btnQuick").on("click", function () {
    sedangInput = true;
    const value = parseInt($(this).data("value"));
    bayar = (parseInt(bayar || "0") + value).toString();
    updateInputDisplay();
  });

  $(document).on("keydown", function (e) {
    if (!sedangInput) return;

    if (e.key >= "0" && e.key <= "9") {
      bayar += e.key;
      updateInputDisplay();
    } else if (["Backspace", "Delete"].includes(e.key)) {
      bayar = bayar.slice(0, -1);
      updateInputDisplay();
    } else if (e.key === "Enter") {
      $("#confirmBtn").trigger("click");
    } else if (e.key === "Escape") {
      bayar = "";
      sedangInput = false;
      updateInputDisplay();
    }
  });

  $("#metodePembayaran").on("change", function () {
    const metode = $(this).val();
    if (metode === "Piutang") {
      $("#namaPelanggan").prop("required", true).parent().show();
    } else {
      $("#namaPelanggan").prop("required", false).parent().show();
    }
  });

  $("#confirmBtn").click(function (e) {
    e.preventDefault();

    const metode = $("#metodePembayaran").val();
    const pelanggan = $("#namaPelanggan").val().trim();
    const keterangan = $("#keterangan").val().trim();
    const bayarFinal = bayar === "" ? 0 : parseInt(bayar);
    const makananList = JSON.parse(localStorage.getItem("cartItems")) || [];

    console.log("🔍 Data items yang akan dikirim:", makananList);

    if (metode === "Piutang") {
      if (!pelanggan) {
        alert("Nama pelanggan wajib diisi untuk piutang!");
        return;
      }

      localStorage.setItem("pendingPiutangNama", pelanggan);
      localStorage.setItem("pendingPiutangTotal", grandTotal);
      localStorage.setItem("pendingPiutangKet", keterangan || "-");

      const piutangList = JSON.parse(localStorage.getItem("piutangList")) || [];
      piutangList.push({
        nama: pelanggan,
        total: grandTotal,
        tanggal: new Date().toLocaleString("id-ID"),
        keterangan: keterangan || "-",
        makanan: makananList,
        paid: false,
      });
      localStorage.setItem("piutangList", JSON.stringify(piutangList));

      const dataKirim = {
        nama: pelanggan,
        metode,
        total: grandTotal,
        keterangan: keterangan || "-",
        status: "Piutang",
        items: makananList,
        diskon: disc,
        pajak: tax,
        uang_masuk: 0,
        kembalian: 0,
      };

      console.log("📤 Data piutang dikirim:", dataKirim);

      $.ajax({
        url: "../penjualan/simpan_penjualan.php",
        type: "POST",
        data: JSON.stringify(dataKirim),
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        beforeSend: function () {
          $("#confirmBtn").prop("disabled", true).text("Menyimpan Piutang...");
        },
        success: function (res) {
          console.log("✅ Respon server:", res);
          if (res && res.status === "success") {
            alert("✅ Transaksi piutang disimpan untuk: " + pelanggan);
            window.location.href = "../piutangkantin/tambah.php";
          } else {
            alert("⚠️ Data gagal disimpan di server!");
          }
        },
        error: function (xhr) {
          alert("❌ Gagal menyimpan data piutang!");
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

    if (bayarFinal < grandTotal) {
      alert("Uang tidak cukup!");
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

    console.log("📤 Data penjualan dikirim:", dataKirim);

    $.ajax({
      url: "../../penjualan/simpan_penjualan.php",
      type: "POST",
      data: JSON.stringify(dataKirim),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      beforeSend: function () {
        $("#confirmBtn").prop("disabled", true).text("Menyimpan...");
      },
      success: function (res) {
        console.log("✅ Respon server:", res);
        if (res && res.status === "success") {
          alert(
            `✅ Pembayaran Berhasil!\nMetode: ${metode}\nKembalian: Rp ${kembalian.toLocaleString(
              "id-ID"
            )}`
          );
          localStorage.clear();
          window.location.href = "../index.php";
        } else {
          alert("⚠️ Data gagal disimpan di server!");
        }
      },
      error: function (xhr) {
        alert("❌ Gagal menyimpan data penjualan!");
        console.error(xhr.responseText);
      },
      complete: function () {
        $("#confirmBtn").prop("disabled", false).text("Konfirmasi Pembayaran");
      },
    });
  });

  $("#metodePembayaran").trigger("change");
});
