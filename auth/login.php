<?php
// ============================================================================
// auth/login.php - Form & Proses Login Multi-Role
// Etalase Digital Stella - SMK Telkom Lampung
// ============================================================================

require_once __DIR__ . '/../config/kuis_db.php';
require_once __DIR__ . '/../middleware/auth_check.php';

// Jika sudah login, redirect sesuai peran
if (is_logged_in()) {
    $role = current_role();
    if ($role === 'super_admin' || $role === 'admin') {
        header('Location: ../admin/index.php');
    } else {
        header('Location: ../index.php');
    }
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
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id'     => (int)$user['id'],
                'nama'   => $user['nama'],
                'email'  => $user['email'],
                'role'   => $user['role'],
                'avatar' => $user['avatar'] ?? null
            ];

            flash_set('success', 'Selamat datang kembali, <strong>' . e($user['nama']) . '</strong>! Berhasil masuk sebagai ' . role_name($user['role']) . '.');

            // Redirect Multi-Role: Admin & Super Admin diarahkan ke dashboard admin
            if ($user['role'] === 'super_admin' || $user['role'] === 'admin') {
                header('Location: ../admin/index.php');
            } else {
                header('Location: ../index.php');
            }
            exit;
        } else {
            $error = 'Email atau password yang Anda masukkan tidak valid!';
        }
    }
}

$pageTitle = 'Masuk Akun (Multi-Role)';
$activePage = 'login';
$pageCss = 'auth.css';

require __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="brand-icon" style="margin: 0 auto 1rem; width: 48px; height: 48px; font-size: 1.4rem;">
                <i class="fa-solid fa-lock"></i>
            </div>
            <h1>Masuk Akun</h1>
            <p>Akses panel multi-role Etalase Digital SMK Telkom Lampung</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-bottom: 1.25rem;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="auth-form">
            <div class="form-group">
                <label for="email" class="form-label">Alamat Email</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input type="email" name="email" id="email" class="form-control" 
                           placeholder="nama@stella.sch.id" required autofocus value="<?= e($email) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Kata Sandi</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" name="password" id="password" class="form-control" 
                           placeholder="Masukkan kata sandi..." required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                <i class="fa-solid fa-right-to-bracket"></i> Masuk Sekarang
            </button>
        </form>

        <div class="auth-footer">
            Belum punya akun siswa? <a href="register.php">Daftar Akun Siswa</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
