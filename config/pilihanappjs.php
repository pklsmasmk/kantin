<?php
    switch ($_GET["q"] ?? "") {
      case 'retur':
        include("../retur/js/index_js.php");
        break;
      case 'laporan_penjualan':
        include("../penjualan/js/laporanpen_js.php");
        break;
      case 'penjualan':
        include("../penjualan/js/pembayaran_js.php");
        break;
      default:
        break;
    }