<?php
if (defined('__DBCONFIG__')) {
    define('__DBCONFIG__', true);
    $host = '192.168.109.195';
    $username = 'smk';
    $password = 'smk123';
    $database = 'db_kantin';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // die("Koneksi database gagal: " . $e->getMessage());
    }
}
