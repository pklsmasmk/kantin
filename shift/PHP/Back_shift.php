<?php
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

function refresh_shift_history($pdo) {
    if (isset($_SESSION["shift_history"])) {
        unset($_SESSION["shift_history"]);
    }
    return read_shift_data($pdo);
}

function save_shift_data($pdo, $data) {
    try {
        $saldo_awal = floatval($data['saldo_awal']);
        $saldo_akhir = floatval($data['saldo_akhir']);
        
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
            $saldo_awal,
            $saldo_akhir,
            $data['waktu_mulai'],
            $data['waktu_selesai']
        ]);
        
        refresh_shift_history($pdo);
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
        
        $saldo_awal = floatval($shift_data['saldo_awal']);
        $saldo_akhir = floatval($shift_data['saldo_akhir']);
        $selisih = $saldo_akhir - $saldo_awal;
        
        $cashdrawer_display = "Riwayat Shift";
        
        if ($existing) {
            $stmt = $pdo->prepare("
                UPDATE rekap_shift SET 
                    cashdrawer = ?, saldo_awal = ?, saldo_akhir = ?, selisih = ?,
                    waktu_mulai = ?, waktu_selesai = ?, last_updated = ?
                WHERE shift_id = ?
            ");
            $stmt->execute([
                $cashdrawer_display,
                $saldo_awal,
                $saldo_akhir,
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
                $cashdrawer_display,
                $saldo_awal,
                $saldo_akhir,
                0, 0, 0, 0, 
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
            'cashdrawer' => $cashdrawer_display,
            'saldo_awal' => $saldo_awal,
            'saldo_akhir' => $saldo_akhir,
            'selisih' => $selisih,
            'waktu_mulai' => $shift_data['waktu_mulai'],
            'waktu_selesai' => $shift_data['waktu_selesai'] ?? null
        ];
    } catch (PDOException $e) {
        error_log("Error sync_to_rekap: " . $e->getMessage());
        return null;
    }
}

function akhiri_shift($pdo, $shift_id, $saldo_akhir) {
    try {
        $saldo_akhir = floatval($saldo_akhir);
        
        $stmt = $pdo->prepare("
            UPDATE shifts SET 
                waktu_selesai = NOW(),
                saldo_akhir = ?
            WHERE id = ?
        ");
        $stmt->execute([$saldo_akhir, $shift_id]);
        
        refresh_shift_history($pdo);
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
            $saldo_awal = floatval($rekap['saldo_awal']);
            $saldo_akhir = floatval($rekap['saldo_akhir']);
            
            $cashdrawer_display = "Riwayat Shift";
            
            $stmt = $pdo->prepare("
                UPDATE shifts SET 
                    saldo_akhir = ?, saldo_awal = ?, cashdrawer = ?,
                    waktu_mulai = ?, waktu_selesai = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $saldo_akhir,
                $saldo_awal,
                $cashdrawer_display,
                $rekap['waktu_mulai'],
                $rekap['waktu_selesai'],
                $shift_id
            ]);
            
            refresh_shift_history($pdo);
            return $rekap;
        }
        return null;
    } catch (PDOException $e) {
        error_log("Error sync_from_rekap: " . $e->getMessage());
        return null;
    }
}

function get_rekap_data_for_shift($pdo, $shift_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM rekap_shift WHERE shift_id = ?");
        $stmt->execute([$shift_id]);
        $rekap = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($rekap) {
            $rekap['saldo_awal'] = floatval($rekap['saldo_awal']);
            $rekap['saldo_akhir'] = floatval($rekap['saldo_akhir']);
            $rekap['selisih'] = floatval($rekap['selisih']);
            $rekap['total_penjualan'] = floatval($rekap['total_penjualan']);
            $rekap['total_pengeluaran'] = floatval($rekap['total_pengeluaran']);
            $rekap['total_pemasukan_lain'] = floatval($rekap['total_pemasukan_lain']);
            $rekap['total_pengeluaran_lain'] = floatval($rekap['total_pengeluaran_lain']);
        }
        
        return $rekap;
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
            WHERE waktu_selesai IS NOT NULL
            ORDER BY waktu_mulai DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return floatval($result['saldo_akhir'] ?? 0);
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
        $current_shift['saldo_akhir'] = floatval($rekap_data['saldo_akhir']);
        $current_shift['saldo_awal'] = floatval($rekap_data['saldo_awal']);
        $current_shift['cashdrawer'] = "Riwayat Shift";
        $current_shift['waktu_mulai'] = $rekap_data['waktu_mulai'];
        $current_shift['waktu_selesai'] = $rekap_data['waktu_selesai'] ?? null;
        
        $_SESSION["shift_current"] = $current_shift;
        
        save_shift_data($pdo, $current_shift);
    }
    
    return $current_shift;
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
        return floatval($result['total'] ?? 0);
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
        
        $total = floatval($result['total'] ?? 0);
        $hari = intval($result['hari'] ?? 0);
        
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
        return floatval($result['total'] ?? 0);
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
        $setoran = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($setoran as &$item) {
            $item['jumlah'] = floatval($item['jumlah']);
        }
        
        return $setoran;
    } catch (PDOException $e) {
        error_log("Error get_setoran_hari_ini: " . $e->getMessage());
        return [];
    }
}

