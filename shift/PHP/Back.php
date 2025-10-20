<?php
date_default_timezone_set('Asia/Jakarta');

$data_file = 'shift_data.json';
$rekap_file = 'Rekap_Shift/rekap_data.json';
$setoran_file = 'setoran_data.json';

function read_shift_data($file) {
    if (file_exists($file)) {
        $json_data = file_get_contents($file);
        return json_decode($json_data, true) ?: [];
    }
    return [];
}

function save_shift_data($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

function sync_to_rekap($shift_data, $rekap_file) {
    $rekap_data = read_shift_data($rekap_file);
    $existing_index = -1;
    foreach ($rekap_data as $index => $rekap) {
        if (isset($rekap['shift_id']) && $rekap['shift_id'] === $shift_data['id']) {
            $existing_index = $index;
            break;
        }
    }
    
    $rekap_entry = [
        'shift_id' => $shift_data['id'],
        'cashdrawer' => $shift_data['cashdrawer'],
        'saldo_awal' => $shift_data['saldo_awal'],
        'saldo_akhir' => $shift_data['saldo_akhir'],
        'total_penjualan' => 0,
        'total_pengeluaran' => 0,
        'total_pemasukan_lain' => 0,
        'total_pengeluaran_lain' => 0,
        'selisih' => $shift_data['saldo_akhir'] - $shift_data['saldo_awal'],
        'waktu_mulai' => $shift_data['waktu_mulai'],
        'waktu_selesai' => $shift_data['waktu_selesai'] ?? null,
        'kasir' => $shift_data['nama'],
        'role' => $shift_data['role'],
        'last_updated' => date('Y-m-d H:i:s')
    ];
    
    if ($existing_index >= 0) {
        $rekap_data[$existing_index] = array_merge($rekap_data[$existing_index], [
            'cashdrawer' => $shift_data['cashdrawer'],
            'saldo_awal' => $shift_data['saldo_awal'],
            'saldo_akhir' => $shift_data['saldo_akhir'],
            'selisih' => $shift_data['saldo_akhir'] - $shift_data['saldo_awal'],
            'waktu_mulai' => $shift_data['waktu_mulai'],
            'waktu_selesai' => $shift_data['waktu_selesai'] ?? null,
            'last_updated' => date('Y-m-d H:i:s')
        ]);
    } else {
        array_unshift($rekap_data, $rekap_entry);
    }
    
    save_shift_data($rekap_file, $rekap_data);
    return $rekap_entry;
}

function sync_from_rekap($shift_id, $rekap_file, $data_file) {
    $rekap_data = read_shift_data($rekap_file);
    $shift_data = read_shift_data($data_file);
    
    foreach ($rekap_data as $rekap) {
        if (isset($rekap['shift_id']) && $rekap['shift_id'] === $shift_id) {
            foreach ($shift_data as &$shift) {
                if ($shift['id'] === $shift_id) {
                    $shift['saldo_akhir'] = $rekap['saldo_akhir'];
                    $shift['saldo_awal'] = $rekap['saldo_awal'];
                    $shift['cashdrawer'] = $rekap['cashdrawer'];
                    $shift['waktu_mulai'] = $rekap['waktu_mulai'];
                    $shift['waktu_selesai'] = $rekap['waktu_selesai'] ?? null;
                    break;
                }
            }
            
            save_shift_data($data_file, $shift_data);
            return $rekap;
        }
    }
    return null;
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

function calculate_total_setoran_bulan_ini($setoran_data) {
    $total = 0;
    $current_month = date('Y-m');
    foreach ($setoran_data as $setoran) {
        if (date('Y-m', strtotime($setoran['waktu'])) === $current_month) {
            $total += $setoran['jumlah'];
        }
    }
    return $total;
}

function calculate_rata_rata_setoran($setoran_data) {
    if (empty($setoran_data)) return 0;
    
    $total = 0;
    $days = [];
    foreach ($setoran_data as $setoran) {
        $day = date('Y-m-d', strtotime($setoran['waktu']));
        if (!in_array($day, $days)) {
            $days[] = $day;
        }
        $total += $setoran['jumlah'];
    }
    
    $unique_days = count($days);
    return $unique_days > 0 ? $total / $unique_days : 0;
}

function get_saldo_warisan($history_data) {
    if (empty($history_data)) return 0;
    
    $shift_selesai = array_filter($history_data, function($shift) {
        return isset($shift['waktu_selesai']) && !empty($shift['waktu_selesai']);
    });
    
    if (empty($shift_selesai)) return 0;
    
    usort($shift_selesai, function($a, $b) {
        return strtotime($b['waktu_selesai']) - strtotime($a['waktu_selesai']);
    });
    
    $shift_terakhir = $shift_selesai[0];
    
    return $shift_terakhir['saldo_akhir'] ?? 0;
}

function calculate_total_setoran_hari_ini($setoran_data) {
    $total = 0;
    $today = date('Y-m-d');
    
    foreach ($setoran_data as $setoran) {
        if (date('Y-m-d', strtotime($setoran['waktu'])) === $today) {
            $total += $setoran['jumlah'];
        }
    }
    
    return $total;
}

function calculate_saldo_tersedia($saldo_akhir, $total_setoran_hari_ini) {
    $saldo_tersedia = $saldo_akhir - $total_setoran_hari_ini;
    return max(0, $saldo_tersedia);
}

function get_rekap_data_for_shift($shift_id, $rekap_file) {
    $rekap_data = read_shift_data($rekap_file);
    foreach ($rekap_data as $rekap) {
        if (isset($rekap['shift_id']) && $rekap['shift_id'] === $shift_id) {
            return $rekap;
        }
    }
    return null;
}

function get_saldo_akhir_dari_riwayat($history_data, $rekap_file, $data_file) {
    if (empty($history_data)) return 0;
    
    $shift_terakhir = $history_data[0];
    
    if (isset($shift_terakhir['id'])) {
        $rekap_data = sync_from_rekap($shift_terakhir['id'], $rekap_file, $data_file);
        if ($rekap_data) {
            return $rekap_data['saldo_akhir'];
        }
    }
    
    return $shift_terakhir['saldo_akhir'] ?? 0;
}

function update_current_shift_from_rekap($current_shift, $rekap_file, $data_file) {
    if (!$current_shift || !isset($current_shift['id'])) {
        return $current_shift;
    }
    
    $rekap_data = get_rekap_data_for_shift($current_shift['id'], $rekap_file);
    if ($rekap_data) {
        $current_shift['saldo_akhir'] = $rekap_data['saldo_akhir'];
        $current_shift['saldo_awal'] = $rekap_data['saldo_awal'];
        $current_shift['cashdrawer'] = $rekap_data['cashdrawer'];
        $current_shift['waktu_mulai'] = $rekap_data['waktu_mulai'];
        $current_shift['waktu_selesai'] = $rekap_data['waktu_selesai'] ?? null;
        
        $_SESSION["shift_current"] = $current_shift;
        
        if (isset($_SESSION["shift_history"][0]) && $_SESSION["shift_history"][0]['id'] === $current_shift['id']) {
            $_SESSION["shift_history"][0] = $current_shift;
            save_shift_data($data_file, $_SESSION["shift_history"]);
        }
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

session_start();

$saved_data = read_shift_data($data_file);
$saved_setoran_data = read_shift_data($setoran_file);

if (!isset($_SESSION["shift_history"]) && !empty($saved_data)) {
    $_SESSION["shift_history"] = $saved_data;
} elseif (!isset($_SESSION["shift_history"])) {
    $_SESSION["shift_history"] = [];
}

if (!isset($_SESSION["setoran_data"]) && !empty($saved_setoran_data)) {
    $_SESSION["setoran_data"] = $saved_setoran_data;
} elseif (!isset($_SESSION["setoran_data"])) {
    $_SESSION["setoran_data"] = [];
}

$saldo_warisan = get_saldo_warisan($_SESSION["shift_history"]);
$today = date('Y-m-d');
$total_setoran_hari_ini = calculate_total_setoran_hari_ini($_SESSION["setoran_data"]);
$setoran_hari_ini = [];

foreach ($_SESSION["setoran_data"] as $setoran) {
    if (date('Y-m-d', strtotime($setoran['waktu'])) === $today) {
        $setoran_hari_ini[] = $setoran;
    }
}

$currentShift = $_SESSION["shift_current"] ?? null;

if ($currentShift) {
    $currentShift = update_current_shift_from_rekap($currentShift, $rekap_file, $data_file);
    $_SESSION["shift_current"] = $currentShift;
}

$saldo_akhir_riwayat = get_saldo_akhir_dari_riwayat($_SESSION["shift_history"], $rekap_file, $data_file);

if ($currentShift) {
    $saldo_awal_hari_ini = $currentShift['saldo_awal'];
    $saldo_akhir_hari_ini = $currentShift['saldo_akhir'];
    $saldo_tersedia = calculate_saldo_tersedia($currentShift['saldo_akhir'], $total_setoran_hari_ini);
    $can_setor = $saldo_tersedia > 0;
    $saldo_akhir_display = $currentShift['saldo_akhir'];
} else {
    $saldo_awal_hari_ini = 0;
    $saldo_akhir_hari_ini = $saldo_akhir_riwayat;
    $saldo_tersedia = calculate_saldo_tersedia($saldo_akhir_riwayat, $total_setoran_hari_ini);
    $can_setor = $saldo_tersedia > 0;
    $saldo_akhir_display = $saldo_akhir_riwayat;
}

$cashdrawers = [
    "Kasir 01 - Cashdrawer 1",
    "Kasir 02 - Cashdrawer 2", 
    "Kasir 03 - Cashdrawer 3",
];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['setoran_action'])) {
    if ($_POST['setoran_action'] === 'add') {
        $penyetor = trim($_POST['penyetor'] ?? '');
        $jumlah_input = $_POST['jumlah_setoran'] ?? '';
        $jenis_setoran = $_POST['jenis_setoran'] ?? '';
        $metode_setoran = $_POST['metode_setoran'] ?? '';
        $keterangan = trim($_POST['keterangan_setoran'] ?? '');
        $detail_lainnya = trim($_POST['detail_lainnya'] ?? '');

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
                    $setoran_data = [
                        'id' => uniqid('setoran_', true),
                        'penyetor' => $penyetor,
                        'jumlah' => $jumlah_value,
                        'jenis' => $jenis_setoran,
                        'jenis_display' => get_jenis_setoran_display($jenis_setoran),
                        'metode' => $metode_setoran,
                        'metode_display' => get_metode_setoran_display($metode_setoran),
                        'keterangan' => $keterangan,
                        'detail_lainnya' => $detail_lainnya,
                        'waktu' => date('Y-m-d H:i:s'),
                        'shift_id' => $_SESSION['shift_current']['id'] ?? null
                    ];

                    array_unshift($_SESSION['setoran_data'], $setoran_data);
                    save_shift_data($setoran_file, $_SESSION['setoran_data']);
                    
                    if ($currentShift) {
                        $_SESSION["shift_current"]['saldo_akhir'] = $currentShift['saldo_akhir'] - $jumlah_value;
                        $_SESSION["shift_history"][0]['saldo_akhir'] = $_SESSION["shift_current"]['saldo_akhir'];
                        save_shift_data($data_file, $_SESSION["shift_history"]);
                        
                        sync_to_rekap($_SESSION["shift_current"], $rekap_file);
                    }
                    
                    $_SESSION["success"] = "Setoran berhasil disimpan.";
                }
            }
        }
        
        header("Location: " . $_SERVER["PHP_SELF"] . "?tab=setoran");
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'rekap_shift') {
    if (isset($_SESSION["shift_current"])) {
        $_SESSION['shift'] = $_SESSION["shift_current"];
        
        if (!isset($_SESSION['transaksi'])) {
            $_SESSION['transaksi'] = [];
        }
        
        $rekap_data = read_shift_data($rekap_file);
        $shift_found = false;
        foreach ($rekap_data as $rekap) {
            if (isset($rekap['shift_id']) && $rekap['shift_id'] === $_SESSION["shift_current"]['id']) {
                $shift_found = true;
                
                if ($rekap['cashdrawer'] !== $_SESSION["shift_current"]['cashdrawer'] || 
                    $rekap['saldo_awal'] !== $_SESSION["shift_current"]['saldo_awal'] ||
                    $rekap['saldo_akhir'] !== $_SESSION["shift_current"]['saldo_akhir']) {
                    sync_to_rekap($_SESSION["shift_current"], $rekap_file);
                }
                break;
            }
        }
        
        if (!$shift_found) {
            sync_to_rekap($_SESSION["shift_current"], $rekap_file);
        }
        
        header("Location: Rekap_Shift/rekap_shift.php");
        exit;
    } else {
        $_SESSION["error"] = "Silakan mulai shift terlebih dahulu sebelum melihat rekap shift.";
        header("Location: " . $_SERVER["PHP_SELF"] . "?tab=current");
        exit;
    }
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
            $saldo_akhir = $saldo_warisan + $saldo_value;
            
            $shift = [
                "id"         => uniqid("shift_", true),
                "nama"       => "User Kasir", 
                "role"       => "Kasir",
                "cashdrawer" => $cashdrawer,
                "saldo_awal" => $saldo_value, 
                "saldo_akhir" => $saldo_akhir,
                "waktu_mulai" => date("Y-m-d H:i:s"),
                "waktu_selesai" => null
            ];

            $_SESSION["shift_current"] = $shift;
            $_SESSION["transaksi"] = [];
            array_unshift($_SESSION["shift_history"], $shift);
            
            save_shift_data($data_file, $_SESSION["shift_history"]);
            sync_to_rekap($shift, $rekap_file);

            unset($_SESSION["pending_shift"]);
            unset($_SESSION["show_confirmation"]);

            $_SESSION["success"] = "Shift berhasil dimulai! Saldo awal: " . format_rupiah($saldo_value) . 
                                 ($saldo_warisan > 0 ? " + Warisan: " . format_rupiah($saldo_warisan) : "");
        }
    }

    header("Location: " . $_SERVER["PHP_SELF"] . "?tab=current");
    exit;
}

$active_tab = isset($_GET['tab']) && in_array($_GET['tab'], ['current', 'setoran', 'history']) ? $_GET['tab'] : 'current';
$history = $_SESSION["shift_history"] ?? [];
$setoran_data = $_SESSION["setoran_data"] ?? [];
$total_setoran_bulan_ini = calculate_total_setoran_bulan_ini($setoran_data);
$rata_rata_setoran = calculate_rata_rata_setoran($setoran_data);
$error = $_SESSION["error"] ?? null;
$success = $_SESSION["success"] ?? null;

unset($_SESSION["error"], $_SESSION["success"]);
?>