<?php
require_once 'config.php';

$pdo = getDBConnection();
if (!$pdo) {
    die("Error: Koneksi database gagal\n");
}

try {
    $stmt = $pdo->query("SELECT username, nama FROM users");
    $users = $stmt->fetchAll();

    echo "Daftar Users yang tersedia:\n";
    echo "============================\n";
    
    if (empty($users)) {
        echo "Tidak ada data users\n";
    } else {
        echo "Username\tNama\n";
        echo "--------\t----\n";
        foreach ($users as $user) {
            echo $user['username'] . "\t\t" . $user['nama'] . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>