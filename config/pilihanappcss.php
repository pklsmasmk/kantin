<?php
    switch ($_GET["q"] ?? "") {
      case 'retur':
        include("../retur/css/index_css.php");
        break;
      default:
        break;
    }