function calculate_saldo_tersedia($pdo, $currentShift = null) {
    if ($currentShift) {
        return max(0, floatval($currentShift['saldo_akhir']));
    } else {
        $saldo_akhir_riwayat = get_saldo_akhir_dari_riwayat($pdo);
        return max(0, $saldo_akhir_riwayat);
    }
}

function update_saldo_setelah_setoran($pdo, $jumlah_setoran, $currentShift = null) {
    $jumlah_setoran = floatval($jumlah_setoran);
    
    if ($currentShift) {
        $new_saldo_akhir = floatval($currentShift['saldo_akhir']) - $jumlah_setoran;
        
        $stmt = $pdo->prepare("UPDATE shifts SET saldo_akhir = ? WHERE id = ?");
        $stmt->execute([$new_saldo_akhir, $currentShift['id']]);
        
        $_SESSION["shift_current"]['saldo_akhir'] = $new_saldo_akhir;
        
        sync_to_rekap($pdo, $_SESSION["shift_current"]);
        refresh_shift_history($pdo);
        
        return $new_saldo_akhir;
    } else {
        $saldo_akhir_riwayat = get_saldo_akhir_dari_riwayat($pdo);
        $new_saldo_akhir = $saldo_akhir_riwayat - $jumlah_setoran;
        
        $shift = [
            "id"         => uniqid("shift_auto_", true),
            "nama"       => $_SESSION['namalengkap'] ?? "System Auto",
            "role"       => $_SESSION['nama'] ?? "System",
            "cashdrawer" => "Riwayat Shift",
            "saldo_awal" => $saldo_akhir_riwayat,
            "saldo_akhir" => $new_saldo_akhir,
            "waktu_mulai" => date("Y-m-d H:i:s"),
            "waktu_selesai" => date("Y-m-d H:i:s") 
        ];
        
        save_shift_data($pdo, $shift);
        sync_to_rekap($pdo, $shift);
        
        return $new_saldo_akhir;
    }
}

function validate_setoran($jumlah_setoran, $saldo_tersedia) {
    $jumlah_setoran = floatval($jumlah_setoran);
    $saldo_tersedia = floatval($saldo_tersedia);
    
    if ($jumlah_setoran <= 0) {
        return "Jumlah setoran harus lebih besar dari 0.";
    }
    
    if ($jumlah_setoran > $saldo_tersedia) {
        return "Saldo tidak mencukupi. Saldo tersedia: " . format_rupiah($saldo_tersedia);
    }
    
    $saldo_setelah_setor = $saldo_tersedia - $jumlah_setoran;
    if ($saldo_setelah_setor < 100000) {
        return "Setoran tidak boleh menghabiskan semua saldo. Minimal harus menyisakan Rp 100.000. Saldo tersisa setelah setor: " . format_rupiah($saldo_setelah_setor);
    }
    
    return null;
}

function is_shift_pertama($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM shifts");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (intval($result['total'] ?? 0)) == 0;
    } catch (PDOException $e) {
        error_log("Error is_shift_pertama: " . $e->getMessage());
        return true; 
    }
}

function get_saldo_awal_untuk_shift_baru($pdo) {
    $saldo_akhir_riwayat = get_saldo_akhir_dari_riwayat($pdo);
    if ($saldo_akhir_riwayat > 0) {
        return $saldo_akhir_riwayat;
    }
    return 0;
}

function format_rupiah($value) {
    $numeric_value = floatval($value);
    return "Rp " . number_format($numeric_value, 0, ",", ".");
}

function format_datetime($datetime) {
    $date = date_create_from_format("Y-m-d H:i:s", $datetime);
    return $date ? $date->format("d M Y • H:i") : $datetime;
}

function format_time($datetime) {
    $date = date_create_from_format("Y-m-d H:i:s", $datetime);
    return $date ? $date->format("H:i") : $datetime;
}

function clean_shift_history_data($history) {
    foreach ($history as &$item) {
        $item['saldo_awal'] = floatval($item['saldo_awal']);
        $item['saldo_akhir'] = floatval($item['saldo_akhir']);
        if ($item['cashdrawer'] === "Cashdrawer-Otomatis" || $item['cashdrawer'] === "Auto-Cashdrawer") {
            $item['cashdrawer'] = "Riwayat Shift";
        }
    }
    return $history;
}

