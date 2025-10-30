<?php
ob_start(); 
date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("PHP/Back.php");

if (!isUserLoggedIn()) {
    ob_end_clean();
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Akses Ditolak - Shift Kasir UAM</title>
    <link rel="stylesheet" href="../CSS/awal_shift.css" />
</head>
<body>
    <div class="login-required-container">
        <div class="uam-logo">
            <svg class="logo-svg" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                <rect width="40" height="40" rx="8" ry="8" fill="#5d4e37" />
                <text x="20" y="25" text-anchor="middle" fill="white" font-family="Arial" font-size="16" font-weight="bold">UAM</text>
            </svg>
            <span class="logo-text">Shift Kasir</span>
        </div>
        
        <div class="uam-icon">
            <img src="https://maukuliah.ap-south-1.linodeobjects.com/logo/1714374136-CkCJsaBvSM.jpg" alt="Universitas Anwar Medika">
        </div>
        <h1>Akses Ditolak</h1>
        <p>Anda harus login terlebih dahulu untuk mengakses sistem Shift Kasir Kantin UAM.</p>
        
        <div class="features">
            <h3>Fitur yang Tersedia Setelah Login:</h3>
            <ul>
                <li>Manajemen Shift Kasir</li>
                <li>Pencatatan Transaksi Harian</li>
                <li>Monitoring Saldo Cashdrawer</li>
                <li>Laporan Setoran Keuangan</li>
                <li>Riwayat Transaksi Lengkap</li>
            </ul>
        </div>
        
        <a href="?q=login" class="login-btn">Login Sekarang</a>
        
        <div class="help-text">
            <p>Belum memiliki akun? Hubungi administrator sistem.</p>
        </div>
    </div>

    <script src="../JS/awal_shift.js"></script>
</body>
</html>
    <?php
    exit;
}

include("../Database/config.php");

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Error: Koneksi database tidak valid. Periksa file config.php");
}

try {
    $pdo->query("SELECT 1");
} catch (PDOException $e) {
    die("Error: Koneksi database gagal - " . $e->getMessage());
}


function read_shift_data($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM shifts ORDER BY waktu_mulai DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error read_shift_data: " . $e->getMessage());
        return [];
    }
}

function save_shift_data($pdo, $data) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO shifts (id, nama, role, cashdrawer, saldo_awal, saldo_akhir, waktu_mulai, waktu_selesai) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                cashdrawer = VALUES(cashdrawer),
                saldo_awal = VALUES(saldo_awal),
                saldo_akhir = VALUES(saldo_akhir),
                waktu_mulai = VALUES(waktu_mulai),
                waktu_selesai = VALUES(waktu_selesai),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([
            $data['id'],
            $data['nama'],
            $data['role'],
            $data['cashdrawer'],
            $data['saldo_awal'],
            $data['saldo_akhir'],
            $data['waktu_mulai'],
            $data['waktu_selesai']
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Error save_shift_data: " . $e->getMessage());
        return false;
    }
}

function sync_to_rekap($pdo, $shift_data) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM rekap_shift WHERE shift_id = ?");
        $stmt->execute([$shift_data['id']]);
        $existing = $stmt->fetch();
        
        $selisih = $shift_data['saldo_akhir'] - $shift_data['saldo_awal'];
        
        if ($existing) {
            $stmt = $pdo->prepare("
                UPDATE rekap_shift SET 
                    cashdrawer = ?, saldo_awal = ?, saldo_akhir = ?, selisih = ?,
                    waktu_mulai = ?, waktu_selesai = ?, last_updated = ?
                WHERE shift_id = ?
            ");
            $stmt->execute([
                $shift_data['cashdrawer'],
                $shift_data['saldo_awal'],
                $shift_data['saldo_akhir'],
                $selisih,
                $shift_data['waktu_mulai'],
                $shift_data['waktu_selesai'] ?? null,
                date('Y-m-d H:i:s'),
                $shift_data['id']
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO rekap_shift 
                (shift_id, cashdrawer, saldo_awal, saldo_akhir, total_penjualan, total_pengeluaran, 
                 total_pemasukan_lain, total_pengeluaran_lain, selisih, waktu_mulai, waktu_selesai, 
                 kasir, role, last_updated)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $shift_data['id'],
                $shift_data['cashdrawer'],
                $shift_data['saldo_awal'],
                $shift_data['saldo_akhir'],0, 0, 0, 0, 
                $selisih,
                $shift_data['waktu_mulai'],
                $shift_data['waktu_selesai'] ?? null,
                $shift_data['nama'],
                $shift_data['role'],
                date('Y-m-d H:i:s')
            ]);
        }
        
        return [
            'shift_id' => $shift_data['id'],
            'cashdrawer' => $shift_data['cashdrawer'],
            'saldo_awal' => $shift_data['saldo_awal'],
            'saldo_akhir' => $shift_data['saldo_akhir'],
            'selisih' => $selisih,
            'waktu_mulai' => $shift_data['waktu_mulai'],
            'waktu_selesai' => $shift_data['waktu_selesai'] ?? null
        ];
    } catch (PDOException $e) {
        error_log("Error sync_to_rekap: " . $e->getMessage());
        return null;
    }
}


function save_saldo_warisan($pdo, $saldo_akhir) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO saldo_warisan (saldo_akhir, created_at) 
            VALUES (?, NOW())
        ");
        $stmt->execute([$saldo_akhir]);
        return true;
    } catch (PDOException $e) {
        error_log("Error save_saldo_warisan: " . $e->getMessage());
        return false;
    }
}

function get_saldo_warisan($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT saldo_akhir 
            FROM saldo_warisan 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['saldo_akhir'] > 0) {
            return $result['saldo_akhir'];
        }
        
        $stmt = $pdo->prepare("
            SELECT saldo_akhir 
            FROM shifts 
            WHERE waktu_selesai IS NOT NULL 
            ORDER BY waktu_selesai DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['saldo_akhir'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error get_saldo_warisan: " . $e->getMessage());
        return 0;
    }
}

