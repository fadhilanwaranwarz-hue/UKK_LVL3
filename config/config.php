<?php
// ============================================================================
// ETALASE DIGITAL - SMK TELKOM LAMPUNG
// Pembuat : Ahmad Fadhil Anwar (Kelas XI RPL)
// Tugas   : UKK / LKPD Web Fullstack - CRUD & Hak Akses
// ============================================================================

require_once __DIR__ . '/kuis_db.php';

// Helper Autentikasi
function is_logged_in() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function current_role() {
    return $_SESSION['user']['role'] ?? null;
}

function current_user_id() {
    return $_SESSION['user']['id'] ?? 0;
}

function login_required($redirect = 'login.php') {
    if (!is_logged_in()) {
        flash_set('warning', 'Silakan login terlebih dahulu untuk mengakses halaman tersebut.');
        header("Location: {$redirect}");
        exit;
    }
}

function role_required($allowedRoles, $redirect = 'dashboard.php') {
    login_required();
    $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    if (!in_array(current_role(), $roles, true)) {
        flash_set('danger', 'Akses ditolak. Anda tidak memiliki izin untuk halaman tersebut.');
        header("Location: {$redirect}");
        exit;
    }
}

// Helper Hak Akses Project
function can_manage_project($project) {
    if (!is_logged_in() || empty($project)) return false;
    $role = current_role();
    if ($role === 'super_admin' || $role === 'admin') {
        return true;
    }
    if ($role === 'siswa' && (int)$project['user_id'] === (int)current_user_id()) {
        return true;
    }
    return false;
}

// Helper Label & Badge Role
function role_name($role) {
    $names = [
        'super_admin' => 'Super Admin',
        'admin'       => 'Admin',
        'siswa'       => 'Siswa'
    ];
    return $names[$role] ?? ucfirst($role ?? '');
}

function role_badge($role) {
    switch ($role) {
        case 'super_admin':
            return '<span class="badge badge-super"><i class="fa-solid fa-crown me-1"></i>Super Admin</span>';
        case 'admin':
            return '<span class="badge badge-admin"><i class="fa-solid fa-shield-halved me-1"></i>Admin</span>';
        case 'siswa':
            return '<span class="badge badge-siswa"><i class="fa-solid fa-graduation-cap me-1"></i>Siswa</span>';
        default:
            return '<span class="badge badge-secondary">' . e($role) . '</span>';
    }
}

// Helper Flash Message (Notifikasi Alert)
function flash_set($type, $message) {
    $_SESSION['flash_msg'] = [
        'type'    => $type,    // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function flash_get() {
    if (isset($_SESSION['flash_msg'])) {
        $flash = $_SESSION['flash_msg'];
        unset($_SESSION['flash_msg']);
        return $flash;
    }
    return null;
}

// Helper Upload Gambar Project
function handle_image_upload($fileInputName, $existingImage = 'default-project.svg') {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => $existingImage ?: 'default-project.svg'];
    }

    $file = $_FILES[$fileInputName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Gagal mengupload berkas (Kode error: ' . $file['error'] . ')'];
    }

    // Maksimal 3MB
    $maxSize = 3 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Ukuran file terlalu besar! Maksimal 3 MB.'];
    }

    // Ekstensi yang diizinkan
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExts, true)) {
        return ['success' => false, 'error' => 'Format file tidak didukung! Gunakan JPG, PNG, WEBP, atau SVG.'];
    }

    $uploadDir = dirname(__DIR__) . '/uploads/projects/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $newFilename = 'project_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = $uploadDir . $newFilename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Hapus file lama jika bukan file default
        if ($existingImage && !in_array($existingImage, ['default-project.svg', 'kantinku-preview.svg', 'sim-alumni.svg', 'stella-library.svg'])) {
            $oldPath = $uploadDir . $existingImage;
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }
        return ['success' => true, 'filename' => $newFilename];
    }

    return ['success' => false, 'error' => 'Gagal memindahkan file ke folder uploads.'];
}
