<?php
header('Content-Type: application/json');
require_once '../Database/config.php'; // Include file config

$conn = getDBConnection();

$sql = "SELECT * FROM stok_barang ORDER BY created_at DESC";
$result = $conn->query($sql);

$data = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
$conn->close();
?>