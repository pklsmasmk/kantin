<?php
require_once '../Database/config.php';

$pdo = getDBConnection();

echo "<h3>Struktur Tabel riwayat_transaksi:</h3>";
$stmt = $pdo->query("DESCRIBE riwayat_transaksi");
$columns = $stmt->fetchAll();
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $col) {
    echo "<tr>";
    echo "<td>" . $col['Field'] . "</td>";
    echo "<td>" . $col['Type'] . "</td>";
    echo "<td>" . $col['Null'] . "</td>";
    echo "<td>" . $col['Key'] . "</td>";
    echo "<td>" . $col['Default'] . "</td>";
    echo "<td>" . $col['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Struktur Tabel stok_barang:</h3>";
$stmt = $pdo->query("DESCRIBE stok_barang");
$columns = $stmt->fetchAll();
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $col) {
    echo "<tr>";
    echo "<td>" . $col['Field'] . "</td>";
    echo "<td>" . $col['Type'] . "</td>";
    echo "<td>" . $col['Null'] . "</td>";
    echo "<td>" . $col['Key'] . "</td>";
    echo "<td>" . $col['Default'] . "</td>";
    echo "<td>" . $col['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>