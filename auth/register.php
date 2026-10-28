<?php
// ============================================================================
// auth/register.php - Registrasi Akun Siswa
// Etalase Digital Stella - SMK Telkom Lampung
// ============================================================================

require_once __DIR__ . '/../config/kuis_db.php';
require_once __DIR__ . '/../middleware/auth_check.php';

if (is_logged_in()) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$nama  = trim($_POST['nama'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($nama) || empty($email) || empty($password)) {
        $error = 'Semua kolom wajib diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format alamat email tidak valid!';
    } elseif (strlen($password) < 6) {
        $error = 'Kata sandi minimal harus 6 karakter!';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi kata sandi tidak cocok!';
    } else {
        // Cek keunikan email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $error = 'Alamat email tersebut sudah terdaftar. Silakan gunakan email lain atau langsung masuk.';
        } else {
            // Hash password dengan BCRYPT
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            // Hak akses default saat registrasi publik adalah 'siswa'
            $insert = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (:nama, :email, :password, 'siswa')");
            $insert->execute([
                'nama'     => $nama,
                'email'    => $email,
                'password' => $hashedPassword
            ]);

            flash_set('success', 'Akun Siswa atas nama <strong>' . e($nama) . '</strong> berhasil didaftarkan! Silakan masuk dengan akun Anda.');
            header('Location: login.php');
            exit;
        }
    }
}

$pageTitle = 'Registrasi Akun Siswa';
$activePage = 'register';
$pageCss = 'auth.css';

require __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="brand-icon" style="margin: 0 auto 1rem; width: 48px; height: 48px; font-size: 1.4rem; background: linear-gradient(135deg, var(--color-indigo), #818cf8);">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h1>Daftar Akun Siswa</h1>
            <p>Bergabunglah untuk mempublikasikan proyek karya digital Anda</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-bottom: 1.25rem;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="auth-form">
            <div class="form-group">
                <label for="nama" class="form-label">Nama Lengkap</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-user input-icon"></i>
                    <input type="text" name="nama" id="nama" class="form-control" 
                           placeholder="Contoh: Ahmad Fadhil Anwar" required autofocus value="<?= e($nama) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Alamat Email</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input type="email" name="email" id="email" class="form-control" 
                           placeholder="nama@stella.sch.id" required value="<?= e($email) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Kata Sandi (Minimal 6 Karakter)</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" name="password" id="password" class="form-control" 
                           placeholder="Buat kata sandi..." required minlength="6">
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Konfirmasi Kata Sandi</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-shield-check input-icon"></i>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                           placeholder="Ulangi kata sandi..." required minlength="6">
                </div>
            </div>

            <button type="submit" class="btn btn-indigo" style="width: 100%; margin-top: 0.5rem;">
                <i class="fa-solid fa-user-check"></i> Daftar Sekarang
            </button>
        </form>

        <div class="auth-footer">
            Sudah memiliki akun? <a href="login.php">Masuk di sini</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