function akhiri_shift($pdo, $shift_id, $saldo_akhir) {
    try {
        $stmt = $pdo->prepare("
            UPDATE shifts SET 
                waktu_selesai = NOW(),
                saldo_akhir = ?
            WHERE id = ?
        ");
        $stmt->execute([$saldo_akhir, $shift_id]);
        
        save_saldo_warisan($pdo, $saldo_akhir);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error akhiri_shift: " . $e->getMessage());
        return false;
    }
}

function sync_from_rekap($pdo, $shift_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM rekap_shift WHERE shift_id = ?");
        $stmt->execute([$shift_id]);
        $rekap = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($rekap) {
            $stmt = $pdo->prepare("
                UPDATE shifts SET 
                    saldo_akhir = ?, saldo_awal = ?, cashdrawer = ?,
                    waktu_mulai = ?, waktu_selesai = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $rekap['saldo_akhir'],
                $rekap['saldo_awal'],
                $rekap['cashdrawer'],
                $rekap['waktu_mulai'],
                $rekap['waktu_selesai'],
                $shift_id
            ]);
            
            return $rekap;
        }
        return null;
    } catch (PDOException $e) {
        error_log("Error sync_from_rekap: " . $e->getMessage());
        return null;
    }
}

function get_jenis_setoran_display($jenis) {
    $jenis_map = [
        'kantor_pusat' => 'Setoran ke Pusat',
        'lainnya' => 'Setoran Lainnya'
    ];
    return $jenis_map[$jenis] ?? 'Setoran';
}

function get_metode_setoran_display($metode) {
    $metode_map = [
        'tunai' => 'Tunai',
        'transfer' => 'Transfer Bank'
    ];
    return $metode_map[$metode] ?? 'Tunai';
}

