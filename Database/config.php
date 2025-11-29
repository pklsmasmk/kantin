<?php
$pdo = null;
if (!defined('__DBCONFIG__')) {
    define('__DBCONFIG__', true);
    $host = '192.168.109.132';
    $username = 'smk';
    $password = 'smk123';
    $database = 'db_kantin';


function getDBConnection() {
    global $host, $username, $password, $database;
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Koneksi database gagal: " . $e->getMessage());
    }
}
}

