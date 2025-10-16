$(document).ready(function () {
  const subtotal = parseInt(localStorage.getItem("currentSubtotal")) || 0;
  const disc = parseInt(localStorage.getItem("currentDiscount")) || 0;
  const tax = parseInt(localStorage.getItem("currentTax")) || 0;
  const grandTotal = parseInt(localStorage.getItem("currentTotal")) || 0;

  let bayar = "";
  let sedangInput = false;

  // --- Inisialisasi tampilan ---
  $("#subtotal").text(subtotal.toLocaleString("id-ID"));
  $("#disc").text(disc.toLocaleString("id-ID"));
  $("#tax").text(tax.toLocaleString("id-ID"));
  $("#total").text(grandTotal.toLocaleString("id-ID"));
  $("#inputBayar").text("0");
  $("#displayTotal").text("Rp " + grandTotal.toLocaleString("id-ID"));
  $("#totalNav").text("Rp " + grandTotal.toLocaleString("id-ID"));

  function updateInputDisplay() {
    if (bayar === "") $("#inputBayar").text("0");
    else $("#inputBayar").text(parseInt(bayar).toLocaleString("id-ID"));
  }

  // --- Aktifkan input manual ketika klik ---
  $("#inputBayar").on("click", function () {
    sedangInput = true;
    bayar = "";
    updateInputDisplay();
  });

  // --- Numpad klik ---
  $(".numKey").on("click", function () {
    sedangInput = true;
    const val = $(this).text();
    bayar += val.replace(/\D/g, "");
    updateInputDisplay();
  });

  // --- Clear, Backspace ---
  $("#clearInput").on("click", function () {
    bayar = "";
    updateInputDisplay();
  });

  $("#backspaceBtn").on("click", function () {
    bayar = bayar.slice(0, -1);
    updateInputDisplay();
  });

  // --- Tombol cepat ---
  $("#btnPas").on("click", function () {
    bayar = grandTotal.toString();
    updateInputDisplay();
  });

  $(".btnQuick").on("click", function () {
    sedangInput = true;
    const value = $(this).data("value");
    bayar = (parseInt(bayar || "0") + value).toString();
    updateInputDisplay();
  });

  // === Input Keyboard ===
  $(document).on("keydown", function (e) {
    if (!sedangInput) return;

    if (e.key >= "0" && e.key <= "9") {
      bayar += e.key;
      updateInputDisplay();
    } else if (e.key === "Backspace" || e.key === "Delete") {
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

  // === Tombol Konfirmasi ===
  $("#confirmBtn").click(function (e) {
    e.preventDefault();

    const metode = $("#metodePembayaran").val();
    const pelanggan = $("#namaPelanggan").val().trim();
    const keterangan = $("#keterangan").val().trim();
    const bayarFinal = bayar === "" ? 0 : parseInt(bayar);

    // 🔹 Ambil daftar makanan dari localStorage
    const makananList = JSON.parse(localStorage.getItem("cartItems")) || [];

    // === Jika Piutang ===
    if (metode === "Piutang") {
      if (!pelanggan) {
        alert("Nama pelanggan wajib diisi untuk piutang!");
        return;
      }

      let piutangList = JSON.parse(localStorage.getItem("piutangList")) || [];
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
        metode: metode,
        total: grandTotal,
        keterangan: keterangan || "-",
        status: "Piutang",
        items: makananList,
        diskon: disc,
        pajak: tax,
        uang_masuk: 0, // ✅ biar konsisten formatnya
        kembalian: 0
      };

      console.log("📤 Data piutang dikirim:", dataKirim);

      $.ajax({
        url: "simpan_penjualan.php",
        type: "POST",
        data: JSON.stringify(dataKirim),
        contentType: "application/json; charset=utf-8",
        success: function (res) {
          if (res.status === "success") {
            alert("✅ Transaksi piutang disimpan untuk: " + pelanggan);
          } else {
            alert("⚠️ Data gagal disimpan di server!");
          }
          window.location.href = "piutangkantin/tambah.php";
        },
        error: function (xhr) {
          alert("Gagal menyimpan data piutang!");
          console.error(xhr.responseText);
        },
      });
      return;
    }

    // === Jika Lunas ===
    if (bayarFinal < grandTotal) {
      alert("Uang tidak cukup!");
      return;
    }

    const kembalian = bayarFinal - grandTotal;

    const dataKirim = {
      nama: pelanggan || "Umum",
      metode: metode,
      total: grandTotal,
      keterangan: keterangan || "-",
      status: "Lunas",
      items: makananList,
      diskon: disc,
      pajak: tax,
      uang_masuk: bayarFinal, // ✅ uang masuk dikirim
      kembalian: kembalian    // ✅ kembalian dikirim
    };

    console.log("📤 Data penjualan dikirim:", dataKirim);

    $.ajax({
      url: "simpan_penjualan.php",
      type: "POST",
      data: JSON.stringify(dataKirim),
      contentType: "application/json; charset=utf-8",
      beforeSend: function () {
        $("#confirmBtn").prop("disabled", true).text("Menyimpan...");
      },
      success: function (res) {
        if (res.status === "success") {
          alert(
            `✅ Pembayaran Berhasil!\nMetode: ${metode}\nKembalian: Rp ${kembalian.toLocaleString("id-ID")}`
          );
        } else {
          alert("⚠️ Data gagal disimpan di server!");
        }

        // 🔹 Bersihkan data transaksi
        localStorage.removeItem("currentSubtotal");
        localStorage.removeItem("currentDiscount");
        localStorage.removeItem("currentTax");
        localStorage.removeItem("currentTotal");
        localStorage.removeItem("cartItems");

        window.location.href = "index.php";
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

  // 🔹 Debug untuk cek isi keranjang
  $("#confirmBtn").on("click", function () {
    const cekMakanan = JSON.parse(localStorage.getItem("cartItems")) || [];
    console.log("🔍 Data items yang akan dikirim:", cekMakanan);
  });
});
