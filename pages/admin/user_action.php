<?php
// ============================================================================
// user_action.php - Pemrosesan CRUD Data User & Siswa
// ============================================================================
require_once dirname(__DIR__, 2) . '/config/config.php';
role_required(['super_admin', 'admin']);

$currentRole = current_role();
$currentUserId = current_user_id();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ----------------------------------------------------------------------------
// 1. ACTION: DELETE USER
// ----------------------------------------------------------------------------
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

    // Proteksi: Tidak boleh menghapus diri sendiri!
    if ($id === (int)$currentUserId) {
        flash_set('danger', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan!');
        header('Location: users.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $targetUser = $stmt->fetch();

    if (!$targetUser) {
        flash_set('danger', 'Pengguna tidak ditemukan.');
        header('Location: users.php');
        exit;
    }

    // Proteksi: Admin biasa HANYA boleh menghapus akun role 'siswa'!
    if ($currentRole === 'admin' && $targetUser['role'] !== 'siswa') {
        flash_set('danger', 'Akses ditolak! Administrator hanya diizinkan menghapus akun siswa.');
        header('Location: users.php');
        exit;
    }

    // Hapus file gambar project yang terkait pengguna ini
    $projStmt = $pdo->prepare("SELECT gambar FROM projects WHERE user_id = :userId");
    $projStmt->execute(['userId' => $id]);
    $projects = $projStmt->fetchAll();

    $defaultImages = ['default-project.svg', 'kantinku-preview.svg', 'sim-alumni.svg', 'stella-library.svg'];
    foreach ($projects as $pr) {
        if (!empty($pr['gambar']) && !in_array($pr['gambar'], $defaultImages, true)) {
            $filePath = dirname(__DIR__, 2) . '/uploads/projects/' . $pr['gambar'];
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }
    }

    // Hapus Pengguna (ON DELETE CASCADE akan otomatis menghapus entri project di DB)
    $del = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $del->execute(['id' => $id]);

    flash_set('success', 'Akun pengguna "<strong>' . e($targetUser['nama']) . '</strong>" berhasil dihapus dari sistem.');
    header('Location: users.php');
    exit;
}

// ----------------------------------------------------------------------------
// 2. ACTION: CREATE & UPDATE USER (POST)
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

$nama  = trim($_POST['nama'] ?? '');
$email = trim($_POST['email'] ?? '');
$role  = $_POST['role'] ?? 'siswa';

if (empty($nama) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash_set('danger', 'Nama dan format email harus valid!');
    header('Location: ' . ($action === 'update' ? "user_form.php?id=" . (int)$_POST['id'] : "user_form.php"));
    exit;
}

// Validasi Batasan Role oleh Admin
if ($currentRole === 'admin') {
    $role = 'siswa'; // Paksa role 'siswa' untuk pencegahan eskalasi hak akses
} else {
    if (!in_array($role, ['super_admin', 'admin', 'siswa'], true)) {
        $role = 'siswa';
    }
}

// 2A. CREATE USER
if ($action === 'create') {
    $password = $_POST['password'] ?? '';

    if (strlen($password) < 6) {
        flash_set('danger', 'Password minimal terdiri dari 6 karakter!');
        header('Location: user_form.php');
        exit;
    }

    // Cek Keunikan Email
    $check = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $check->execute(['email' => $email]);
    if ($check->fetch()) {
        flash_set('danger', 'Alamat email "<strong>' . e($email) . '</strong>" sudah terdaftar. Silakan gunakan email lain.');
        header('Location: user_form.php');
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $ins = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (:nama, :email, :password, :role)");
    $ins->execute([
        'nama'     => $nama,
        'email'    => $email,
        'password' => $hashedPassword,
        'role'     => $role
    ]);

    flash_set('success', 'Pengguna baru "<strong>' . e($nama) . '</strong>" (' . role_name($role) . ') berhasil ditambahkan!');
    header('Location: users.php');
    exit;
}

// 2B. UPDATE USER
if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $targetUser = $stmt->fetch();

    if (!$targetUser) {
        flash_set('danger', 'Pengguna tidak ditemukan.');
        header('Location: users.php');
        exit;
    }

    // Proteksi: Admin biasa HANYA boleh edit akun role 'siswa'!
    if ($currentRole === 'admin' && $targetUser['role'] !== 'siswa') {
        flash_set('danger', 'Akses ditolak! Administrator hanya diizinkan mengedit akun siswa.');
        header('Location: users.php');
        exit;
    }

    // Cek Keunikan Email jika diubah
    $check = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
    $check->execute(['email' => $email, 'id' => $id]);
    if ($check->fetch()) {
        flash_set('danger', 'Alamat email "<strong>' . e($email) . '</strong>" sudah digunakan oleh akun lain.');
        header("Location: user_form.php?id={$id}");
        exit;
    }

    $password = $_POST['password'] ?? '';
    if (!empty($password)) {
        if (strlen($password) < 6) {
            flash_set('danger', 'Password baru minimal 6 karakter!');
            header("Location: user_form.php?id={$id}");
            exit;
        }
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $upd = $pdo->prepare("UPDATE users SET nama = :nama, email = :email, role = :role, password = :password WHERE id = :id");
        $upd->execute([
            'nama'     => $nama,
            'email'    => $email,
            'role'     => $role,
            'password' => $hashedPassword,
            'id'       => $id
        ]);
    } else {
        $upd = $pdo->prepare("UPDATE users SET nama = :nama, email = :email, role = :role WHERE id = :id");
        $upd->execute([
            'nama'  => $nama,
            'email' => $email,
            'role'  => $role,
            'id'    => $id
        ]);
    }

    // Jika yang diupdate adalah profil diri sendiri, perbarui data di session
    if ($id === (int)$currentUserId) {
        $_SESSION['user']['nama'] = $nama;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['role'] = $role;
    }

    flash_set('success', 'Data pengguna "<strong>' . e($nama) . '</strong>" berhasil diperbarui!');
    header('Location: users.php');
    exit;
}

header('Location: users.php');
exit;
