<?php
    switch ($_GET["q"] ?? "") {
      case 'retur':
        include("../retur/js/index_js.php");
        break;
      case 'shift__Rekap_Shift__rekap_detail':
        include("../shift/js/rekap_shift__rekap_detail_js.php");
        break;
      default:
        break;
    }