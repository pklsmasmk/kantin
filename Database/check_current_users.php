<?php
require_once 'config.php';
$pdo = getDBConnection();
$stmt = $pdo->query("SELECT username, nama FROM users");
$users = $stmt->fetchAll();

echo "<h3>Daftar Users yang tersedia:</h3>";
echo "<table border='1'>";
echo "<tr><th>Username</th><th>Nama</th></tr>";
foreach ($users as $user) {
    echo "<tr><td>{$user['username']}</td><td>{$user['nama']}</td></tr>";
}
echo "</table>";
?>