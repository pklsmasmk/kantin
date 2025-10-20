<?php
include 'config.php';

if (!isset($_GET['id'])) {
    echo json_encode(["error" => "ID tidak ditemukan"]);
    exit;
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM db_stok WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(["error" => "Data tidak ditemukan"]);
}

$stmt->close();
$conn->close();
?>