function process_shift_requests($pdo) { 
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

    if (isset($_GET['action']) && $_GET['action'] === 'akhiri_shift' && isset($_SESSION["shift_current"])) {
        $currentShift = $_SESSION["shift_current"];
        
        $stmt = $pdo->prepare("UPDATE shifts SET waktu_selesai = NOW(), saldo_akhir = ? WHERE id = ?");
        $stmt->execute([floatval($currentShift['saldo_akhir']), $currentShift['id']]);
        
        sync_to_rekap($pdo, $currentShift);
        
        $_SESSION["shift_current"]['waktu_selesai'] = date("Y-m-d H:i:s");
        $_SESSION["success"] = "Shift berhasil diakhiri. Saldo akhir: " . format_rupiah($currentShift['saldo_akhir']);
        
        refresh_shift_history($pdo);
        unset($_SESSION["shift_current"]);
        
        header("Location: " . $_SERVER["PHP_SELF"] . "?tab=current");
        exit;
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['setoran_action'])) {
        $cashdrawer = isset($_POST["cashdrawer"]) ? trim($_POST["cashdrawer"]) : "";
        $saldo_awal = isset($_POST["saldo_awal"]) ? trim($_POST["saldo_awal"]) : "";
        $confirmed = isset($_POST["confirmed"]) ? $_POST["confirmed"] === "true" : false;

        $is_shift_pertama = is_shift_pertama($pdo);
        
        if ($is_shift_pertama && $cashdrawer === "") {
            $_SESSION["error"] = "Cashdrawer harus dipilih untuk shift pertama.";
        } else {
            $saldo_clean = preg_replace("/[^\d]/", "", $saldo_awal);
            $saldo_value = $saldo_clean !== "" ? floatval($saldo_clean) : NAN;

            if (!is_numeric($saldo_value) || $saldo_value <= 0) {
                $_SESSION["error"] = "Saldo awal harus berupa angka yang lebih besar dari 0.";
            } else if (!$confirmed) {
                $_SESSION["pending_shift"] = [
                    "cashdrawer" => $cashdrawer,
                    "saldo_awal" => $saldo_value,
                    "is_shift_pertama" => $is_shift_pertama
                ];
                $_SESSION["show_confirmation"] = true;
            } else {
                $cashdrawer_final = $is_shift_pertama ? $cashdrawer : "Riwayat Shift";
                
                $shift = [
                    "id"         => uniqid("shift_", true),
                    "nama"       => $_SESSION['namalengkap'] ?? "namalengkap", 
                    "role"       => $_SESSION['nama'] ?? "nama",
                    "cashdrawer" => $cashdrawer_final,
                    "saldo_awal" => $saldo_value, 
                    "saldo_akhir" => $saldo_value,
                    "waktu_mulai" => date("Y-m-d H:i:s"),
                    "waktu_selesai" => null
                ];

                $_SESSION["shift_current"] = $shift;
                $_SESSION["transaksi"] = [];
                
                save_shift_data($pdo, $shift);
                sync_to_rekap($pdo, $shift);

                unset($_SESSION["pending_shift"]);
                unset($_SESSION["show_confirmation"]);

                $_SESSION["success"] = "Shift berhasil dimulai! Saldo awal: " . format_rupiah($saldo_value);
            }
        }

        echo "<script>window.location.href='" . $_SERVER["PHP_SELF"] . "?tab=current';</script>";
        exit;
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['setoran_action'])) {
        process_setoran_request($pdo);
    }
}

function process_setoran_request($pdo) {
    if ($_POST['setoran_action'] === 'add') {
        $penyetor = trim($_POST['penyetor'] ?? $_SESSION['namalengkap'] ?? '');
        $jumlah_input = $_POST['jumlah_setoran'] ?? '';
        $jenis_setoran = $_POST['jenis_setoran'] ?? '';
        $metode_setoran = $_POST['metode_setoran'] ?? '';
        $keterangan = trim($_POST['keterangan_setoran'] ?? '');
        $detail_lainnya = trim($_POST['detail_lainnya'] ?? '');
        
        $currentShift = $_SESSION["shift_current"] ?? null;
        $saldo_tersedia = calculate_saldo_tersedia($pdo, $currentShift);
        
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
            $jumlah_value = $jumlah_clean !== "" ? floatval($jumlah_clean) : 0;

            $validation_error = validate_setoran($jumlah_value, $saldo_tersedia);
            
            if ($validation_error) {
                $_SESSION["error"] = $validation_error;
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
                    $currentShift['id'] ?? null
                ]);

                $new_saldo_akhir = update_saldo_setelah_setoran($pdo, $jumlah_value, $currentShift);
                
                $_SESSION["success"] = "Setoran berhasil disimpan. Saldo tersisa: " . format_rupiah($new_saldo_akhir);
            }
        }
        
        echo "<script>window.location.href='" . $_SERVER["PHP_SELF"] . "?tab=setoran';</script>";
        exit;
    }
}

