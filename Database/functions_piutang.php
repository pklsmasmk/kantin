<?php
// functions_piutang.php - VERSI SIMPLE
require_once 'config.php';

function db_kantin()
{
    global $pdo;

    // Return object sederhana
    return (object) [
        'pdo' => $pdo
    ];
}

function formatRupiah($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function formatTanggal($tanggal)
{
    return date('d/m/Y', strtotime($tanggal));
}

function formatWaktu($waktu)
{
    return date('H:i', strtotime($waktu));
}

function formatTanggalWaktu($datetime)
{
    return date('d/m/Y H:i', strtotime($datetime));
}
?>