<?php
    switch ($_GET["q"] ?? "") {
      case 'retur':
        include("../retur/js/index_js.php");
        break;
      default:
        break;
    }