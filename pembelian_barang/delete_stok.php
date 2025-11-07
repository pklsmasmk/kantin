<?php
header('Content-Type: application/json');
require_once '../Database/config.php';

try {
    $conn = getDBConnection();
    $table = 'stok_barang';

    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']);
        exit;
    }

    $sql = "DELETE FROM $table WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$id])) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan']);
        }
    } else {
        throw new Exception('Gagal menghapus data');
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>