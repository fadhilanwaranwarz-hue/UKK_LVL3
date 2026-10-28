<?php
// ============================================================================
// profile.php - Halaman Profil Pengguna & Edit Avatar
// Fitur Tambahan Terintegrasi
// Etalase Digital Stella - SMK Telkom Lampung
// ============================================================================

require_once __DIR__ . '/config/kuis_db.php';
require_once __DIR__ . '/middleware/auth_check.php';

auth_check('auth/login.php');

$currentUserId = current_user_id();

// Pastikan kolom avatar ada di tabel users
$avatarCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'")->fetch();
if (!$avatarCol) {
    $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL AFTER password");
}

$stmt = $pdo->prepare("SELECT id, nama, email, role, avatar FROM users WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $currentUserId]);
$profile = $stmt->fetch();

if (!$profile) {
    flash_set('danger', 'Data akun tidak ditemukan. Silakan masuk kembali.');
    header('Location: auth/logout.php');
    exit;
}

$error = '';
$nama = $profile['nama'];
$email = $profile['email'];

if (empty($_SESSION['profile_csrf_token'])) {
    $_SESSION['profile_csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $avatarFile = $_FILES['avatar'] ?? null;
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) || !hash_equals($_SESSION['profile_csrf_token'], $submittedToken)) {
        $error = 'Permintaan tidak valid. Silakan muat ulang halaman.';
    } elseif ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Nama wajib diisi dan format email harus valid.';
    } else {
        $emailCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
        $emailCheck->execute(['email' => $email, 'id' => $profile['id']]);
        if ($emailCheck->fetch()) {
            $error = 'Email tersebut sudah digunakan oleh akun lain.';
        }
    }

    $newAvatar = null;
    if ($error === '' && $avatarFile && $avatarFile['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($avatarFile['error'] !== UPLOAD_ERR_OK) {
            $error = 'Foto gagal diunggah. Silakan coba kembali.';
        } elseif ($avatarFile['size'] > 2 * 1024 * 1024) {
            $error = 'Ukuran foto maksimal 2 MB.';
        } else {
            $ext = strtolower(pathinfo($avatarFile['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowed, true)) {
                $error = 'Format foto harus berupa JPG, PNG, atau WEBP.';
            } else {
                $avatarDirectory = __DIR__ . '/uploads/profiles/';
                if (!is_dir($avatarDirectory)) {
                    mkdir($avatarDirectory, 0755, true);
                }

                $newAvatar = 'profile_' . (int)$profile['id'] . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                if (!move_uploaded_file($avatarFile['tmp_name'], $avatarDirectory . $newAvatar)) {
                    $error = 'Foto gagal disimpan ke folder server.';
                    $newAvatar = null;
                }
            }
        }
    }

    if ($error === '') {
        $avatar = $newAvatar ?? $profile['avatar'];
        
        $update = $pdo->prepare("UPDATE users SET nama = :nama, email = :email, avatar = :avatar WHERE id = :id");
        $update->execute([
            'nama'   => $nama,
            'email'  => $email,
            'avatar' => $avatar,
            'id'     => $profile['id'],
        ]);

        // Hapus foto lama jika diganti
        if ($newAvatar !== null && !empty($profile['avatar'])) {
            $oldAvatarPath = __DIR__ . '/uploads/profiles/' . basename($profile['avatar']);
            if (is_file($oldAvatarPath)) {
                @unlink($oldAvatarPath);
            }
        }

        $_SESSION['user']['nama']   = $nama;
        $_SESSION['user']['email']  = $email;
        $_SESSION['user']['avatar'] = $avatar;

        flash_set('success', 'Profil akun Anda berhasil diperbarui.');
        header('Location: profile.php');
        exit;
    }
}

$pageTitle = 'Profil Saya';
$activePage = 'profile';
$pageCss = 'form.css';

require __DIR__ . '/includes/header.php';
?>

<div class="form-card" style="max-width: 600px;">
    <div class="form-card-header" style="text-align: center;">
        <div class="brand-icon" style="margin: 0 auto 1rem; width: 48px; height: 48px; font-size: 1.4rem;">
            <i class="fa-solid fa-user-gear"></i>
        </div>
        <h1>Profil Akun Saya</h1>
        <p>Kelola data diri dan foto profil Anda</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div><?= e($error) ?></div>
        </div>
    <?php endif; ?>

    <form action="profile.php" method="POST" enctype="multipart/form-data" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['profile_csrf_token']) ?>">

        <!-- Pratinjau Avatar -->
        <div style="text-align: center; margin-bottom: 0.5rem;">
            <div style="width: 90px; height: 90px; border-radius: 50%; margin: 0 auto 0.75rem; overflow: hidden; background: linear-gradient(135deg, var(--color-indigo), #818cf8); display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #fff; font-weight: 800; border: 3px solid var(--color-border);">
                <?php if (!empty($profile['avatar']) && is_file(__DIR__ . '/uploads/profiles/' . $profile['avatar'])): ?>
                    <img src="uploads/profiles/<?= e($profile['avatar']) ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <?= e(strtoupper(substr($nama, 0, 1))) ?>
                <?php endif; ?>
            </div>
            <div style="font-size: 0.85rem; color: var(--color-text-muted);">
                Hak Akses: <?= role_badge($profile['role']) ?>
            </div>
        </div>

        <div class="form-group">
            <label for="profileAvatar" class="form-label">Ganti Foto Profil</label>
            <input type="file" name="avatar" id="profileAvatar" class="form-control" accept="image/jpeg,image/png,image/webp">
            <div class="form-hint">Format didukung: JPG, PNG, WEBP. Maksimal 2 MB. Kosongkan jika tidak ingin mengganti.</div>
        </div>

        <div class="form-group">
            <label for="profileName" class="form-label">Nama Lengkap <span class="required">*</span></label>
            <input type="text" name="nama" id="profileName" class="form-control" value="<?= e($nama) ?>" required>
        </div>

        <div class="form-group">
            <label for="profileEmail" class="form-label">Alamat Email <span class="required">*</span></label>
            <input type="email" name="email" id="profileEmail" class="form-control" value="<?= e($email) ?>" required>
        </div>

        <div class="form-actions">
            <a href="index.php" class="btn btn-outline">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Profil
            </button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
