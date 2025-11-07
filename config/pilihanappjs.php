<?php
switch ($_GET["q"] ?? "") {
    case 'shift__Rekap_Shift__rekap_detail':
        include("../shift/js/rekap_shift__rekap_detail_js.php");
        break;
    case 'retur__html__stok':
        include("../retur/js/index_js.php");
        break;
    case 'pembelian_barang__stok':
        include("../pembelian_barang/stok_js.php");
        break;
    default:
        break;
}