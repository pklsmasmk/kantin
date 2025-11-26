<?php
switch ($_GET["q"] ?? "") {
  case 'dungo':
    include("../konten/dungo.php");
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
  case 'hutang':
    include("../hutang/tampil_hutang.php");
    break;
  case 'hutang__bayar':
    include("../hutang/bayar_hutang.php");
    break;
  case 'Login':
    include("../login/Login.php");
    break;
  case 'Logout':
    include("../login/Logout.php");
    break;
  case 'Daftar':
    include("../login/Daftar.php");
    break;
  case 'shift':
    include("../shift/index.php");
    break;
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

  default:
    include("../konten/ori.php");
    break;
}
