<?php
header('Content-Type: application/json');
require_once '../Database/config.php';

error_reporting(0);

try {
    $conn = getDBConnection();
    
    $sql = "SELECT * FROM stok_barang ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);
    exit;

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>