<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
login_required();

$avatarColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'")->fetch();
if (!$avatarColumn) {
    $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL AFTER password");
}

$stmt = $pdo->prepare("SELECT id, nama, email, role, avatar FROM users WHERE id = :id LIMIT 1");
$stmt->execute(['id' => current_user_id()]);
$profile = $stmt->fetch();

if (!$profile) {
    flash_set('danger', 'Data akun tidak ditemukan. Silakan masuk kembali.');
    header('Location: logout.php');
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
        $error = 'Permintaan tidak valid. Muat ulang halaman lalu coba kembali.';
    } elseif ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Nama wajib diisi dan format email harus valid.';
    } else {
        $emailCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
        $emailCheck->execute(['email' => $email, 'id' => $profile['id']]);
        if ($emailCheck->fetch()) {
            $error = 'Email tersebut sudah digunakan akun lain.';
        }
    }

    $newAvatar = null;
    if ($error === '' && $avatarFile && $avatarFile['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($avatarFile['error'] !== UPLOAD_ERR_OK) {
            $error = 'Foto gagal diunggah. Silakan coba kembali.';
        } elseif ($avatarFile['size'] > 2 * 1024 * 1024) {
            $error = 'Ukuran foto maksimal 2 MB.';
        } else {
            $imageInfo = @getimagesize($avatarFile['tmp_name']);
            $mimeType = $imageInfo['mime'] ?? '';
            $allowedImageTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];

            if (!$imageInfo || !isset($allowedImageTypes[$mimeType])) {
                $error = 'Foto harus berformat JPG, PNG, atau WEBP.';
            } else {
                $avatarDirectory = dirname(__DIR__, 2) . '/uploads/profiles/';
                if (!is_dir($avatarDirectory) && !mkdir($avatarDirectory, 0755, true) && !is_dir($avatarDirectory)) {
                    $error = 'Folder penyimpanan foto profil tidak dapat dibuat.';
                } else {
                    $newAvatar = 'profile_' . (int)$profile['id'] . '_' . bin2hex(random_bytes(8)) . '.' . $allowedImageTypes[$mimeType];
                    if (!move_uploaded_file($avatarFile['tmp_name'], $avatarDirectory . $newAvatar)) {
                        $error = 'Foto gagal disimpan. Periksa izin folder uploads.';
                        $newAvatar = null;
                    }
                }
            }
        }
    }

    if ($error === '') {
        $avatar = $newAvatar ?? $profile['avatar'];
        try {
            $update = $pdo->prepare("UPDATE users SET nama = :nama, email = :email, avatar = :avatar WHERE id = :id");
            $update->execute([
                'nama' => $nama,
                'email' => $email,
                'avatar' => $avatar,
                'id' => $profile['id'],
            ]);
        } catch (PDOException $exception) {
            if ($newAvatar !== null) {
                @unlink(dirname(__DIR__, 2) . '/uploads/profiles/' . $newAvatar);
            }
            throw $exception;
        }

        if ($newAvatar !== null && !empty($profile['avatar'])) {
            $oldAvatarPath = dirname(__DIR__, 2) . '/uploads/profiles/' . basename($profile['avatar']);
            if (is_file($oldAvatarPath)) {
                unlink($oldAvatarPath);
            }
        }

        $_SESSION['user']['nama'] = $nama;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['avatar'] = $avatar;

        flash_set('success', 'Profil akun berhasil diperbarui.');
        header('Location: profile.php');
        exit;
    }
}

$pageTitle = 'Profil Saya';
$activePage = 'profile';
require dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="form-card profile-card">
    <div class="profile-heading">
        <h1>Profil Saya</h1>
        <p>Perbarui nama, email, dan foto profil akun Anda.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div><?= e($error) ?></div>
        </div>
    <?php endif; ?>

    <form action="profile.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['profile_csrf_token']) ?>">
        <div class="profile-avatar-preview">
            <?php if (!empty($profile['avatar']) && $error === ''): ?>
                <img src="uploads/profiles/<?= e(rawurlencode($profile['avatar'])) ?>" alt="Foto profil saat ini">
            <?php else: ?>
                <span><?= e(strtoupper(substr($nama, 0, 1))) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="profileAvatar" class="form-label">Foto Profil</label>
            <input type="file" name="avatar" id="profileAvatar" class="form-control" accept="image/jpeg,image/png,image/webp">
            <div class="form-hint">Pilih gambar JPG, PNG, atau WEBP. Maksimal 2 MB. Kosongkan jika tidak ingin mengganti foto.</div>
        </div>

        <div class="form-group">
            <label for="profileName" class="form-label">Nama Lengkap <span class="required">*</span></label>
            <input type="text" name="nama" id="profileName" class="form-control" value="<?= e($nama) ?>" maxlength="100" required>
        </div>

        <div class="form-group">
            <label for="profileEmail" class="form-label">Alamat Email <span class="required">*</span></label>
            <input type="email" name="email" id="profileEmail" class="form-control" value="<?= e($email) ?>" maxlength="100" required>
        </div>

        <div class="profile-role">
            <span class="form-label">Hak Akses</span>
            <?= role_badge($profile['role']) ?>
            <div class="form-hint">Hak akses akun hanya dapat diubah oleh administrator.</div>
        </div>

        <div class="profile-actions">
            <a href="dashboard.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan Profil</button>
        </div>
    </form>
</div>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
