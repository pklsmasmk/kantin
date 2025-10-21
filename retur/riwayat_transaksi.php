<?php
include '../Database/config.php';

$sql = "SELECT * FROM riwayat_transaksi ORDER BY tanggal DESC LIMIT 50";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table class='table table-striped'>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Barang</th>
                    <th>Jenis</th>
                    <th>Keterangan</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['tanggal']}</td>
                <td>{$row['nama_barang']}</td>
                <td>{$row['jenis_transaksi']}</td>
                <td>{$row['keterangan']}</td>
                <td>{$row['user']}</td>
              </tr>";
    }
    
    echo "</tbody></table>";
} else {
    echo "<div class='col-xs-12 text-center text-muted'>Belum ada riwayat transaksi</div>";
}

$conn->close();
?>