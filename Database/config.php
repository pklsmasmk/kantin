<?php
if (!defined('__DBCONFIG__')) {
    define('__DBCONFIG__', true);
    $host = '192.168.109.132';
    $username = 'smk';
    $password = 'smk123';
    $database = 'db_kantin';
    $pdo = null;
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    } catch (PDOException $e) {
        error_log("Koneksi database gagal: " . $e->getMessage());
    }
}

if (!function_exists('getDBConnection')) {
    function getDBConnection() {
        global $pdo;
        return $pdo;
    }
}
?>
