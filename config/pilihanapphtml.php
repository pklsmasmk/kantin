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
      case 'logout':
        include("../login/Logout.php");
        break;
      default:
        include("../konten/ori.php");
        break;
    }