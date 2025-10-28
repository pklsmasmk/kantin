<?php
header('Content-Type: application/json');
include '../Database/config.php';

try {
    
    $sql = "SELECT r.*, sb.nama as nama_barang 
            FROM retur_barang r 
            JOIN stok_barang sb ON r.barang_id = sb.id 
            ORDER BY r.tanggal DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $data = [];
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) > 0) {
        foreach ($rows as $row) {
            $alasan = $row['alasan'] == 'Lainnya' ? $row['alasan_lainnya'] : $row['alasan'];
            $data[] = [
                'nama_barang' => $row['nama_barang'],
                'jumlah' => $row['jumlah'],
                'alasan' => $alasan,
                'keterangan' => $row['keterangan'],
                'tanggal' => $row['tanggal']
            ];
        }
    } else {
        $data = ["message" => "Belum ada riwayat retur"];
    }

    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>