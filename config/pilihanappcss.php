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
      case 'hasil_piutang':
        include("../piutangkantin/css/hasil_piutang_css.php");
        break;
      default:
        break;
    }