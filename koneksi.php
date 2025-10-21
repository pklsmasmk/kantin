<?php
$host = "localhost";
$user = "smk";
$pass = "smk123";
$db   = "db_kantin";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
