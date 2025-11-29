<?php
    switch ($_GET["app"] ?? "") {
      case 'simpan_penjualan':
        include("../penjualan/simpan_penjualan.php");
        break;
      default:
        break;
    }