<?php
header('Content-Type: application/json');
require_once '..Database/config.php';

// TAMBAHKAN INI UNTUK DEBUG
error_reporting(0); // Nonaktifkan error reporting untuk output bersih

try {
    $conn = getDBConnection();
    
    $sql = "SELECT * FROM stok_barang ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // PASTIKAN HANYA INI YANG DI OUTPUT
    echo json_encode($data);
    exit;

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>