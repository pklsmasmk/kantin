<?php
    switch ($_GET["q"] ?? "") {
      case 'retur':
        include("../retur/js/index_js.php");
        break;
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
      case 'piutang':
        include("../piutangkantin/js/index_js.php");
        break;
      default:
        break;
    }