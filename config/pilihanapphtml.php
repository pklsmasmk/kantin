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
      case 'login':
        include("../login/Login.php");
        break;
      case 'logout':
        include("../login/Logout.php");
        break;
      case 'daftar':
        include("../login/Daftar.php");
        break;
      default:
        include("../konten/ori.php");
        break;
    }