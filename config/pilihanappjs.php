<?php
switch ($_GET["q"] ?? "") {
    case 'shift__Rekap_Shift__rekap_detail':
        include("../shift/js/rekap_shift__rekap_detail_js.php");
        break;
    case 'retur__html__stok':
        include("../retur/js/index_js.php");
        break;
    case 'pembelian_barang__stok':
        include("../pembelian_barang/stok_js.php");
      case 'menu':
        include("../penjualan/js/menu_js.php");
        break;
      case 'laporan_penjualan':
        include("../penjualan/js/laporanpen_js.php");
        break;
      case 'penjualan':
        include("../penjualan/js/pembayaran_js.php");
        break;
      case 'full':
        include("../penjualan/js/pembayaran_js.php");
        break;
      default:
        break;
}