<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method not allowed";
    exit;
}

$barang_id = isset($_POST['barang_id']) ? (int)$_POST['barang_id'] : 0;
$jumlah = isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 0;
$alasan = isset($_POST['alasan']) ? trim($_POST['alasan']) : '';

if ($barang_id <= 0 || $jumlah <= 0) {
    echo "error: input tidak valid";
    exit;
}

// Ambil stok sekarang
$stmt = $conn->prepare("SELECT stok, tipe FROM db_stok WHERE id = ?");
$stmt->bind_param("i", $barang_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo "error: barang tidak ditemukan";
    exit;
}

$row = $res->fetch_assoc();
$current_stok = (int)$row['stok'];
$tipe = $row['tipe'];
$stmt->close();

if ($jumlah > $current_stok) {
    echo "error: jumlah retur melebihi stok";
    exit;
}

// Kurangi stok
$new_stok = $current_stok - $jumlah;
$stmt = $conn->prepare("UPDATE db_stok SET stok=? WHERE id=?");
$stmt->bind_param("ii", $new_stok, $barang_id);
$stmt->execute();
$stmt->close();

// (opsional) catat ke riwayat retur
$stmt = $conn->prepare("INSERT INTO riwayat_transaksi (tanggal, nama_barang, jenis_transaksi, keterangan)
                        VALUES (NOW(), ?, 'Retur', ?)");
$stmt->bind_param("ss", $tipe, $alasan);
$stmt->execute();
$stmt->close();

echo "success";
$conn->close();
?>
