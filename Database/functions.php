<?php
require_once 'config.php';

function getAllStokBarang() {
    $pdo = getDBConnection();
    if (!$pdo) {
        error_log("Database connection not available");
        return [];
    }
    
    try {
        $stmt = $pdo->query("SELECT * FROM stok_barang ORDER BY id DESC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getAllStokBarang: " . $e->getMessage());
        return [];
    }
}

function tambahBarang($data) {
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    try {
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
    } catch (PDOException $e) {
        error_log("Error tambahBarang: " . $e->getMessage());
        return false;
    }
}

function updateStokBarang($id, $stok) {
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    try {
        $sql = "UPDATE stok_barang SET stok = stok + ?, updated_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$stok, $id]);
    } catch (PDOException $e) {
        error_log("Error updateStokBarang: " . $e->getMessage());
        return false;
    }
}

function catatTransaksi($data) {
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    try {
        $sql = "INSERT INTO riwayat_transaksi (nama_barang, jenis_transaksi, pemasok, penjual, jumlah, harga, total, keterangan) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            $data['nama_barang'],
            $data['jenis_transaksi'],
            $data['pemasok'],
            $data['penjual'],
            $data['jumlah'],
            $data['harga'],
            $data['total'],
            $data['keterangan']
        ]);
    } catch (PDOException $e) {
        error_log("Error catatTransaksi: " . $e->getMessage());
        return false;
    }
}

function bayarHutang($id, $jumlahBayar, $pembayar) {
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    try {
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

            if ($result) {
                catatRiwayatPembayaran($id, $jumlahBayar, $sisa_sebelum, $sisaHutangBaru, $pembayar);
            }
            
            return $result;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Error bayarHutang: " . $e->getMessage());
        return false;
    }
}

function catatRiwayatPembayaran($hutang_id, $jumlah_bayar, $sisa_sebelum, $sisa_sesudah, $pembayar) {
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    try {
        $sql = "INSERT INTO riwayat_pembayaran_hutang (hutang_id, jumlah_bayar, sisa_hutang_sebelum, sisa_hutang_sesudah, pembayar) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$hutang_id, $jumlah_bayar, $sisa_sebelum, $sisa_sesudah, $pembayar]);
    } catch (PDOException $e) {
        error_log("Error catatRiwayatPembayaran: " . $e->getMessage());
        return false;
    }
}

function getRiwayatPembayaranHutang($hutang_id = null) {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    try {
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
    } catch (PDOException $e) {
        error_log("Error getRiwayatPembayaranHutang: " . $e->getMessage());
        return [];
    }
}
?>