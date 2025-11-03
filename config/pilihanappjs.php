<?php
    switch ($_GET["q"] ?? "") {
      case 'retur':
        include("../retur/js/index_js.php");
        break;
      case 'shift':
        include("../shift/js/shift_js.php");
        break;  
      case 'shift__Rekap_Shift__rekap_detail':
        include("../shift/js/rekap_shift__rekap_detail_js.php");
        break;
      case 'shift__Rekap_Shift__rekap_shift':
        include("../shift/js/rekap_shift__rekap_shift_js.php");
        break;
      case 'shift__Akhiri__akhiri_shift':
        include("../shift/js/akhiri__akhiri_shift_js.php");
        break; 
      default:
        break;
    }