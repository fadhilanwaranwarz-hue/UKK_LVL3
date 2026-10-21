<?php
// ============================================================================
// user_form.php - Form Tambah & Edit User / Siswa
// ============================================================================
require_once dirname(__DIR__, 2) . '/config/config.php';
role_required(['super_admin', 'admin']);

$currentRole = current_role();
$currentUserId = current_user_id();

$id = (int)($_GET['id'] ?? 0);
$isEdit = ($id > 0);

$targetUser = null;
if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $targetUser = $stmt->fetch();

    if (!$targetUser) {
        flash_set('danger', 'Pengguna tidak ditemukan.');
        header('Location: users.php');
        exit;
    }

    // Proteksi: Admin biasa HANYA boleh mengedit akun role 'siswa'!
    if ($currentRole === 'admin' && $targetUser['role'] !== 'siswa') {
        flash_set('danger', 'Akses ditolak! Administrator hanya diizinkan mengelola data akun siswa.');
        header('Location: users.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'Edit Pengguna: ' . $targetUser['nama'] : 'Tambah ' . ($currentRole === 'admin' ? 'Siswa' : 'Pengguna') . ' Baru';
$activePage = 'users';

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<div style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.88rem; color: var(--color-text-muted);">
    <a href="users.php" style="color: var(--color-text-muted);"><i class="fa-solid fa-users"></i> Kelola <?= $currentRole === 'admin' ? 'Siswa' : 'Pengguna' ?></a>
    <span>/</span>
    <span style="color: var(--color-primary); font-weight: 600;">
        <?= $isEdit ? 'Edit Pengguna' : 'Tambah Pengguna' ?>
    </span>
</div>

<div class="form-card">
    <div style="margin-bottom: 2rem; text-align: center;">
        <div class="brand-icon" style="margin: 0 auto 1rem; width: 48px; height: 48px; font-size: 1.35rem; background: linear-gradient(135deg, var(--color-indigo), #818cf8);">
            <i class="fa-solid <?= $isEdit ? 'fa-user-pen' : 'fa-user-plus' ?>"></i>
        </div>
        <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--color-text-main); margin-bottom: 0.35rem;">
            <?= $isEdit ? 'Edit Data Pengguna' : 'Tambah Pengguna Baru' ?>
        </h1>
        <p style="color: var(--color-text-muted); font-size: 0.9rem;">
            <?= $currentRole === 'admin' ? 'Kelola akun siswa untuk Etalase Digital Stella' : 'Atur profil pengguna dan tingkat wewenang (Role)' ?>
        </p>
    </div>

    <form action="user_action.php" method="POST">
        <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$targetUser['id'] ?>">
        <?php endif; ?>

        <!-- Nama Lengkap -->
        <div class="form-group">
            <label for="userName" class="form-label">
                Nama Lengkap <span class="required">*</span>
            </label>
            <input type="text" name="nama" id="userName" class="form-control" value="<?= e($targetUser['nama'] ?? '') ?>" placeholder="Nama lengkap siswa atau admin" required autofocus>
        </div>

        <!-- Alamat Email -->
        <div class="form-group">
            <label for="userEmail" class="form-label">
                Alamat Email <span class="required">*</span>
            </label>
            <input type="email" name="email" id="userEmail" class="form-control" value="<?= e($targetUser['email'] ?? '') ?>" placeholder="nama@stella.sch.id" required>
            <div class="form-hint">Email akan digunakan sebagai username untuk login ke dalam sistem.</div>
        </div>

        <!-- Role / Hak Akses -->
        <div class="form-group">
            <label for="userRole" class="form-label">
                Hak Akses (Role) <span class="required">*</span>
            </label>
            <?php if ($currentRole === 'super_admin'): ?>
                <select name="role" id="userRole" class="form-control" required>
                    <?php 
                    $currR = $targetUser['role'] ?? 'siswa';
                    ?>
                    <option value="siswa" <?= $currR === 'siswa' ? 'selected' : '' ?>>Siswa (Kelola project sendiri &amp; lihat karya)</option>
                    <option value="admin" <?= $currR === 'admin' ? 'selected' : '' ?>>Admin (Kelola semua project &amp; kelola siswa)</option>
                    <option value="super_admin" <?= $currR === 'super_admin' ? 'selected' : '' ?>>Super Admin (Hak akses sistem penuh)</option>
                </select>
                <div class="form-hint">Pilih tingkatan hak akses akun ini sesuai wewenang.</div>
            <?php else: ?>
                <!-- Admin biasa hanya bisa membuat/mengedit role Siswa -->
                <input type="hidden" name="role" value="siswa">
                <input type="text" class="form-control" value="Siswa (Siswa SMK Telkom Lampung)" readonly style="background: var(--color-surface-muted); cursor: not-allowed;">
                <div class="form-hint">Administrator hanya berwenang mendaftarkan atau mengedit akun Siswa.</div>
            <?php endif; ?>
        </div>

        <!-- Password -->
        <div class="form-group">
            <label for="userPassword" class="form-label">
                <?= $isEdit ? 'Password Baru' : 'Password' ?> <?= $isEdit ? '<span style="font-weight: normal; color: var(--color-text-muted); font-size: 0.8rem;">(Opsional)</span>' : '<span class="required">*</span>' ?>
            </label>
            <input type="password" name="password" id="userPassword" class="form-control" placeholder="<?= $isEdit ? 'Isi untuk mereset password; kosongkan agar tidak berubah' : 'Minimal 6 karakter' ?>" <?= $isEdit ? '' : 'required' ?>>
            <div class="form-hint"><?= $isEdit ? 'Isi bagian ini untuk mengganti password pengguna. Password minimal 6 karakter.' : 'Gunakan password yang aman minimal 6 karakter.' ?></div>
        </div>

        <!-- Tombol Aksi -->
        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--color-border);">
            <a href="users.php" class="btn btn-outline">
                <i class="fa-solid fa-xmark"></i> Batal
            </a>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-floppy-disk"></i> <?= $isEdit ? 'Simpan Perubahan' : 'Buat Pengguna' ?>
            </button>
        </div>
    </form>
</div>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
