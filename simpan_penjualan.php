<?php
header("Content-Type: application/json");

// 🔹 Pastikan waktu sesuai zona Indonesia (WIB)
date_default_timezone_set('Asia/Jakarta');

// Ambil data JSON dari body request
$data = json_decode(file_get_contents("php://input"), true);

// 🔸 Validasi dasar
if (!$data || !isset($data["nama"])) {
    echo json_encode(["status" => "error", "message" => "Data kosong atau tidak valid"]);
    exit;
}

$file = "penjualan.json";
$records = [];

// 🔹 Jika file sudah ada, ambil datanya
if (file_exists($file)) {
    $json = file_get_contents($file);
    $records = json_decode($json, true) ?: [];
}

// 🔹 Tambahkan waktu transaksi (format WIB)
$data["tanggal"] = date("Y-m-d H:i:s");

// 🔹 Pastikan daftar items tersimpan dengan benar
if (isset($data["items"]) && is_array($data["items"])) {
    $bersih = [];
    foreach ($data["items"] as $item) {
        $bersih[] = [
            "nama"  => $item["nama"]  ?? "-",
            "qty"   => (int)($item["qty"]   ?? 1),
            "harga" => (int)($item["harga"] ?? 0)
        ];
    }
    $data["items"] = $bersih;
} else {
    $data["items"] = [];
}

// 🔹 Tambahkan nilai default jika tidak ada
$data["diskon"]      = (int)($data["diskon"]      ?? 0);
$data["pajak"]       = (int)($data["pajak"]       ?? 0);
$data["uang_masuk"]  = (int)($data["uang_masuk"]  ?? 0);
$data["kembalian"]   = (int)($data["kembalian"]   ?? 0);
$data["total"]       = (int)($data["total"]       ?? 0);
$data["metode"]      = $data["metode"]            ?? "Tunai";
$data["status"]      = $data["status"]            ?? "Lunas";
$data["keterangan"]  = $data["keterangan"]        ?? "-";

// 🔹 Hitung ulang total jika belum ada atau bernilai 0
if ($data["total"] <= 0) {
    $subtotal = 0;
    foreach ($data["items"] as $it) {
        $subtotal += ($it["qty"] * $it["harga"]);
    }

    // Rumus total akhir: subtotal - diskon + pajak
    $data["total"] = max(0, $subtotal - $data["diskon"] + $data["pajak"]);
}

// 🔹 Simpan data transaksi baru ke dalam array utama
$records[] = $data;

// 🔹 Tulis kembali ke file JSON
file_put_contents(
    $file,
    json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

// 🔹 Respon sukses ke JavaScript
echo json_encode([
    "status" => "success",
    "message" => "Data tersimpan",
    "data" => $data // opsional, kirim balik data yang disimpan untuk debug
]);
?>
