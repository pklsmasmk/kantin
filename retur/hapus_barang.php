<?php
include 'config.php';

if (!isset($_POST['id'])) {
    echo "error: ID tidak ditemukan";
    exit;
}

$id = (int)$_POST['id'];

$stmt = $conn->prepare("DELETE FROM db_stok WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "success";
} else {
    echo "error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
