<?php
function getDBConnection() {
    $host = "192.168.109.195";
    $username = "smk";
    $password = "smk123";
    $dbname = "db_kantin";
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?>