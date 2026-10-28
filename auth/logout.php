<?php
// ============================================================================
// auth/logout.php - Proses Logout Sesi
// Etalase Digital Stella - SMK Telkom Lampung
// ============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Mulai sesi baru untuk menyimpan notifikasi flash sukses keluar
session_start();
require_once __DIR__ . '/../config/kuis_db.php';
flash_set('info', 'Anda telah berhasil keluar dari sistem (Logout).');

header('Location: login.php');
exit;
