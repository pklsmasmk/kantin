<?php
switch ($_GET["q"] ?? "") {
    case 'retur':
        include("../retur/css/index_css.php");
        break;
     case 'menu':
        include("../penjualan/css/menu_css.php");
        break;
      case 'penjualan':
        include("../penjualan/css/index_css.php");
        break;
      case 'laporan':
        include("../penjualan/css/laporan_css.php");
        break;
      case 'laporan_penjualan':
        include("../penjualan/css/laporanpen_css.php");
        break;
      case 'piutang':
        include("../piutangkantin/css/index_css.php");
        break;
      case 'shift':
        include("../shift/css/shift_css.php");
        break;   
      case 'shift__Rekap_Shift__rekap_shift':
        include("../shift/css/rekap_shift__rekap_shift.php");
        break; 
      case 'shift__Akhiri__akhiri_shift':
        include("../shift/css/akhiri__akhiri_shift.php");
        break; 
      case 'shift__Akhiri__akhiri_sukses':
        include("../shift/css/akhiri__akhiri_sukses.php");
        break; 
      default:
        break;
    case 'shift__Rekap_Shift__rekap_detail':
        include("../shift/css/rekap_shift__rekap_detail.php");
        break; 
    case 'retur__html__stok':
        include("../retur/css/index_css.php");
        break;
    case 'pembelian_barang__stok':
        include("../pembelian_barang/stok_css.php");
        break;
}