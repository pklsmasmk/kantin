<?php
require_once '../Database/config.php';

$pdo = getDBConnection();

if (!$pdo) {
    die("Error: Koneksi database gagal");
}

try {
    $stmt = $pdo->query("SELECT username, nama FROM users");
    $users = $stmt->fetchAll();

    echo "<h3>Daftar Users yang tersedia:</h3>";
    
    if (empty($users)) {
        echo "Tidak ada data users";
    } else {
        echo "<table border='1'>";
        echo "<tr><th>Username</th><th>Nama</th></tr>";
        foreach ($users as $user) {
            echo "<tr><td>{$user['username']}</td><td>{$user['nama']}</td></tr>";
        }
        echo "</table>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>