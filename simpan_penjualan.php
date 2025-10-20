<?php
header("Content-Type: application/json");

date_default_timezone_set('Asia/Jakarta');
  
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data["nama"])) {
    echo json_encode(["status" => "error", "message" => "Data kosong atau tidak valid"]);
    exit;
}

$file = "penjualan.json";
$records = [];

if (file_exists($file)) {
    $json = file_get_contents($file);
    $records = json_decode($json, true) ?: [];
}

$data["tanggal"] = date("Y-m-d H:i:s");

if (isset($data["items"]) && is_array($data["items"])) {
    $bersih = [];
    foreach ($data["items"] as $item) {
        $bersih[] = [
            "nama" => $item["nama"] ?? "-",
            "qty" => (int) ($item["qty"] ?? 1),
            "harga" => (int) ($item["harga"] ?? 0)
        ];
    }
    $data["items"] = $bersih;
} else {
    $data["items"] = [];
}

$data["diskon"] = (int) ($data["diskon"] ?? 0);
$data["pajak"] = (int) ($data["pajak"] ?? 0);
$data["uang_masuk"] = (int) ($data["uang_masuk"] ?? 0);
$data["kembalian"] = (int) ($data["kembalian"] ?? 0);
$data["total"] = (int) ($data["total"] ?? 0);
$data["metode"] = $data["metode"] ?? "Tunai";
$data["status"] = $data["status"] ?? "Lunas";
$data["keterangan"] = $data["keterangan"] ?? "-";

if ($data["total"] <= 0) {
    $subtotal = 0;
    foreach ($data["items"] as $it) {
        $subtotal += ($it["qty"] * $it["harga"]);
    }

    $data["total"] = max(0, $subtotal - $data["diskon"] + $data["pajak"]);
}

$records[] = $data;

file_put_contents(
    $file,
    json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo json_encode([
    "status" => "success",
    "message" => "Data tersimpan",
    "data" => $data
]);
?>