function get_shift_display_data($pdo) {
    $history = read_shift_data($pdo);
    
    $history = clean_shift_history_data($history);
    
    $today = date('Y-m-d');
    $total_setoran_hari_ini = calculate_total_setoran_hari_ini($pdo);
    $setoran_hari_ini = get_setoran_hari_ini($pdo);

    $currentShift = $_SESSION["shift_current"] ?? null;

    if ($currentShift) {
        $currentShift = update_current_shift_from_rekap($pdo, $currentShift);
        $_SESSION["shift_current"] = $currentShift;
    }

    $saldo_akhir_riwayat = get_saldo_akhir_dari_riwayat($pdo);

    $is_shift_pertama = is_shift_pertama($pdo);
    $saldo_awal_rekomendasi = get_saldo_awal_untuk_shift_baru($pdo);
    
    if ($currentShift) {
        $saldo_awal_hari_ini = floatval($currentShift['saldo_awal']);
        $saldo_akhir_hari_ini = floatval($currentShift['saldo_akhir']);
        $saldo_tersedia = calculate_saldo_tersedia($pdo, $currentShift);
        $can_setor = $saldo_tersedia > 100000; 
        $saldo_akhir_display = floatval($currentShift['saldo_akhir']);
    } else {
        $saldo_awal_hari_ini = $saldo_awal_rekomendasi;
        $saldo_akhir_hari_ini = $saldo_akhir_riwayat;
        $saldo_tersedia = calculate_saldo_tersedia($pdo, null);
        $can_setor = $saldo_tersedia > 100000; 
        $saldo_akhir_display = $saldo_akhir_riwayat;
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

    return [
        'total_setoran_hari_ini' => $total_setoran_hari_ini,
        'setoran_hari_ini' => $setoran_hari_ini,
        'currentShift' => $currentShift,
        'saldo_akhir_riwayat' => $saldo_akhir_riwayat,
        'saldo_awal_hari_ini' => $saldo_awal_hari_ini,
        'saldo_akhir_hari_ini' => $saldo_akhir_hari_ini,
        'saldo_tersedia' => $saldo_tersedia,
        'can_setor' => $can_setor,
        'saldo_akhir_display' => $saldo_akhir_display,
        'cashdrawers' => $cashdrawers,
        'history' => $history, 
        'total_setoran_bulan_ini' => calculate_total_setoran_bulan_ini($pdo),
        'rata_rata_setoran' => calculate_rata_rata_setoran($pdo),
        'is_shift_pertama' => $is_shift_pertama,
        'saldo_awal_rekomendasi' => $saldo_awal_rekomendasi
    ];
}

if (isset($_GET['action']) && $_GET['action'] === 'get_shift_detail' && isset($_GET['shift_id'])) {
    $shift_id = $_GET['shift_id'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM shifts WHERE id = ?");
        $stmt->execute([$shift_id]);
        $shift = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$shift) {
            echo json_encode(['success' => false, 'message' => 'Shift tidak ditemukan']);
            exit;
        }
        
        if ($shift['cashdrawer'] === "Cashdrawer-Otomatis" || $shift['cashdrawer'] === "Auto-Cashdrawer") {
            $shift['cashdrawer'] = "Riwayat Shift";
        }
        
        $stmt = $pdo->prepare("SELECT * FROM rekap_shift WHERE shift_id = ?");
        $stmt->execute([$shift_id]);
        $rekap = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($shift) {
            $shift['saldo_awal'] = floatval($shift['saldo_awal']);
            $shift['saldo_akhir'] = floatval($shift['saldo_akhir']);
        }
        if ($rekap) {
            $rekap['saldo_awal'] = floatval($rekap['saldo_awal']);
            $rekap['saldo_akhir'] = floatval($rekap['saldo_akhir']);
            $rekap['selisih'] = floatval($rekap['selisih']);
        }
        
        echo json_encode([
            'success' => true,
            'shift' => $shift,
            'rekap' => $rekap ?: null
        ]);
        
    } catch (PDOException $e) {
        error_log("Error get_shift_detail: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'sync_shift' && isset($_GET['shift_id'])) {
    $shift_id = $_GET['shift_id'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM shifts WHERE id = ?");
        $stmt->execute([$shift_id]);
        $shift = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$shift) {
            echo json_encode(['success' => false, 'message' => 'Shift tidak ditemukan']);
            exit;
        }
        
        $sync_result = sync_to_rekap($pdo, $shift);
        
        if ($sync_result) {
            echo json_encode(['success' => true, 'message' => 'Shift berhasil disinkronisasi']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyinkronisasi shift']);
        }
        
    } catch (PDOException $e) {
        error_log("Error sync_shift: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}
?>