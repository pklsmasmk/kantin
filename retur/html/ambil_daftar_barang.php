<?php
header('Content-Type: application/json');
require_once '..Database/config.php';
error_reporting(0);

try {
    $conn = getDBConnection();
    $sql = "SELECT id, nama, stok, tipe, pemasok, harga_dasar, harga_jual 
            FROM stok_barang 
            WHERE stok >= 0 
            ORDER BY nama";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'data' => []
    ]);
    exit;
}
?>