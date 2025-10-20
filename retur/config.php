<?php
// config.php
$host = '192.168.109.195';
$username = 'smk';
$password = 'smk123';
$database = 'db_kantin';

function getDBConnection() {
    global $host, $username, $password, $database;
    
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Function untuk escape string
function escapeString($string) {
    $conn = getDBConnection();
    return $conn->real_escape_string($string);
}
?>