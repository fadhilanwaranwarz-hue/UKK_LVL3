<?php
// ============================================================================
// forgot_password.php - Halaman Bantuan Lupa Password
// Etalase Digital Stella - SMK Telkom Lampung
// ============================================================================

require_once __DIR__ . '/config/kuis_db.php';
require_once __DIR__ . '/middleware/auth_check.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Lupa Password - Etalase Digital';
$activePage = 'login';
$pageCss = 'auth.css';

require __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="brand-icon" style="margin: 0 auto 1rem; width: 48px; height: 48px; font-size: 1.5rem; background: linear-gradient(135deg, var(--color-indigo), #818cf8);">
                <i class="fa-solid fa-key"></i>
            </div>
            <h1>Lupa Kata Sandi?</h1>
            <p>Bantuan pemulihan kata sandi akun Etalase Digital Stella</p>
        </div>

        <div class="alert alert-info">
            <i class="fa-solid fa-circle-info"></i>
            <div>
                Penggantian kata sandi dikelola langsung oleh administrator sekolah. Silakan hubungi admin dengan menyebutkan alamat email akun Anda untuk dilakukan verifikasi dan peresetan kata sandi.
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1.5rem;">
            <a href="auth/login.php" class="btn btn-primary" style="width: 100%;">
                <i class="fa-solid fa-right-to-bracket"></i> Kembali ke Form Masuk
            </a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
