<?php
require_once '../Database/config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    
    $sql = "SELECT rt.*, sb.nama as nama_barang 
            FROM riwayat_transaksi rt 
            LEFT JOIN stok_barang sb ON rt.barang_id = sb.id 
            ORDER BY rt.tanggal DESC 
            LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($data) === 0) {
        echo json_encode(["message" => "Belum ada riwayat transaksi"]);
    } else {
        echo json_encode($data);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>