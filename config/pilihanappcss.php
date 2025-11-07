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
    case 'retur__html__stok':
        include("../retur/css/index_css.php");
        break;
    case 'pembelian_barang__stok':
        include("../pembelian_barang/stok_css.php");
        break;
    default:
        break;
}