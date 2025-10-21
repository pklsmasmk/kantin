<?php
include '../Database/config.php';

$sql = "SELECT r.*, b.nama as nama_barang 
        FROM retur_barang r 
        JOIN barang b ON r.barang_id = b.id 
        ORDER BY r.tanggal DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $alasan = $row['alasan'] == 'Lainnya' ? $row['alasan_lainnya'] : $row['alasan'];
        $foto = $row['foto_bukti'] ? "<br><img src='{$row['foto_bukti']}' width='100' class='img-thumbnail'>" : "";
        
        echo "
        <div class='col-md-6'>
            <div class='panel panel-warning'>
                <div class='panel-heading'>
                    <h4 class='panel-title'>{$row['nama_barang']}</h4>
                </div>
                <div class='panel-body'>
                    <p><strong>Jumlah:</strong> {$row['jumlah']}</p>
                    <p><strong>Alasan:</strong> {$alasan}</p>
                    <p><strong>Keterangan:</strong> {$row['keterangan']}</p>
                    <p><strong>Tanggal:</strong> {$row['tanggal']}</p>
                    {$foto}
                </div>
            </div>
        </div>";
    }
} else {
    echo "<div class='col-xs-12 text-center text-muted'>Belum ada riwayat retur</div>";
}

$conn->close();
?>