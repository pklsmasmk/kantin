<?php
header("Content-Type: application/json");
date_default_timezone_set('Asia/Jakarta');
include '../Database/config.php';

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data["nama"])) {
    echo json_encode(["status" => "error", "message" => "Data kosong atau tidak valid"]);
    exit;
}

$data["tanggal"] = date("Y-m-d H:i:s");

if (isset($data["items"]) && is_array($data["items"])) {
    $items = [];
    foreach ($data["items"] as $item) {
        $items[] = [
            "nama"  => $item["nama"] ?? "-",
            "qty"   => (int) ($item["qty"] ?? 1),
            "harga" => (int) ($item["harga"] ?? 0)
        ];
    }
    $data["items"] = $items;
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

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO penjualan 
        (nama_pembeli, tanggal, total, metode, status, diskon, pajak, uang_masuk, kembalian, keterangan)
        VALUES (:nama, :tanggal, :total, :metode, :status, :diskon, :pajak, :uang_masuk, :kembalian, :keterangan)");

    $stmt->execute([
        ':nama'        => $data["nama"],
        ':tanggal'     => $data["tanggal"],
        ':total'       => $data["total"],
        ':metode'      => $data["metode"],
        ':status'      => $data["status"],
        ':diskon'      => $data["diskon"],
        ':pajak'       => $data["pajak"],
        ':uang_masuk'  => $data["uang_masuk"],
        ':kembalian'   => $data["kembalian"],
        ':keterangan'  => $data["keterangan"]
    ]);

    $id_penjualan = $pdo->lastInsertId();

    if (!empty($data["items"])) {
        $stmtDetail = $pdo->prepare("INSERT INTO detail_penjualan 
            (id_penjualan, nama_item, qty, harga) 
            VALUES (:id_penjualan, :nama_item, :qty, :harga)");

        foreach ($data["items"] as $it) {
            $stmtDetail->execute([
                ':id_penjualan' => $id_penjualan,
                ':nama_item'    => $it["nama"],
                ':qty'          => $it["qty"],
                ':harga'        => $it["harga"]
            ]);
        }
    }

    $pdo->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Data berhasil disimpan ke database",
        "redirect" => "../public/index.php",
        "data" => $data
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
