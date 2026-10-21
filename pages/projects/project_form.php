<?php
// ============================================================================
// project_form.php - Form Tambah & Edit Karya Project
// ============================================================================
require_once dirname(__DIR__, 2) . '/config/config.php';
login_required();

$currentRole = current_role();
$currentUserId = current_user_id();

$id = (int)($_GET['id'] ?? 0);
$isEdit = ($id > 0);

$project = null;
if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $project = $stmt->fetch();

    if (!$project) {
        flash_set('danger', 'Project tidak ditemukan.');
        header('Location: dashboard.php');
        exit;
    }

    // Cek Hak Akses Edit
    if (!can_manage_project($project)) {
        flash_set('danger', 'Akses ditolak! Anda hanya berhak mengedit karya project milik Anda sendiri.');
        header('Location: dashboard.php');
        exit;
    }
}

// Ambil list siswa jika Admin / Super Admin (agar bisa assign pemilik karya)
$students = [];
if ($currentRole === 'super_admin' || $currentRole === 'admin') {
    $students = $pdo->query("SELECT id, nama, email FROM users WHERE role = 'siswa' ORDER BY nama ASC")->fetchAll();
}

$pageTitle = $isEdit ? 'Edit Project: ' . $project['nama_project'] : 'Tambah Project Baru';
$activePage = $isEdit ? '' : 'add_project';

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<div style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.88rem; color: var(--color-text-muted);">
    <a href="dashboard.php" style="color: var(--color-text-muted);"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <span>/</span>
    <span style="color: var(--color-primary); font-weight: 600;">
        <?= $isEdit ? 'Edit Project' : 'Tambah Project Baru' ?>
    </span>
</div>

<div class="form-card">
    <div style="margin-bottom: 2rem; text-align: center;">
        <div class="brand-icon" style="margin: 0 auto 1rem; width: 48px; height: 48px; font-size: 1.35rem;">
            <i class="fa-solid <?= $isEdit ? 'fa-pen-to-square' : 'fa-folder-plus' ?>"></i>
        </div>
        <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--color-text-main); margin-bottom: 0.35rem;">
            <?= $isEdit ? 'Perbarui Data Project' : 'Publikasikan Project Baru' ?>
        </h1>
        <p style="color: var(--color-text-muted); font-size: 0.9rem;">
            Lengkapi informasi karya proyek digital untuk dipamerkan di Etalase Stella
        </p>
    </div>

    <form action="project_action.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$project['id'] ?>">
        <?php endif; ?>

        <!-- Nama Project -->
        <div class="form-group">
            <label for="namaProject" class="form-label">
                Nama / Judul Project <span class="required">*</span>
            </label>
            <input type="text" name="nama_project" id="namaProject" class="form-control" value="<?= e($project['nama_project'] ?? '') ?>" placeholder="Contoh: Aplikasi Pemesanan Kantin Digital" required autofocus>
            <div class="form-hint">Gunakan nama yang jelas, menarik, dan mencerminkan aplikasi yang Anda buat.</div>
        </div>

        <!-- Pemilik Project (Hanya tampil pilihan bagi Admin & Super Admin) -->
        <?php if ($currentRole === 'super_admin' || $currentRole === 'admin'): ?>
            <div class="form-group">
                <label for="userIdSelect" class="form-label">
                    Pemilik / Siswa Pembuat Karya <span class="required">*</span>
                </label>
                <select name="user_id" id="userIdSelect" class="form-control" required>
                    <option value="">-- Pilih Siswa Pemilik --</option>
                    <?php foreach ($students as $stu): ?>
                        <?php 
                        $selected = ($isEdit && (int)$project['user_id'] === (int)$stu['id']) ? 'selected' : '';
                        if (!$isEdit && (int)$currentUserId === (int)$stu['id']) $selected = 'selected';
                        ?>
                        <option value="<?= $stu['id'] ?>" <?= $selected ?>>
                            <?= e($stu['nama']) ?> (<?= e($stu['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-hint">Sebagai <?= role_name($currentRole) ?>, Anda dapat mengaitkan karya ini ke siswa yang bersangkutan.</div>
            </div>
        <?php else: ?>
            <input type="hidden" name="user_id" value="<?= (int)$currentUserId ?>">
        <?php endif; ?>

        <!-- Deskripsi Project -->
        <div class="form-group">
            <label for="deskripsiProject" class="form-label">
                Deskripsi Lengkap Project <span class="required">*</span>
            </label>
            <textarea name="deskripsi" id="deskripsiProject" class="form-control" rows="5" placeholder="Jelaskan tujuan project, fitur utama, teknologi yang digunakan, dan manfaat aplikasi..." required><?= e($project['deskripsi'] ?? '') ?></textarea>
            <div class="form-hint">Jelaskan latar belakang, alur sistem, dan fitur keunggulan proyek secara ringkas dan informatif.</div>
        </div>

        <!-- Upload Gambar -->
        <div class="form-group">
            <label for="gambarInput" class="form-label">
                Gambar Thumbnail / Screenshot Karya <?= $isEdit ? '(Opsional jika tidak diganti)' : '<span class="required">*</span>' ?>
            </label>
            <input type="file" name="gambar" id="gambarInput" class="form-control" accept="image/png, image/jpeg, image/webp, image/svg+xml" <?= $isEdit ? '' : '' ?>>
            <div class="form-hint">Format yang didukung: JPG, PNG, WEBP, SVG. Maksimal ukuran berkas: 3 MB.</div>

            <!-- Preview Frame -->
            <div style="margin-top: 0.85rem;">
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--color-text-muted); display: block; margin-bottom: 0.35rem;">
                    Preview Gambar:
                </span>
                <div class="image-preview-wrapper">
                    <?php 
                    $currentImg = (!empty($project['gambar'])) ? 'uploads/projects/' . $project['gambar'] : 'uploads/projects/default-project.svg';
                    ?>
                    <img id="imagePreview" src="<?= e($currentImg) ?>" alt="Preview Gambar Project">
                </div>
            </div>
        </div>

        <!-- Link Pendukung -->
        <div class="form-group">
            <label for="linkProject" class="form-label">
                Link Pendukung (GitHub / Repositori / Dokumentasi / Figma) <span class="required">*</span>
            </label>
            <div style="position: relative;">
                <input type="url" name="link_project" id="linkProject" class="form-control" value="<?= e($project['link_project'] ?? '') ?>" placeholder="https://github.com/username/project-kamu" required>
            </div>
            <div class="form-hint">Tautan ke repositori kode sumber atau berkas dokumentasi proyek.</div>
        </div>

        <!-- Demo (Opsional) -->
        <div class="form-group">
            <label for="demoUrl" class="form-label">
                Link Live Demo <span style="font-weight: normal; color: var(--color-text-muted); font-size: 0.8rem;">(Opsional)</span>
            </label>
            <input type="url" name="demo" id="demoUrl" class="form-control" value="<?= e($project['demo'] ?? '') ?>" placeholder="https://demo-project-kamu.vercel.app">
            <div class="form-hint">Tautan jika website project Anda sudah di-deploy dan dapat dicoba secara online. Kosongkan jika belum tersedia.</div>
        </div>

        <!-- Tombol Aksi Form -->
        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--color-border);">
            <a href="dashboard.php" class="btn btn-outline">
                <i class="fa-solid fa-xmark"></i> Batal
            </a>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-floppy-disk"></i> <?= $isEdit ? 'Simpan Perubahan' : 'Publikasikan Project' ?>
            </button>
        </div>
    </form>
</div>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
