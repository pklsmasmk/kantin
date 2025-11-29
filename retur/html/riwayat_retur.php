<?php
require_once '../Database/config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    
    $sql = "SELECT r.*, sb.nama as nama_barang 
            FROM retur_barang r 
            JOIN stok_barang sb ON r.barang_id = sb.id 
            ORDER BY r.tanggal DESC 
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($data) > 0) {
        $result = [];
        foreach ($data as $row) {
            // Gunakan alasan_lainnya jika alasan adalah 'Lainnya'
            $alasan_final = ($row['alasan'] == 'Lainnya' && !empty($row['alasan_lainnya'])) 
                ? $row['alasan_lainnya'] 
                : $row['alasan'];
                
            $result[] = [
                'nama_barang' => $row['nama_barang'],
                'jumlah' => $row['jumlah'],
                'alasan' => $alasan_final,
                'tanggal' => $row['tanggal']
            ];
        }
        echo json_encode($result);
    } else {
        echo json_encode(["message" => "Belum ada riwayat retur"]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>