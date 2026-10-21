<?php
// ============================================================================
// login.php - Halaman Masuk Akun Multi-Role
// ============================================================================
require_once dirname(__DIR__, 2) . '/config/config.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi!';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID untuk keamanan
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id'    => (int)$user['id'],
                'nama'  => $user['nama'],
                'email' => $user['email'],
                'role'  => $user['role'],
                'avatar' => $user['avatar'] ?? null
            ];

            flash_set('success', 'Selamat datang kembali, <strong>' . e($user['nama']) . '</strong>! Login berhasil sebagai ' . role_name($user['role']) . '.');
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Email atau password yang Anda masukkan salah!';
        }
    }
}

$pageTitle = 'Masuk Akun - Etalase Digital';
$activePage = 'login';

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="brand-icon" style="margin: 0 auto 1rem; width: 48px; height: 48px; font-size: 1.5rem;">
                <i class="fa-solid fa-lock"></i>
            </div>
            <h1>Masuk Akun</h1>
            <p style="color: var(--color-text-muted); font-size: 0.9rem;">
                Akses panel kontrol Etalase Digital SMK Telkom Lampung
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-bottom: 1.25rem;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="loginEmail" class="form-label">
                    Alamat Email <span class="required">*</span>
                </label>
                <div style="position: relative;">
                    <input type="email" name="email" id="loginEmail" class="form-control" value="<?= e($email) ?>" placeholder="nama@stella.sch.id" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label for="loginPassword" class="form-label">
                    Password <span class="required">*</span>
                </label>
                <input type="password" name="password" id="loginPassword" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 0.5rem;">
                <i class="fa-solid fa-right-to-bracket"></i> Masuk Sekarang
            </button>
        </form>

        <div style="text-align: center; margin-top: 1rem; font-size: 0.88rem;">
            <a href="forgot_password.php" style="color: var(--color-primary); font-weight: 700;">Lupa password?</a>
        </div>

        <!-- Akun Demo Cepat (Quick Login) -->
        <div class="demo-account-box">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--color-text-main);">
                    <i class="fa-solid fa-wand-magic-sparkles text-primary me-1"></i> Klik untuk Isi Akun Demo:
                </span>
                <span style="font-size: 0.72rem; color: var(--color-text-muted);">
                    Pass: password123
                </span>
            </div>
            <div class="demo-account-grid">
                <button type="button" class="btn-demo-account" data-demo-email="superadmin@stella.sch.id">
                    <strong>Super Admin</strong>
                    <div style="font-size: 0.68rem; color: var(--color-text-muted);">superadmin@stella.sch.id</div>
                </button>
                <button type="button" class="btn-demo-account" data-demo-email="admin@stella.sch.id">
                    <strong>Admin Stella</strong>
                    <div style="font-size: 0.68rem; color: var(--color-text-muted);">admin@stella.sch.id</div>
                </button>
                <button type="button" class="btn-demo-account" data-demo-email="fadhil@stella.sch.id">
                    <strong>Ahmad Fadhil</strong>
                    <div style="font-size: 0.68rem; color: var(--color-text-muted);">fadhil@stella.sch.id (Siswa)</div>
                </button>
                <button type="button" class="btn-demo-account" data-demo-email="siswa@stella.sch.id">
                    <strong>Siswa Contoh</strong>
                    <div style="font-size: 0.68rem; color: var(--color-text-muted);">siswa@stella.sch.id (Siswa)</div>
                </button>
            </div>
        </div>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.88rem; color: var(--color-text-muted);">
            Belum punya akun? <a href="register.php" style="color: var(--color-primary); font-weight: 700;">Daftar Siswa</a>
        </div>
    </div>
</div>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
