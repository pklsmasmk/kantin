<?php
    switch ($_GET["q"] ?? "") {
      case 'retur':
        include("../retur/css/index_css.php");
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
      default:
        break;
    }