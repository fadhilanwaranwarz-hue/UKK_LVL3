<?php
// ============================================================================
// project_action.php - Pemrosesan CRUD Project dengan Validasi Hak Akses Ketat
// ============================================================================
require_once dirname(__DIR__, 2) . '/config/config.php';
login_required();

$currentRole = current_role();
$currentUserId = current_user_id();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ----------------------------------------------------------------------------
// 1. ACTION: DELETE
// ----------------------------------------------------------------------------
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $project = $stmt->fetch();

    if (!$project) {
        flash_set('danger', 'Project tidak ditemukan atau sudah dihapus.');
        header('Location: dashboard.php');
        exit;
    }

    // Validasi Hak Akses: Siswa HANYA boleh hapus miliknya sendiri!
    if (!can_manage_project($project)) {
        flash_set('danger', 'Akses ditolak! Anda tidak memiliki wewenang untuk menghapus project milik siswa lain.');
        header('Location: dashboard.php');
        exit;
    }

    // Hapus berkas gambar fisik jika bukan aset default
    $gambar = $project['gambar'];
    $defaultImages = ['default-project.svg', 'kantinku-preview.svg', 'sim-alumni.svg', 'stella-library.svg'];
    if (!empty($gambar) && !in_array($gambar, $defaultImages, true)) {
        $filePath = dirname(__DIR__, 2) . '/uploads/projects/' . $gambar;
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }

    // Eksekusi Hapus dari Database
    $del = $pdo->prepare("DELETE FROM projects WHERE id = :id");
    $del->execute(['id' => $id]);

    flash_set('success', 'Project "<strong>' . e($project['nama_project']) . '</strong>" berhasil dihapus.');
    header('Location: dashboard.php');
    exit;
}

// ----------------------------------------------------------------------------
// 2. ACTION: CREATE & UPDATE (POST)
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$namaProject = trim($_POST['nama_project'] ?? '');
$deskripsi   = trim($_POST['deskripsi'] ?? '');
$linkProject = trim($_POST['link_project'] ?? '');
$demo        = trim($_POST['demo'] ?? '');

// Validasi Form Wajib
if (empty($namaProject) || empty($deskripsi) || empty($linkProject)) {
    flash_set('danger', 'Harap isi semua kolom wajib (Nama Project, Deskripsi, dan Link Pendukung).');
    header('Location: ' . ($action === 'update' ? "project_form.php?id=" . (int)$_POST['id'] : "project_form.php"));
    exit;
}

// Penentuan Pemilik Project
$targetUserId = $currentUserId;
if ($currentRole === 'super_admin' || $currentRole === 'admin') {
    if (!empty($_POST['user_id'])) {
        $targetUserId = (int)$_POST['user_id'];
    }
}

// 2A. CREATE NEW PROJECT
if ($action === 'create') {
    $upload = handle_image_upload('gambar', 'default-project.svg');
    if (!$upload['success']) {
        flash_set('danger', $upload['error']);
        header('Location: project_form.php');
        exit;
    }

    $gambar = $upload['filename'];

    $stmt = $pdo->prepare("INSERT INTO projects (user_id, nama_project, deskripsi, gambar, link_project, demo) 
                           VALUES (:user_id, :nama_project, :deskripsi, :gambar, :link_project, :demo)");
    $stmt->execute([
        'user_id'      => $targetUserId,
        'nama_project' => $namaProject,
        'deskripsi'    => $deskripsi,
        'gambar'       => $gambar,
        'link_project' => $linkProject,
        'demo'         => $demo ?: null
    ]);

    flash_set('success', 'Karya project baru "<strong>' . e($namaProject) . '</strong>" berhasil dipublikasikan ke Etalase Digital!');
    header('Location: dashboard.php');
    exit;
}

// 2B. UPDATE EXISTING PROJECT
if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $project = $stmt->fetch();

    if (!$project) {
        flash_set('danger', 'Project tidak ditemukan.');
        header('Location: dashboard.php');
        exit;
    }

    // Validasi Hak Akses Edit
    if (!can_manage_project($project)) {
        flash_set('danger', 'Akses ditolak! Anda hanya berhak mengedit karya project milik Anda sendiri.');
        header('Location: dashboard.php');
        exit;
    }

    $gambar = $project['gambar'];
    // Jika ada file gambar baru yang diupload
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = handle_image_upload('gambar', $project['gambar']);
        if (!$upload['success']) {
            flash_set('danger', $upload['error']);
            header("Location: project_form.php?id={$id}");
            exit;
        }
        $gambar = $upload['filename'];
    }

    // Untuk Siswa, jangan ubah user_id (tetap miliknya). Untuk Admin, boleh ubah.
    if ($currentRole === 'siswa') {
        $targetUserId = (int)$project['user_id'];
    }

    $updateStmt = $pdo->prepare("UPDATE projects SET 
        user_id = :user_id,
        nama_project = :nama_project,
        deskripsi = :deskripsi,
        gambar = :gambar,
        link_project = :link_project,
        demo = :demo
        WHERE id = :id");

    $updateStmt->execute([
        'user_id'      => $targetUserId,
        'nama_project' => $namaProject,
        'deskripsi'    => $deskripsi,
        'gambar'       => $gambar,
        'link_project' => $linkProject,
        'demo'         => $demo ?: null,
        'id'           => $id
    ]);

    flash_set('success', 'Data project "<strong>' . e($namaProject) . '</strong>" berhasil diperbarui!');
    header('Location: dashboard.php');
    exit;
}

header('Location: dashboard.php');
exit;
