<?php
require_once 'config.php';

function getAllStokBarang() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM stok_barang ORDER BY id DESC");
    return $stmt->fetchAll();
}

function tambahBarang($data) {
    $pdo = getDBConnection();
    $sql = "INSERT INTO stok_barang (nama_barang, tipe, stok, harga_dasar, harga_jual) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['nama_barang'],
        $data['tipe_barang'],
        $data['stok'],
        $data['harga_dasar'],
        $data['harga_jual']
    ]);
}

function updateStokBarang($id, $stok) {
    $pdo = getDBConnection();
    $sql = "UPDATE stok_barang SET stok = stok + ?, updated_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$stok, $id]);
}

function catatTransaksi($data) {
    $pdo = getDBConnection();
    
    // Temporary: disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    
    $sql = "INSERT INTO riwayat_transaksi (nama_barang, jenis_transaksi, pemasok, penjual, jumlah, harga, total, keterangan) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $data['nama_barang'],
        $data['jenis_transaksi'],
        $data['pemasok'],
        $data['penjual'],
        $data['jumlah'],
        $data['harga'],
        $data['total'],
        $data['keterangan']
    ]);
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    
    return $result;
}

function bayarHutang($id, $jumlahBayar, $pembayar) {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT * FROM hutang WHERE id = ?");
    $stmt->execute([$id]);
    $hutang = $stmt->fetch();
    
    if ($hutang) {
        $sisa_sebelum = $hutang['sisa_hutang'];
        $sisaHutangBaru = $sisa_sebelum - $jumlahBayar;
        $status = $sisaHutangBaru <= 0 ? 'lunas' : 'belum_lunas';

        $sql = "UPDATE hutang SET sisa_hutang = ?, status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$sisaHutangBaru, $status, $id]);

        error_log("DEBUG: Bayar hutang - ID: $id, Bayar: $jumlahBayar, Result: " . ($result ? 'true' : 'false'));

        if ($result) {
            $riwayatResult = catatRiwayatPembayaran($id, $jumlahBayar, $sisa_sebelum, $sisaHutangBaru, $pembayar);
            error_log("DEBUG: Riwayat pembayaran - Result: " . ($riwayatResult ? 'true' : 'false'));
        }
        
        return $result;
    }
    
    return false;
}

function catatRiwayatPembayaran($hutang_id, $jumlah_bayar, $sisa_sebelum, $sisa_sesudah, $pembayar) {
    $pdo = getDBConnection();
    
    // DEBUG
    error_log("DEBUG: Catat riwayat - Hutang ID: $hutang_id, Bayar: $jumlah_bayar");
    
    try {
        $sql = "INSERT INTO riwayat_pembayaran_hutang (hutang_id, jumlah_bayar, sisa_hutang_sebelum, sisa_hutang_sesudah, pembayar) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$hutang_id, $jumlah_bayar, $sisa_sebelum, $sisa_sesudah, $pembayar]);
        
        error_log("DEBUG: Insert riwayat - Result: " . ($result ? 'true' : 'false'));
        return $result;
        
    } catch (Exception $e) {
        error_log("ERROR: Gagal catat riwayat - " . $e->getMessage());
        return false;
    }
}

function getRiwayatPembayaranHutang($hutang_id = null) {
    $pdo = getDBConnection();
    
    if ($hutang_id) {
        $stmt = $pdo->prepare("
            SELECT rph.*, h.nama_barang, h.pemasok 
            FROM riwayat_pembayaran_hutang rph
            JOIN hutang h ON rph.hutang_id = h.id
            WHERE rph.hutang_id = ?
            ORDER BY rph.created_at DESC
        ");
        $stmt->execute([$hutang_id]);
    } else {
        $stmt = $pdo->query("
            SELECT rph.*, h.nama_barang, h.pemasok 
            FROM riwayat_pembayaran_hutang rph
            JOIN hutang h ON rph.hutang_id = h.id
            ORDER BY rph.created_at DESC
        ");
    }
    
    return $stmt->fetchAll();
}
?>