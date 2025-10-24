<?php
include '../Database/config.php';

$sql = "SELECT id, tipe FROM db_stok ORDER BY tipe";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = htmlspecialchars($row['id']);
        $tipe = htmlspecialchars($row['tipe']);
        echo "<option value='{$id}'>{$tipe}</option>";
    }
} else {
    echo "<option value=''>-- Tidak ada data --</option>";
}

$conn->close();
?>
