<?php

function isUserLoggedIn() {
    return isset($_SESSION['namalengkap']) && isset($_SESSION['nama']);
}

function redirectToLogin() {
    header("Location: ../login/Login.php");
    exit;
}

function logout() {
    session_destroy();
    header("Location: ../login/Login.php");
    exit;
}

function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isUserLoggedIn()) {
        redirectToLogin();
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
}
?>