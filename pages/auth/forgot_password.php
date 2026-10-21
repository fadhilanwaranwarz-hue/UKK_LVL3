<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Lupa Password - Etalase Digital';
$activePage = 'login';

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="brand-icon" style="margin: 0 auto 1rem; width: 48px; height: 48px; font-size: 1.5rem; background: linear-gradient(135deg, var(--color-indigo), #818cf8);">
                <i class="fa-solid fa-key"></i>
            </div>
            <h1>Lupa Password?</h1>
            <p style="color: var(--color-text-muted); font-size: 0.9rem;">
                Minta bantuan administrator untuk mengganti password akun Anda.
            </p>
        </div>

        <div class="alert alert-info" role="status">
            <i class="fa-solid fa-circle-info"></i>
            <div>
                Demi keamanan akun, penggantian password dilakukan oleh administrator.
                Sampaikan alamat email akun Anda kepada admin sekolah. Admin dapat membantu
                mereset akun siswa, sedangkan akun administrator perlu dibantu Super Admin.
                Setelah identitas diverifikasi, password baru dapat ditetapkan melalui menu
                <strong>Kelola Siswa/Pengguna</strong>.
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1.5rem;">
            <a href="login.php" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-right-to-bracket"></i> Kembali ke Login
            </a>
            <a href="index.php" class="btn btn-outline">
                <i class="fa-solid fa-house"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
</div>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
