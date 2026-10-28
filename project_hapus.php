<?php
// ============================================================================
// project_hapus.php - Proses Hapus Project + Gambar
// Sesuai Ketentuan Rubrik Penilaian
// Etalase Digital Stella - SMK Telkom Lampung
// ============================================================================

require_once __DIR__ . '/config/kuis_db.php';
require_once __DIR__ . '/middleware/auth_check.php';

// Wajib Login
auth_check('auth/login.php');

$currentRole = current_role();
$currentUserId = current_user_id();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    flash_set('danger', 'ID Project tidak valid.');
    header('Location: index.php');
    exit;
}

// Ambil data project
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = :id");
$stmt->execute(['id' => $id]);
$project = $stmt->fetch();

if (!$project) {
    flash_set('danger', 'Project tidak ditemukan atau sudah dihapus sebelumnya.');
    header('Location: index.php');
    exit;
}

// Validasi Hak Akses:
// Super Admin & Admin boleh menghapus semua project.
// Siswa HANYA boleh menghapus project miliknya sendiri!
if (!can_manage_project($project)) {
    flash_set('danger', 'Akses ditolak! Anda tidak memiliki wewenang untuk menghapus project milik siswa lain.');
    header('Location: index.php');
    exit;
}

// 1. Hapus Berkas Gambar Fisik dari uploads/projects/ jika bukan file default sistem
$gambar = $project['gambar'];
$defaultImages = ['default-project.svg', 'kantinku-preview.svg', 'sim-alumni.svg', 'stella-library.svg'];

if (!empty($gambar) && !in_array($gambar, $defaultImages, true)) {
    $filePath = __DIR__ . '/uploads/projects/' . $gambar;
    if (is_file($filePath)) {
        @unlink($filePath);
    }
}

// 2. Hapus Catatan Project dari Basis Data
$del = $pdo->prepare("DELETE FROM projects WHERE id = :id");
$del->execute(['id' => $id]);

flash_set('success', 'Project "<strong>' . e($project['nama_project']) . '</strong>" beserta berkas gambarnya berhasil dihapus.');

// Redirect kembali ke halaman asal (Dashboard Admin atau Halaman Utama)
if ($currentRole === 'super_admin' || $currentRole === 'admin') {
    header('Location: admin/index.php');
} else {
    header('Location: index.php');
}
exit;
