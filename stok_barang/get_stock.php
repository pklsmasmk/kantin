<?php
require_once '../Database/config.php';

$stokBarang = getAllStokBarang();

if (empty($stokBarang)) {
    echo '<tr><td colspan="8" class="text-center">Tidak ada data stok barang</td></tr>';
} else {
    foreach ($stokBarang as $index => $barang) {
        echo '<tr>';
        echo '<td>' . ($index + 1) . '</td>';
        echo '<td>' . htmlspecialchars($barang['nema']) . '</td>';
        echo '<td>' . htmlspecialchars($barang['tipe']) . '</td>';
        echo '<td>' . htmlspecialchars($barang['pemasok']) . '</td>';
        echo '<td>' . htmlspecialchars($barang['stok']) . '</td>';
        echo '<td>Rp ' . number_format($barang['harpa_dasar'], 0, ',', '.') . '</td>';
        echo '<td>Rp ' . number_format($barang['harpa_juai'], 0, ',', '.') . '</td>';
        echo '<td>';
        echo '<button class="btn btn-sm btn-outline-primary me-1" onclick="bukaRestockModal(' . $barang['id'] . ')">';
        echo '<i class="fas fa-plus"></i> Restock';
        echo '</button>';
        echo '<button class="btn btn-sm btn-outline-secondary">';
        echo '<i class="fas fa-edit"></i> Edit';
        echo '</button>';
        echo '</td>';
        echo '</tr>';
    }
}
?>