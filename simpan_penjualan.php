<?php
header("Content-Type: application/json");
date_default_timezone_set('Asia/Jakarta');
include 'koneksi.php';

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

$data["diskon"]     = (int) ($data["diskon"] ?? 0);
$data["pajak"]      = (int) ($data["pajak"] ?? 0);
$data["uang_masuk"] = (int) ($data["uang_masuk"] ?? 0);
$data["kembalian"]  = (int) ($data["kembalian"] ?? 0);
$data["total"]      = (int) ($data["total"] ?? 0);
$data["metode"]     = $data["metode"] ?? "Tunai";
$data["status"]     = $data["status"] ?? "Lunas";
$data["keterangan"] = $data["keterangan"] ?? "-";

if ($data["total"] <= 0) {
    $subtotal = 0;
    foreach ($data["items"] as $it) {
        $subtotal += ($it["qty"] * $it["harga"]);
    }
    $data["total"] = max(0, $subtotal - $data["diskon"] + $data["pajak"]);
}

$records[] = $data;
file_put_contents($file, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$nama       = $conn->real_escape_string($data["nama"]);
$tanggal    = $conn->real_escape_string($data["tanggal"]);
$total      = $data["total"];
$metode     = $conn->real_escape_string($data["metode"]);
$status     = $conn->real_escape_string($data["status"]);
$diskon     = $data["diskon"];
$pajak      = $data["pajak"];
$uangMasuk  = $data["uang_masuk"];
$kembalian  = $data["kembalian"];
$keterangan = $conn->real_escape_string($data["keterangan"]);

$sql = "INSERT INTO penjualan 
        (nama_pembeli, tanggal, total, metode, status, diskon, pajak, uang_masuk, kembalian, keterangan)
        VALUES 
        ('$nama', '$tanggal', '$total', '$metode', '$status', '$diskon', '$pajak', '$uangMasuk', '$kembalian', '$keterangan')";

if ($conn->query($sql) === TRUE) {
    $id_penjualan = $conn->insert_id;
    if (!empty($data["items"])) {
        foreach ($data["items"] as $it) {
            $n = $conn->real_escape_string($it["nama"]);
            $q = (int) $it["qty"];
            $h = (int) $it["harga"];
            $conn->query("INSERT INTO detail_penjualan (id_penjualan, nama_item, qty, harga) VALUES ($id_penjualan, '$n', $q, $h)");
        }
    }
    echo json_encode(["status" => "success", "message" => "Data tersimpan ke JSON & database", "data" => $data]);
} else {
    echo json_encode(["status" => "error", "message" => $conn->error]);
}

$conn->close();
?>
