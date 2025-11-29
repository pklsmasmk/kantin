<?php
switch ($_GET["q"] ?? "") {
  case 'dungo':
    include("../konten/dungo.php");
    break;
  case 'retur':
    include("../retur/html/index.php");
    break;
  case 'retur__tampilstok':
    include("../retur/html/tampil_stok.php");
    break;
  case 'shift':
    include("../shift/index.php");
    break;
  case 'piutang':
    include("../piutangkantin/index.php");
    break;
  case 'piutang_tambahpiutang':
    include("../piutangkantin/tambah_piutang.php");
    break;
  case 'piutang_hasilpiutang':
    include("../piutangkantin/hasil_piutang.php");
    break;
  case 'piutang__bayarpiutang':
    include("../piutangkantin/bayar_piutang.php");
    break;
  case 'menu':
    include("../penjualan/menu.php");
    break;
  case 'penjualan':
    include("../penjualan/pembayaran.php");
    break;
  case 'simpan_penjualan':
    include("../penjualan/simpan_penjualan.php");
    break;
  case 'laporan':
    include("../penjualan/laporan.php");
    break;
  case 'laporan_penjualan':
    include("../penjualan/laporan_penjualan.php");
    break;
  case 'login':
    include("../login/Login.php");
    break;
  case 'daftar':
    include("../login/Daftar.php");
    break;
  case 'logout':
    include("../login/Logout.php");
  case 'shift__Rekap_Shift__rekap_shift':
    include("../shift/Rekap_Shift/rekap_shift.php");
    break;
  case 'shift__Rekap_Shift__rekap_detail':
    include("../shift/Rekap_Shift/rekap_detail.php");
    break;
  case 'shift__Akhiri__akhiri_shift':
    include("../shift/Akhiri/akhiri_shift.php");
    break;
  case 'shift__Akhiri__akhiri_sukses':
    include("../shift/Akhiri/akhiri_sukses.php");
    break;
  case 'stok_barang':
    include("../stok_barang/stok_barang.php");
    break;
  case 'stok_barang__getstock':
    include("../stok_barang/get_stock.php");
    break;
  case 'riwayat_transaksi':
    include("../stok_barang/riwayat_transaksi.php");
    break;
  case 'stok_barang__simpanbarang':
    include("../stok_barang/simpan_barang.php");
    break;
  case 'stok_barang__simpanrestock':
    include("../stok_barang/simpan_restock.php");
    break;
  case 'hutang':
    include("../hutang/tampil_hutang.php");
    break;
  case 'riwayat_pembayaran':
    include("../hutang/riwayat_pembayaran.php");
    break;
  case 'hutang__bayar':
    include("../hutang/bayar_hutang.php");
    break;
  case 'multi_restock':
    include("../stok_barang/multi_restock.php");
    break;
  case 'retur__prosesretur':
    include("../retur/html/proses_retur.php");
    break;
  case 'retur__riwayatretur':
    include("../retur/html/riwayat_retur.php");
    break;
  case 'retur__tampilriwayat':
    include("../retur/html/tampil_riwayat.php");
    break;
  case 'retur__updatestok':
    include("../pembelian_barang/update_stok.php");
    break;

  default:
    include("../konten/ori.php");
    break;
}