function calculate_total_setoran_bulan_ini($pdo) {
    try {
        $current_month = date('Y-m');
        $stmt = $pdo->prepare("
            SELECT SUM(jumlah) as total 
            FROM setoran 
            WHERE DATE_FORMAT(waktu, '%Y-%m') = ?
        ");
        $stmt->execute([$current_month]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error calculate_total_setoran_bulan_ini: " . $e->getMessage());
        return 0;
    }
}

function calculate_rata_rata_setoran($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                SUM(jumlah) as total,
                COUNT(DISTINCT DATE(waktu)) as hari
            FROM setoran
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $total = $result['total'] ?? 0;
        $hari = $result['hari'] ?? 0;
        
        return $hari > 0 ? $total / $hari : 0;
    } catch (PDOException $e) {
        error_log("Error calculate_rata_rata_setoran: " . $e->getMessage());
        return 0;
    }
}

function calculate_total_setoran_hari_ini($pdo) {
    try {
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("
            SELECT SUM(jumlah) as total 
            FROM setoran 
            WHERE DATE(waktu) = ?
        ");
        $stmt->execute([$today]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error calculate_total_setoran_hari_ini: " . $e->getMessage());
        return 0;
    }
}

function get_setoran_hari_ini($pdo) {
    try {
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("
            SELECT *, 
                CASE jenis 
                    WHEN 'kantor_pusat' THEN 'Setoran ke Pusat'
                    WHEN 'lainnya' THEN 'Setoran Lainnya'
                    ELSE 'Setoran'
                END as jenis_display,
                CASE metode 
                    WHEN 'tunai' THEN 'Tunai'
                    WHEN 'transfer' THEN 'Transfer Bank'
                    ELSE 'Tunai'
                END as metode_display
            FROM setoran 
            WHERE DATE(waktu) = ?
            ORDER BY waktu DESC
        ");
        $stmt->execute([$today]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error get_setoran_hari_ini: " . $e->getMessage());
        return [];
    }
}


function calculate_saldo_tersedia($pdo, $currentShift = null) {
    if ($currentShift) {
        $total_setoran_hari_ini = calculate_total_setoran_hari_ini($pdo);
        $saldo_tersedia = $currentShift['saldo_akhir'] - $total_setoran_hari_ini;
        return max(0, $saldo_tersedia);
    } else {
        $saldo_warisan = get_saldo_warisan($pdo);
        $total_setoran_hari_ini = calculate_total_setoran_hari_ini($pdo);
        $saldo_tersedia = $saldo_warisan - $total_setoran_hari_ini;
        return max(0, $saldo_tersedia);
    }
}

function get_rekap_data_for_shift($pdo, $shift_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM rekap_shift WHERE shift_id = ?");
        $stmt->execute([$shift_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error get_rekap_data_for_shift: " . $e->getMessage());
        return null;
    }
}

function get_saldo_akhir_dari_riwayat($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT saldo_akhir 
            FROM shifts 
            ORDER BY waktu_mulai DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['saldo_akhir'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error get_saldo_akhir_dari_riwayat: " . $e->getMessage());
        return 0;
    }
}

function update_current_shift_from_rekap($pdo, $current_shift) {
    if (!$current_shift || !isset($current_shift['id'])) {
        return $current_shift;
    }
    
    $rekap_data = get_rekap_data_for_shift($pdo, $current_shift['id']);
    if ($rekap_data) {
        $current_shift['saldo_akhir'] = $rekap_data['saldo_akhir'];
        $current_shift['saldo_awal'] = $rekap_data['saldo_awal'];
        $current_shift['cashdrawer'] = $rekap_data['cashdrawer'];
        $current_shift['waktu_mulai'] = $rekap_data['waktu_mulai'];
        $current_shift['waktu_selesai'] = $rekap_data['waktu_selesai'] ?? null;
        
        $_SESSION["shift_current"] = $current_shift;
        
        save_shift_data($pdo, $current_shift);
    }
    
    return $current_shift;
}

function format_rupiah($value) {
    return "Rp " . number_format($value, 0, ",", ".");
}

function format_datetime($datetime) {
    $date = date_create_from_format("Y-m-d H:i:s", $datetime);
    return $date ? $date->format("d M Y • H:i") : $datetime;
}

function format_time($datetime) {
    $date = date_create_from_format("Y-m-d H:i:s", $datetime);
    return $date ? $date->format("H:i") : $datetime;
}


if (!isset($_SESSION["shift_history"])) {
    $_SESSION["shift_history"] = read_shift_data($pdo);
}

$saldo_warisan = get_saldo_warisan($pdo);
$today = date('Y-m-d');
$total_setoran_hari_ini = calculate_total_setoran_hari_ini($pdo);
$setoran_hari_ini = get_setoran_hari_ini($pdo);

$currentShift = $_SESSION["shift_current"] ?? null;

if ($currentShift) {
    $currentShift = update_current_shift_from_rekap($pdo, $currentShift);
    $_SESSION["shift_current"] = $currentShift;
}

$saldo_akhir_riwayat = get_saldo_akhir_dari_riwayat($pdo);

if ($currentShift) {
    $saldo_awal_hari_ini = $currentShift['saldo_awal'];
    $saldo_akhir_hari_ini = $currentShift['saldo_akhir'];
    $saldo_tersedia = calculate_saldo_tersedia($pdo, $currentShift);
    $can_setor = $saldo_tersedia > 0;
    $saldo_akhir_display = $currentShift['saldo_akhir'];
} else {
    $saldo_awal_hari_ini = 0;
    $saldo_akhir_hari_ini = $saldo_warisan;
    $saldo_tersedia = calculate_saldo_tersedia($pdo, null);
    $can_setor = $saldo_tersedia > 0;
    $saldo_akhir_display = $saldo_warisan;
}

$cashdrawers = [
    "Kasir 01 - Cashdrawer 1",
    "Kasir 02 - Cashdrawer 2", 
    "Kasir 03 - Cashdrawer 3",
];

try {
    $stmt = $pdo->prepare("SELECT nama FROM cashdrawers WHERE is_active = TRUE ORDER BY nama");
    $stmt->execute();
    $db_cashdrawers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($db_cashdrawers)) {
        $cashdrawers = $db_cashdrawers;
    }
} catch (PDOException $e) {
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['setoran_action'])) {
    if ($_POST['setoran_action'] === 'add') {
        $penyetor = trim($_POST['penyetor'] ?? $_SESSION['namalengkap'] ?? '');
        $jumlah_input = $_POST['jumlah_setoran'] ?? '';
        $jenis_setoran = $_POST['jenis_setoran'] ?? '';
        $metode_setoran = $_POST['metode_setoran'] ?? '';
        $keterangan = trim($_POST['keterangan_setoran'] ?? '');
        $detail_lainnya = trim($_POST['detail_lainnya'] ?? '');
        
        $bukti_transfer_name = null;
        $bukti_transfer_path = null;
        
        if ($metode_setoran === 'transfer' && isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = "uploads/bukti_transfer/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file = $_FILES['bukti_transfer'];
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
            $max_file_size = 5 * 1024 * 1024;
            
            if (in_array(strtolower($file_extension), $allowed_extensions) && $file['size'] <= $max_file_size) {
                $bukti_transfer_name = uniqid('bukti_', true) . '.' . $file_extension;
                $bukti_transfer_path = $upload_dir . $bukti_transfer_name;
                
                if (!move_uploaded_file($file['tmp_name'], $bukti_transfer_path)) {
                    $_SESSION["error"] = "Gagal mengupload bukti transfer.";
                    echo "<script>window.location.href='" . $_SERVER["PHP_SELF"] . "?tab=setoran';</script>";
                    exit;
                }
            } else {
                $_SESSION["error"] = "File bukti transfer tidak valid. Format: JPG, PNG, PDF, DOC (Maks. 5MB)";
                echo "<script>window.location.href='" . $_SERVER["PHP_SELF"] . "?tab=setoran';</script>";
                exit;
            }
        } elseif ($metode_setoran === 'transfer') {
            $_SESSION["error"] = "Bukti transfer wajib diupload untuk metode transfer.";
            echo "<script>window.location.href='" . $_SERVER["PHP_SELF"] . "?tab=setoran';</script>";
            exit;
        }

        if (empty($penyetor) || empty($jumlah_input) || empty($jenis_setoran) || empty($metode_setoran) || empty($keterangan)) {
            $_SESSION["error"] = "Semua field bertanda * harus diisi.";
        } else {
            $jumlah_clean = preg_replace("/[^\d]/", "", $jumlah_input);
            $jumlah_value = $jumlah_clean !== "" ? (float) $jumlah_clean : 0;

            if ($jumlah_value <= 0) {
                $_SESSION["error"] = "Jumlah setoran harus lebih besar dari 0.";
            } else {
                if (!$can_setor) {
                    $_SESSION["error"] = "Tidak dapat melakukan setoran. Saldo tidak mencukupi.";
                } elseif ($jumlah_value > $saldo_tersedia) {
                    $_SESSION["error"] = "Saldo tidak mencukupi. Saldo tersedia: " . format_rupiah($saldo_tersedia);
                } else {
                    $setoran_id = uniqid('setoran_', true);
                    $stmt = $pdo->prepare("
                        INSERT INTO setoran 
                        (id, penyetor, jumlah, jenis, metode, keterangan, detail_lainnya, waktu, shift_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $setoran_id,
                        $penyetor,
                        $jumlah_value,
                        $jenis_setoran,
                        $metode_setoran,
                        $keterangan,
                        $detail_lainnya,
                        date('Y-m-d H:i:s'),
                        $_SESSION['shift_current']['id'] ?? null
                    ]);

                    if ($currentShift) {
                        $new_saldo_akhir = $currentShift['saldo_akhir'] - $jumlah_value;
                        
                        $stmt = $pdo->prepare("UPDATE shifts SET saldo_akhir = ? WHERE id = ?");
                        $stmt->execute([$new_saldo_akhir, $currentShift['id']]);
                        
                        $_SESSION["shift_current"]['saldo_akhir'] = $new_saldo_akhir;
                        $_SESSION["shift_history"][0]['saldo_akhir'] = $new_saldo_akhir;
                        
                        sync_to_rekap($pdo, $_SESSION["shift_current"]);
                    } else {
                        $new_saldo_warisan = $saldo_warisan - $jumlah_value;
                        save_saldo_warisan($pdo, $new_saldo_warisan);
                    }
                    
                    $_SESSION["success"] = "Setoran berhasil disimpan. Saldo tersisa: " . format_rupiah($saldo_tersedia - $jumlah_value);
                }
            }
        }
        
        echo "<script>window.location.href='" . $_SERVER["PHP_SELF"] . "?tab=setoran';</script>";
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'rekap_shift') {
    if (isset($_SESSION["shift_current"])) {
        $_SESSION['shift'] = $_SESSION["shift_current"];
        
        if (!isset($_SESSION['transaksi'])) {
            $_SESSION['transaksi'] = [];
        }
        
        $sync_result = sync_to_rekap($pdo, $_SESSION["shift_current"]);
        
        echo "<script>window.location.href='Rekap_Shift/rekap_shift.php';</script>";
        exit;
    } else {
        $_SESSION["error"] = "Silakan mulai shift terlebih dahulu sebelum melihat rekap shift.";
        echo "<script>window.location.href='" . $_SERVER["PHP_SELF"] . "?tab=current';</script>";
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'akhiri_shift' && $currentShift) {
    save_saldo_warisan($pdo, $currentShift['saldo_akhir']);
    
    $stmt = $pdo->prepare("UPDATE shifts SET waktu_selesai = NOW() WHERE id = ?");
    $stmt->execute([$currentShift['id']]);
    
    $_SESSION["shift_current"]['waktu_selesai'] = date("Y-m-d H:i:s");
    $_SESSION["success"] = "Shift berhasil diakhiri. Saldo akhir: " . format_rupiah($currentShift['saldo_akhir']) . " telah disimpan sebagai warisan.";
    
    unset($_SESSION["shift_current"]);
    
    header("Location: " . $_SERVER["PHP_SELF"] . "?tab=current");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['setoran_action'])) {
    $cashdrawer = isset($_POST["cashdrawer"]) ? trim($_POST["cashdrawer"]) : "";
    $saldo_awal = isset($_POST["saldo_awal"]) ? trim($_POST["saldo_awal"]) : "";
    $confirmed = isset($_POST["confirmed"]) ? $_POST["confirmed"] === "true" : false;

    if ($cashdrawer === "") {
        $_SESSION["error"] = "Cashdrawer harus dipilih.";
    } else {
        $saldo_clean = preg_replace("/[^\d]/", "", $saldo_awal);
        $saldo_value = $saldo_clean !== "" ? (float) $saldo_clean : NAN;

        if (!is_numeric($saldo_value) || $saldo_value <= 0) {
            $_SESSION["error"] = "Saldo awal harus berupa angka yang lebih besar dari 0.";
        } else if (!$confirmed) {
            $_SESSION["pending_shift"] = [
                "cashdrawer" => $cashdrawer,
                "saldo_awal" => $saldo_value,
                "saldo_warisan" => $saldo_warisan
            ];
            $_SESSION["show_confirmation"] = true;
        } else {
            $saldo_akhir = $saldo_value + $saldo_warisan;
            
            $shift = [
                "id"         => uniqid("shift_", true),
                "nama"       => $_SESSION['namalengkap'] ?? "namalengkap", 
                "role"       => $_SESSION['nama'] ?? "nama",
                "cashdrawer" => $cashdrawer,
                "saldo_awal" => $saldo_value, 
                "saldo_akhir" => $saldo_akhir,
                "waktu_mulai" => date("Y-m-d H:i:s"),
                "waktu_selesai" => null
            ];

            $_SESSION["shift_current"] = $shift;
            $_SESSION["transaksi"] = [];
            
            if (!isset($_SESSION["shift_history"]) || !is_array($_SESSION["shift_history"])) {
                $_SESSION["shift_history"] = [];
            }
            array_unshift($_SESSION["shift_history"], $shift);
            
            save_shift_data($pdo, $shift);
            sync_to_rekap($pdo, $shift);

            unset($_SESSION["pending_shift"]);
            unset($_SESSION["show_confirmation"]);

            $_SESSION["success"] = "Shift berhasil dimulai! Saldo awal: " . format_rupiah($saldo_value) . 
                                ($saldo_warisan > 0 ? " + Warisan: " . format_rupiah($saldo_warisan) : "");
        }
    }

    echo "<script>window.location.href='" . $_SERVER["PHP_SELF"] . "?tab=current';</script>";
    exit;
}

$active_tab = isset($_GET['tab']) && in_array($_GET['tab'], ['current', 'setoran', 'history']) ? $_GET['tab'] : 'current';
$history = $_SESSION["shift_history"] ?? [];
$setoran_data = $_SESSION["setoran_data"] ?? [];
$total_setoran_bulan_ini = calculate_total_setoran_bulan_ini($pdo);
$rata_rata_setoran = calculate_rata_rata_setoran($pdo);
$error = $_SESSION["error"] ?? null;
$success = $_SESSION["success"] ?? null;

unset($_SESSION["error"], $_SESSION["success"]);

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Shift Kasir - UAM</title>
    <link rel="stylesheet" href="../CSS/shift.css" />
    <link rel="stylesheet" href="../CSS/modal.css" />
    <link rel="stylesheet" href="../CSS/tambahan.css" />
    <link rel="stylesheet" href="../CSS/tambahan_histori.css" />
</head>
<body>
    <div class="container" role="main" aria-label="Halaman Shift Kasir">
        <header>
            <div class="logo" aria-label="UAM Logo">
                <svg width="28" height="28" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <rect width="28" height="28" rx="8" ry="8" fill="#5d4e37" />
                </svg>
                <h1>Shift Kasir Kantin</h1>
            </div>
        </header>

        <main>
            <h2>Kasir Kantin Universitas Anwar Medika</h2>

            <nav class="tabs" role="tablist" aria-label="Navigasi Shift">
                <button role="tab" aria-selected="<?= $active_tab === 'current' ? 'true' : 'false' ?>" 
                        class="<?= $active_tab === 'current' ? 'active' : '' ?>" 
                        tabindex="0" data-tab="current">Cashdrawer</button>
                <button role="tab" aria-selected="<?= $active_tab === 'setoran' ? 'true' : 'false' ?>" 
                        class="<?= $active_tab === 'setoran' ? 'active' : '' ?>" 
                        tabindex="-1" data-tab="setoran">Setoran</button>
                <button role="tab" aria-selected="<?= $active_tab === 'history' ? 'true' : 'false' ?>" 
                        class="<?= $active_tab === 'history' ? 'active' : '' ?>" 
                        tabindex="-1" data-tab="history">Riwayat</button>
            </nav>

            <section class="user-info">
                <summary class="avatar">
                    <img src="https://gravatar.com/avatar/00000000000000000000000000000000?d=mp">
                </summary>
                <div class="user-name-role">
                    <strong><?=$_SESSION['namalengkap']?></strong>
                    <strong><?=$_SESSION['nama']?></strong>
                </div>
            </section>

            <?php if (!empty($error)): ?>
                <div class="alert error" role="status"><?= htmlspecialchars($error) ?></div>
            <?php elseif (!empty($success)): ?>
                <div class="alert success" role="status"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <section class="tab-panel current-panel <?= $active_tab === 'current' ? 'is-active' : '' ?>" data-tab-panel="current">
                <section class="info">
                    <p>
                        <strong>Sistem Shift Kasir Kantin UAM</strong><br>
                        Aplikasi kasir dengan sistem warisan saldo otomatis dan manajemen setoran terintegrasi.
                    </p>
                    <div style="margin-top: 12px; padding-left: 16px; border-left: 3px solid #8B7355;">
                        <strong>Alur Kerja Sistem:</strong>
                        <ul style="margin: 8px 0; padding-left: 20px; color: #555;">
                            <li><strong>Mulai Shift</strong> - Input saldo awal, sistem otomatis tambah saldo warisan</li>
                            <li><strong>Operasional</strong> - Transaksi penjualan, pengeluaran, pemasukan/pengeluaran lain</li>
                            <li><strong>Setoran Fleksibel</strong> - Setor kapan saja dari saldo akhir yang tersedia</li>
                            <li><strong>Rekap Detail</strong> - Monitoring lengkap transaksi dan saldo</li>
                            <li><strong>Akhiri Shift</strong> - Sistem hitung otomatis, saldo akhir jadi warisan berikutnya</li>
                        </ul>
                        
                        <div style="margin-top: 10px; padding: 8px; background: #fff3cd; border-radius: 6px;">
                            <small><strong>Saldo Warisan:</strong> Saldo akhir shift sebelumnya otomatis menjadi bagian saldo awal shift baru</small>
                        </div>
                    </div>
                </section>
                
                <?php if ($saldo_warisan > 0): ?>
                <div class="saldo-warisan-info">
                    <small>Saldo warisan dari shift sebelumnya: <strong><?= format_rupiah($saldo_warisan) ?></strong> akan ditambahkan ke saldo awal</small>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="shift-form" novalidate>
                    <label for="cashdrawer">Pilih Cashdrawer</label>
                    <div class="refresh-wrapper">
                        <select id="cashdrawer" name="cashdrawer" required>
                            <option value="">-- Pilih Cashdrawer --</option>
                            <?php foreach ($cashdrawers as $option): ?>
                                <option value="<?= htmlspecialchars($option) ?>">
                                    <?= htmlspecialchars($option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="refresh-btn" title="Refresh cashdrawer" id="refreshCashdrawer">&#x21bb;</button>
                    </div>

                    <label for="saldo_awal">Masukkan Saldo Awal</label>
                    <div class="input-rp">
                        <span class="rp-label">Rp</span>
                        <input
                            type="text"
                            id="saldo_awal"
                            name="saldo_awal"
                            placeholder="0"
                            autocomplete="off"
                            inputmode="numeric"
                            pattern="[0-9.,]*"
                            required
                            value=""
                        />
                    </div>
                    <small id="lastData" class="last-data">
                        <?php if (!empty($history)): ?>
                            Data terakhir: <?= htmlspecialchars($history[0]["cashdrawer"]) ?> •
                            Saldo awal: <?= format_rupiah($history[0]["saldo_awal"]) ?> • Saldo akhir: <?= format_rupiah($history[0]["saldo_akhir"]) ?> •
                            <?= format_datetime($history[0]["waktu_mulai"]) ?>
                        <?php else: ?>
                            Silahkan isi field diatas untuk memulai shift Anda
                        <?php endif; ?>
                    </small>
                    
                    <?php if ($saldo_warisan > 0): ?>
                    <div class="warisan-notice">
                        <small><strong>SALDO WARISAN:</strong> <?= format_rupiah($saldo_warisan) ?> akan ditambahkan ke saldo awal Anda</small>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="submit-btn" id="submitShiftBtn">
                        <?php if ($saldo_warisan > 0): ?>
                            Mulai Shift Anda - Total: <?= format_rupiah($saldo_warisan) ?> + Saldo Awal
                        <?php else: ?>
                            Mulai Shift Anda
                        <?php endif; ?>
                    </button>

                    <div class="action-buttons">
                        <a href="/?q=shift__Rekap_Shift__rekap_shift" class="action-btn manage-cash-btn" <?= !$currentShift ? 'style="opacity: 0.6; pointer-events: none;"' : '' ?>>
                            <span>Rekap Shift</span>
                        </a>
                        <a href="../index.php" class="action-btn cart-btn">
                            <span>Pergi ke Menu Utama</span>
                        </a>
                    </div>
                </form>
            </section>

            <div id="confirmationModal" class="selimut-modal">
                <div class="konten-modal-konfirmasi">
                    <div class="header-modal-konfirmasi">
                        <div class="judul-modal">Konfirmasi Mulai Shift</div>
                        <p>Apakah Anda yakin ingin memulai shift dengan data berikut?</p>
                    </div>
                    
                    <div class="body-modal-konfirmasi">
                        <div class="detail-konfirmasi">
                            <div class="item-detail">
                                <span class="label-detail">Cashdrawer:</span>
                                <span class="nilai-detail" id="modalCashdrawer"></span>
                            </div>
                            <div class="item-detail">
                                <span class="label-detail">Saldo Awal:</span>
                                <span class="nilai-detail" id="modalSaldoAwal"></span>
                            </div>
                            <?php if ($saldo_warisan > 0): ?>
                            <div class="item-detail">
                                <span class="label-detail">Saldo Warisan:</span>
                                <span class="nilai-detail" id="modalSaldoWarisan"></span>
                            </div>
                            <div class="item-detail">
                                <span class="label-detail">Total Saldo:</span>
                                <span class="nilai-detail" id="modalTotalSaldo"></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="catatan-warisan">
                            <small><strong>PERHATIAN:</strong> Setelah shift dimulai, data akan tersimpan di sistem dan masuk ke riwayat shift.</small>
                        </div>
                    </div>
                    
                    <div class="footer-modal-konfirmasi">
                        <button type="button" class="tombol tombol-batal" id="cancelBtn">Tidak, Kembali</button>
                        <button type="button" class="tombol tombol-konfirmasi" id="confirmBtn">Ya, Mulai Shift</button>
                    </div>
                </div>
            </div>
            
            <form id="confirmedForm" method="POST" class="hidden-form">
                <input type="hidden" name="cashdrawer" id="confirmedCashdrawer">
                <input type="hidden" name="saldo_awal" id="confirmedSaldoAwal">
                <input type="hidden" name="confirmed" value="true">
            </form>


            <section class="tab-panel setoran-panel <?= $active_tab === 'setoran' ? 'is-active' : '' ?>" data-tab-panel="setoran" aria-label="Manajemen setoran kas">
                <div class="setoran-header">
                    <h3>Manajemen Setoran Keuangan</h3>
                    <p>Sistem setoran terintegrasi dengan saldo akhir</p>
                </div>

                <div class="shift-status-info">
                    <?php if ($currentShift): ?>
                        <div class="status-active">
                            <span class="status-badge">🟢 Shift Aktif</span>
                            <small>
                                <?= htmlspecialchars($currentShift['cashdrawer']) ?> • 
                                Saldo awal: <?= format_rupiah($currentShift['saldo_awal']) ?> • 
                                Saldo akhir: <?= format_rupiah($currentShift['saldo_akhir']) ?> •
                                Saldo tersedia untuk setor: <?= format_rupiah($saldo_tersedia) ?>
                            </small>
                        </div>
                    <?php else: ?>
                        <div class="status-inactive">
                            <span class="status-badge"><?= $saldo_akhir_riwayat > 0 ? '🟡 Saldo Tersedia' : '🔴 Tidak Ada Saldo' ?></span>
                            <small>
                                <?php if ($saldo_akhir_riwayat > 0): ?>
                                    Saldo akhir dari riwayat: <?= format_rupiah($saldo_akhir_riwayat) ?> • 
                                    Saldo tersedia untuk setor: <?= format_rupiah($saldo_tersedia) ?> •
                                    <?= $can_setor ? 'Dapat melakukan setoran' : 'Tidak dapat setoran' ?>
                                <?php else: ?>
                                    Tidak ada saldo silahkan mulai shift terlebih dahulu
                                <?php endif; ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="setoran-form-section">
                    <form method="POST" class="setoran-form" novalidate enctype="multipart/form-data">
                        <input type="hidden" name="setoran_action" value="add">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="penyetor">Nama Penyetor *</label>
                                <strong><?=$_SESSION['namalengkap']?></strong>
                            </div>
                            
                            <div class="form-group">
                                <label for="jumlah_setoran">Jumlah Setoran (Rp) *</label>
                                <div class="input-rp">
                                    <input type="text" id="jumlah_setoran" name="jumlah_setoran" required 
                                        placeholder="0" inputmode="numeric" <?= !$can_setor ? 'disabled' : '' ?>>
                                </div>
                                <?php if ($can_setor): ?>
                                    <small class="saldo-tersedia">Saldo tersedia untuk disetor: <?= format_rupiah($saldo_tersedia) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="jenis_setoran">Jenis Setoran *</label>
                                <select id="jenis_setoran" name="jenis_setoran" required <?= !$can_setor ? 'disabled' : '' ?>>
                                    <option value="">-- Pilih Jenis Setoran --</option>
                                    <option value="kantor_pusat">Setoran ke Pusat</option>
                                    <option value="lainnya">Lainnya</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="metode_setoran">Metode Setoran *</label>
                                <select id="metode_setoran" name="metode_setoran" required <?= !$can_setor ? 'disabled' : '' ?>>
                                    <option value="">-- Pilih Metode --</option>
                                    <option value="tunai">Tunai</option>
                                    <option value="transfer">Transfer Bank</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="keterangan_setoran">Keterangan Setoran *</label>
                            <textarea id="keterangan_setoran" name="keterangan_setoran" rows="3" 
                                    placeholder="Contoh: Setoran penjualan harian tanggal..." required <?= !$can_setor ? 'disabled' : '' ?>></textarea>
                        </div>

                        <div class="form-group" id="detail_lainnya_group" style="display: none;">
                            <label for="detail_lainnya">Detail Tambahan</label>
                            <input type="text" id="detail_lainnya" name="detail_lainnya" 
                                placeholder="Masukkan detail tambahan..." <?= !$can_setor ? 'disabled' : '' ?>>
                        </div>

                        <div class="form-group" id="bukti_transfer_group" style="display: none;">
                            <label for="bukti_transfer">Upload Bukti Transfer *</label>
                            <div class="file-input-wrapper">
                                <input type="file" id="bukti_transfer" name="bukti_transfer" 
                                    accept="image/*,.pdf,.doc,.docx" <?= !$can_setor ? 'disabled' : '' ?>>
                                <label for="bukti_transfer" class="file-input-label" id="fileInputLabel">
                                    📎 Klik untuk upload bukti transfer
                                    <div class="file-name" id="fileName"></div>
                                </label>
                            </div>
                            <small>Format: JPG, PNG, PDF, DOC (Maks. 5MB)</small>
                        </div>

                        <button type="submit" class="submit-btn setoran-submit" <?= !$can_setor ? 'disabled' : '' ?>>
                            <?php if ($can_setor): ?>
                                Simpan Setoran dari Saldo Akhir
                            <?php else: ?>
                                <?= $saldo_akhir_riwayat > 0 ? 'Tidak Dapat Setoran' : 'Tidak Ada Saldo' ?>
                            <?php endif; ?>
                        </button>
                        
                        <?php if (!$can_setor && $saldo_akhir_riwayat > 0): ?>
                            <div class="isi-ulang-info">
                                <small><strong>Tidak ada saldo tersisa untuk disetor. </strong> Silakan mulai shift baru untuk menambah saldo.</small>
                            </div>
                        <?php elseif (!$can_setor): ?>
                            <div class="warning-message">
                                <small>Tidak ada saldo, silahkan mulai shift terlebih dahulu</small>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="saldo-summary">
                    <h4>Ringkasan Saldo untuk Setoran</h4>
                    <div class="saldo-cards">
                        <div class="saldo-card">
                            <div class="saldo-info">
                                <span class="saldo-label">Saldo Awal</span>
                                <strong class="saldo-value"><?= format_rupiah($saldo_awal_hari_ini) ?></strong>
                            </div>
                        </div>
                        <div class="saldo-card">
                            <div class="saldo-info">
                                <span class="saldo-label">Total Setoran</span>
                                <strong class="saldo-value"><?= format_rupiah($total_setoran_hari_ini) ?></strong>
                                <small><?= count($setoran_hari_ini) ?> transaksi</small>
                            </div>
                        </div>
                        <div class="saldo-card highlight">
                            <div class="saldo-info">
                                <span class="saldo-label">Saldo Akhir</span>
                                <strong class="saldo-value"><?= format_rupiah($saldo_akhir_display) ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="warisan-info">
                        <div class="info-item">
                            <span class="info-label">Sumber Setoran:</span>
                            <span class="info-value">
                                Saldo akhir dari shift (berjalan/riwayat)
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Rumus Setoran:</span>
                            <span class="info-value">
                                Saldo Tersedia = Saldo Akhir - Total Setoran Hari Ini
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status Setoran:</span>
                            <span class="info-value">
                                <?php if ($saldo_tersedia > 0): ?>
                                    <span style="color: #28a745;">● Masih bisa setor (<?= format_rupiah($saldo_tersedia) ?> tersedia)</span>
                                <?php else: ?>
                                    <span style="color: #dc3545;">● Saldo habis, mulai shift baru</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="setoran-hari-ini">
                    <h4>Setoran Hari Ini (<?= date('d M Y') ?>)</h4>
                    <?php if (empty($setoran_hari_ini)): ?>
                        <div class="empty-state">
                            <strong>Belum ada setoran hari ini</strong>
                            <p>Mulai dengan menambahkan setoran pertama dari saldo akhir</p>
                        </div>
                    <?php else: ?>
                        <div class="setoran-summary">
                            <div class="summary-item">
                                <small>Total Setoran:</small>
                                <strong><?= format_rupiah($total_setoran_hari_ini) ?></strong>
                            </div>
                            <div class="summary-item">
                                <small>Jumlah Setoran:</small>
                                <strong><?= count($setoran_hari_ini) ?></strong>
                            </div>
                            <div class="summary-item">
                                <small>Saldo Tersisa:</small>
                                <strong><?= format_rupiah($saldo_tersedia) ?></strong>
                            </div>
                        </div>

                        <div class="setoran-list">
                            <?php foreach ($setoran_hari_ini as $index => $setoran): ?>
                                <div class="setoran-item">
                                    <div class="setoran-info">
                                        <div class="setoran-header-info">
                                            <strong><?= htmlspecialchars($setoran['penyetor']) ?></strong>
                                            <span class="setoran-amount"><?= format_rupiah($setoran['jumlah']) ?></span>
                                        </div>
                                        <div class="setoran-details">
                                            <span class="setoran-type"><?= $setoran['jenis_display'] ?></span>
                                            <span class="setoran-method">• <?= $setoran['metode_display'] ?></span>
                                            <span class="setoran-time">• <?= date('H:i', strtotime($setoran['waktu'])) ?></span>
                                        </div>
                                        <div class="setoran-desc">
                                            <?= htmlspecialchars($setoran['keterangan']) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="setoran-statistics">
                    <h4>Statistik Setoran</h4>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-info">
                                <span class="stat-label">Total Setoran Bulan Ini</span>
                                <strong class="stat-value"><?= format_rupiah($total_setoran_bulan_ini) ?></strong>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-info">
                                <span class="stat-label">Rata-rata Harian</span>
                                <strong class="stat-value"><?= format_rupiah($rata_rata_setoran) ?></strong>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-info">
                                <span class="stat-label">Setoran Hari Ini</span>
                                <strong class="stat-value"><?= count($setoran_hari_ini) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
             
            <section class="tab-panel history-panel <?= $active_tab === 'history' ? 'is-active' : '' ?>" data-tab-panel="history" aria-label="Riwayat shift cashdrawer">
                <div class="search-date">
                    <form method="GET" class="date-search-form">
                        <input type="hidden" name="tab" value="history">
                        <div class="search-input-group">
                            <label for="search_date">Cari Berdasarkan Tanggal:</label>
                            <div class="date-input-wrapper">
                                <input 
                                    type="date" 
                                    id="search_date" 
                                    name="search_date" 
                                    value="<?= isset($_GET['search_date']) ? htmlspecialchars($_GET['search_date']) : date('Y-m-d') ?>"
                                >
                                <button type="submit" class="search-btn">Cari</button>
                                <?php if (isset($_GET['search_date']) && $_GET['search_date'] !== date('Y-m-d')): ?>
                                    <a href="<?= $_SERVER['PHP_SELF'] ?>?tab=history" class="clear-btn">Tampilkan Hari Ini</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <?php
                $searchDate = isset($_GET['search_date']) && !empty($_GET['search_date']) ? $_GET['search_date'] : date('Y-m-d');
                
                $filteredHistory = array_filter($history, function($item) use ($searchDate) {
                    return date('Y-m-d', strtotime($item['waktu_mulai'])) === $searchDate;
                });
                $filteredHistory = array_values($filteredHistory);
                
                $displayHistory = $filteredHistory;
                $isToday = $searchDate === date('Y-m-d');
                $isCustomSearch = isset($_GET['search_date']) && $_GET['search_date'] !== date('Y-m-d');
                ?>

                <?php if (count($displayHistory) === 0): ?>
                    <div class="empty-state">
                        <?php if ($isCustomSearch): ?>
                            <strong>Tidak ada riwayat shift pada tanggal <?= htmlspecialchars($_GET['search_date']) ?>.</strong>
                            <p>Coba tanggal lain atau lihat riwayat hari ini</a>.</p>
                        <?php else: ?>
                            <strong>Belum ada riwayat shift hari ini.</strong>
                            <p>Mulai shift pertama Anda untuk melihat rekam jejak di sini.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="history-summary">
                        <div class="summary-grid">
                            <div class="summary-item">
                                <span class="summary-label">Total Shift</span>
                                <strong><?= count($displayHistory) ?></strong>
                            </div>
                        </div>
                        <?php if ($isCustomSearch): ?>
                            <div class="search-info">
                                Menampilkan shift pada: <?= date('d M Y', strtotime($_GET['search_date'])) ?>
                            </div>
                        <?php else: ?>
                            <div class="search-info">
                                Menampilkan shift hari ini: <?= date('d M Y') ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <ul class="history-list compact-view">
                        <?php foreach ($displayHistory as $item): 
                            $rekap_info = get_rekap_data_for_shift($pdo, $item['id']);
                            $is_synced = $rekap_info !== null;
                            
                            $saldo_awal = $rekap_info['saldo_awal'] ?? $item['saldo_awal'] ?? 0;
                            $saldo_akhir = $rekap_info['saldo_akhir'] ?? $item['saldo_akhir'] ?? $saldo_awal;
                            $selisih = $rekap_info['selisih'] ?? ($saldo_akhir - $saldo_awal);
                            $waktu_mulai = $rekap_info['waktu_mulai'] ?? $item['waktu_mulai'] ?? $item['waktu'];
                            $waktu_selesai = $rekap_info['waktu_selesai'] ?? $item['waktu_selesai'] ?? null;
                        ?>

                            <li class="history-card compact-card" 
                                data-shift-id="<?= $item['id'] ?>" 
                                onclick="showShiftDetail('<?= $item['id'] ?>')">
                                
                                <div class="compact-header">
                                    <div class="shift-basic-info">
                                        <span class="cashdrawer-name"><?= htmlspecialchars($item["cashdrawer"]) ?></span>
                                        <span class="shift-date"><?= date('d M', strtotime($waktu_mulai)) ?></span>
                                    </div>
                                    <div class="shift-status">
                                        <?php if ($is_synced): ?>
                                            <span class="sync-indicator">🔄</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="compact-saldo">
                                    <div class="saldo-item">
                                        <small>Saldo Awal</small>
                                        <span class="saldo-awal"><?= format_rupiah($saldo_awal) ?></span>
                                    </div>
                                    <div class="saldo-item">
                                        <small>Saldo Akhir</small>
                                        <span class="saldo-akhir"><?= format_rupiah($saldo_akhir) ?></span>
                                    </div>
                                    <div class="saldo-item">
                                        <small>Selisih</small>
                                        <span class="<?= $selisih >= 0 ? 'selisih-positif' : 'selisih-negatif' ?>">
                                            <?= $selisih >= 0 ? '+' : '' ?><?= format_rupiah(abs($selisih)) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="compact-time">
                                    <small>
                                        Mulai: <?= format_time($waktu_mulai) ?>
                                        <?php if ($waktu_selesai): ?>
                                            • Selesai: <?= format_time($waktu_selesai) ?>
                                        <?php endif; ?>
                                    </small>
                                </div>

                                <div class="compact-user">
                                    <small><?= htmlspecialchars($_SESSION["namalengkap"]) ?> • <?= htmlspecialchars($_SESSION["nama"]) ?></small>
                                </div>

                                <div class="view-detail-btn">
                                    <span>Lihat Detail →</span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <div id="shiftDetailModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Detail Shift</h3>
                    </div>
                    <div class="modal-body" id="shiftDetailContent"></div>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; 2025 Universitas Anwar Medika</p>
        </footer>
    </div>
    <script>
        window.shiftHistoryData = <?= json_encode($displayHistory, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
        window.rekapData = <?= 
            json_encode(
                array_combine(
                    array_column($displayHistory, 'id'),
                    array_map(function($item) use ($pdo) {
                        return get_rekap_data_for_shift($pdo, $item['id']);
                    }, $displayHistory)
                ),
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
            ) 
        ?>;
    </script>
    <script src="../JS/shift.js"></script>
    <script src="../JS/shift_history.js"></script>
</body>
</html>