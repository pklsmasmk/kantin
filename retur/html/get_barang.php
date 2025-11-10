<?php
require_once '../Database/config.php';  // PASTIKAN path benar

header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    $sql = "SELECT id, nama, stok FROM stok_barang WHERE stok > 0 ORDER BY nama";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($data);

} catch (Exception $e) {
    http_response_code(500);  // Tambahkan status code error
    echo json_encode(['error' => $e->getMessage()]);
}
?>