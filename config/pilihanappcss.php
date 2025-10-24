<?php
    switch ($_GET["q"] ?? "") {
      case 'retur':
        include("../retur/css/index_css.php");
        break;
      case 'shift__Rekap_Shift__rekap_shift':
        include("../shift/css/rekap_shift__rekap_shift.php");
        break;
      case 'shift__Rekap_Shift__rekap_detail':
        include("../shift/css/rekap_shift__rekap_detail.php");
        break; 
      default:
        break;
    }