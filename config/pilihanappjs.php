<?php
    switch ($_GET["q"] ?? "") {
      case 'shift__Rekap_Shift__rekap_detail':
        include("../shift/js/rekap_shift__rekap_detail_js.php");
        break;
      default:
        break;
      case 'retur__html__stok':
        include("../retur/js/index_js.php");
        break;
    }