<?php
// ============================================================================
// register.php - Halaman Registrasi Siswa Baru
// ============================================================================
require_once dirname(__DIR__, 2) . '/config/config.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$nama = trim($_POST['nama'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (empty($nama) || empty($email) || empty($password)) {
        $error = 'Semua field wajib diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format alamat email tidak valid!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal terdiri dari 6 karakter!';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Konfirmasi password tidak cocok!';
    } else {
        // Cek apakah email sudah terdaftar
        $check = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $check->execute(['email' => $email]);
        if ($check->fetch()) {
            $error = 'Alamat email tersebut sudah terdaftar! Silakan gunakan email lain atau login.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (:nama, :email, :password, 'siswa')");
            $stmt->execute([
                'nama'     => $nama,
                'email'    => $email,
                'password' => $hashedPassword
            ]);

            $newUserId = (int)$pdo->lastInsertId();

            // Auto-login
            $_SESSION['user'] = [
                'id'    => $newUserId,
                'nama'  => $nama,
                'email' => $email,
                'role'  => 'siswa',
                'avatar' => null
            ];

            flash_set('success', 'Registrasi berhasil! Selamat datang di Etalase Digital, <strong>' . e($nama) . '</strong>.');
            header('Location: dashboard.php');
            exit;
        }
    }
}

$pageTitle = 'Daftar Akun Siswa - Etalase Digital';
$activePage = 'register';

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="brand-icon" style="margin: 0 auto 1rem; width: 48px; height: 48px; font-size: 1.5rem; background: linear-gradient(135deg, var(--color-indigo), #818cf8);">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h1>Daftar Akun Siswa</h1>
            <p style="color: var(--color-text-muted); font-size: 0.9rem;">
                Bergabunglah untuk memamerkan proyek digital Anda di SMK Telkom Lampung
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-bottom: 1.25rem;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="regNama" class="form-label">
                    Nama Lengkap Siswa <span class="required">*</span>
                </label>
                <input type="text" name="nama" id="regNama" class="form-control" value="<?= e($nama) ?>" placeholder="Contoh: Ahmad Fadhil Anwar" required autofocus>
            </div>

            <div class="form-group">
                <label for="regEmail" class="form-label">
                    Alamat Email Sekolah/Pribadi <span class="required">*</span>
                </label>
                <input type="email" name="email" id="regEmail" class="form-control" value="<?= e($email) ?>" placeholder="siswa@stella.sch.id" required>
            </div>

            <div class="form-group">
                <label for="regPass" class="form-label">
                    Password <span class="required">*</span>
                </label>
                <input type="password" name="password" id="regPass" class="form-control" placeholder="Minimal 6 karakter" required>
            </div>

            <div class="form-group">
                <label for="regPassConfirm" class="form-label">
                    Konfirmasi Password <span class="required">*</span>
                </label>
                <input type="password" name="password_confirm" id="regPassConfirm" class="form-control" placeholder="Ulangi password di atas" required>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 0.5rem;">
                <i class="fa-solid fa-paper-plane"></i> Buat Akun Siswa
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.88rem; color: var(--color-text-muted);">
            Sudah memiliki akun? <a href="login.php" style="color: var(--color-primary); font-weight: 700;">Masuk di sini</a>
        </div>
    </div>
</div>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
