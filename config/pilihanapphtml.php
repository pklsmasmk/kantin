<?php
    switch ($_GET["q"] ?? "") {
      case 'dungo':
        include("../konten/dungo.php");
        break;
      case 'pembelian_barang':
        include("../pembelian_barang/stok.php");
        break;
      case 'pembelian_barang__tampilstok':
        include("../pembelian_barang/tampil_stok.php");
        break;
      case 'retur__ambil_barang':
        include("../pembelian_barang/ambil_daftar_barang.php");
        break;  
      case 'retur__deletestok':
        include("../pembelian_barang/delete_stok.php");
        break;
      case 'pembelian_barang__insertstok':
        include("../pembelian_barang/insert_stok.php");
        break;
      case 'indexretur':
        include("../retur/html/index_retur.php");
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
        include("../pembelian_barang/update_stok.php");
        break;  
      case 'shift':
        include("../shift/index.php");
        break;
      case 'shift__Rekap_Shift__rekap_shift':
        include("../shift/Rekap_Shift/rekap_shift.php");
        break;
      case 'shift__Rekap_Shift__rekap_detail':
        include("../shift/Rekap_Shift/rekap_detail.php");
      case 'piutang':
        include("../piutangkantin/index.php");
        break;
      case 'piutang_tambah':
        include("../piutangkantin/tambah.php");
        break;
      case 'piutang_data':
        include("../piutangkantin/piutang.php");
        break;
      case 'menu':
        include("../penjualan/menu.php");
        break;
      case 'penjualan':
        include("../penjualan/pembayaran.php");
        break;
      case 'simpan_penjualan':
        include("../penjualan/simpan_penjualan.php");
        break;
      case 'laporan':
        include("../penjualan/laporan.php");
        break;
      case 'laporan_penjualan':
        include("../penjualan/laporan_penjualan.php");
        break;
      case 'login':
        include("../login/Login.php");
        break;
      case 'daftar':
        include("../login/Daftar.php");
        break;
      case 'logout':
        include("../login/Logout.php");
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