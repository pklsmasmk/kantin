<?php
    switch ($_GET["q"] ?? "") {
      case 'dungo':
        include("../konten/dungo.php");
        break;
      case 'retur':
        include("../retur/html/stok.php");
        break;
      case 'retur__tampilstok':
        include("../retur/html/tampil_stok.php");
        break;
      case 'retur__ambil_barang':
        include("../retur/html/ambil_barang.php");
        break;  
      case 'retur__deletestok':
        include("../retur/html/delete_stok.php");
        break;
      case 'retur__insertstok':
        include("../retur/html/insert_stok.php");
        break;
      case 'retur__prosesretur':
        include("../retur/html/proses_retur.php");
        break;
      case 'retur__riwayatretur':
        include("../retur/html/riwayat_retur.php");
        break;
      case 'retur__tampilriwayat':
        include("../retur/html/tampil_riwayat.php");
        break;
      case 'retur__updatestok':
        include("../retur/html/update_stok.php");
        break;  
      case 'shift':
        include("../shift/index.php");
        break;
      case 'shift__Rekap_Shift__rekap_shift':
        include("../shift/Rekap_Shift/rekap_shift.php");
        break;
      case 'shift__Rekap_Shift__rekap_detail':
        include("../shift/Rekap_Shift/rekap_detail.php");
        break;
      case 'shift__Akhiri__akhiri_shift':
        include("../shift/Akhiri/akhiri_shift.php");
        break;  
      case 'shift__Akhiri__akhiri_sukses':
        include("../shift/Akhiri/akhiri_sukses.php");
        break;    
        
      default:
        include("../konten/ori.php");
        break;
    }