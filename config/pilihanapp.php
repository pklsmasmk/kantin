<?php
    switch ($_GET["q"] ?? "") {
      case 'dungo':
        include("../konten/dungo.php");
        break;
      case 'retur':
        include("../retur/index.php");
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
      default:
        include("../konten/ori.php");
        break;
    }