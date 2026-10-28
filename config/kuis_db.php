<?php
// ============================================================================
// config/kuis_db.php - Koneksi Basis Data PDO
// Etalase Digital Stella - SMK Telkom Lampung
// ============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbHost = 'localhost';
$dbName = 'db_etalase_stella';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Jika basis data belum dibuat, buat secara otomatis
    try {
        $pdoTemp = new PDO("mysql:host={$dbHost};charset=utf8mb4", $dbUser, $dbPass);
        $pdoTemp->exec("CREATE DATABASE IF NOT EXISTS db_etalase_stella CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        // Inisialisasi tabel jika baru dibuat
        $schemaPath = dirname(__DIR__) . '/database/database.sql';
        if (is_file($schemaPath)) {
            $sql = file_get_contents($schemaPath);
            $pdo->exec($sql);
        }
    } catch (PDOException $ex) {
        die("<div style='font-family:sans-serif;padding:30px;background:#fef2f2;color:#991b1b;border:1px solid #f87171;border-radius:8px;max-width:600px;margin:40px auto;'>"
            . "<h3 style='margin-top:0;'>Koneksi Basis Data Gagal</h3>"
            . "<p>Pastikan layanan MySQL di XAMPP / MariaDB sudah berjalan.</p>"
            . "<p><small>Pesan Error: " . htmlspecialchars($ex->getMessage()) . "</small></p>"
            . "</div>");
    }
}

// Helper Sanitasi Output XSS
if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Helper Flash Message Sesi
if (!function_exists('flash_set')) {
    function flash_set($type, $message) {
        $_SESSION['flash_msg'] = [
            'type'    => $type,    // 'success', 'danger', 'warning', 'info'
            'message' => $message
        ];
    }
}

if (!function_exists('flash_get')) {
    function flash_get() {
        if (isset($_SESSION['flash_msg'])) {
            $flash = $_SESSION['flash_msg'];
            unset($_SESSION['flash_msg']);
            return $flash;
        }
        return null;
    }
}
