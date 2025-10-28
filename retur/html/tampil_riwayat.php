<?php
header('Content-Type: application/json');
include '../Database/config.php';

try {
    
    $sql = "SELECT * FROM riwayat_transaksi ORDER BY tanggal DESC LIMIT 50";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($data) === 0) {
        $data = ["message" => "Belum ada riwayat transaksi"];
    }

    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>