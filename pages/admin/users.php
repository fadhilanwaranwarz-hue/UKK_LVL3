<?php
// ============================================================================
// users.php - Manajemen Pengguna & Siswa (Admin & Super Admin)
// ============================================================================
require_once dirname(__DIR__, 2) . '/config/config.php';
role_required(['super_admin', 'admin']);

$currentRole = current_role();
$currentUserId = current_user_id();

$pageTitle = ($currentRole === 'admin') ? 'Kelola Data Siswa' : 'Kelola Seluruh Pengguna';
$activePage = 'users';

$q = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? '';

// Build Query
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM projects p WHERE p.user_id = u.id) AS total_project 
        FROM users u WHERE 1=1";
$params = [];

// Jika admin biasa, HANYA boleh melihat dan mengelola role 'siswa'
if ($currentRole === 'admin') {
    $sql .= " AND u.role = 'siswa'";
} elseif (!empty($roleFilter) && in_array($roleFilter, ['super_admin', 'admin', 'siswa'], true)) {
    $sql .= " AND u.role = :role";
    $params['role'] = $roleFilter;
}

if ($q !== '') {
    $sql .= " AND (u.nama LIKE :q1 OR u.email LIKE :q2)";
    $like = "%{$q}%";
    $params['q1'] = $like;
    $params['q2'] = $like;
}

$sql .= " ORDER BY u.role ASC, u.nama ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Hitung Statistik
$countSiswa = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'siswa'")->fetchColumn();
$countAdmin = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$countSuper = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'super_admin'")->fetchColumn();

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<!-- Header Toolbar -->
<div style="margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--color-text-main); margin-bottom: 0.25rem;">
            <?= e($pageTitle) ?>
        </h1>
        <p style="font-size: 0.92rem; color: var(--color-text-muted);">
            <?= $currentRole === 'admin' ? 'Kelola akun siswa SMK Telkom Lampung untuk keperluan upload dan pameran karya.' : 'Kelola seluruh tingkatan hak akses (Super Admin, Admin, dan Siswa).' ?>
        </p>
    </div>

    <a href="user_form.php" class="btn btn-primary">
        <i class="fa-solid fa-user-plus"></i> Tambah <?= $currentRole === 'admin' ? 'Siswa' : 'Pengguna' ?> Baru
    </a>
</div>

<!-- Table Container -->
<div class="table-card">
    <div class="table-header">
        <!-- Filter Tabs (Hanya untuk Super Admin) -->
        <?php if ($currentRole === 'super_admin'): ?>
            <div style="display: flex; gap: 0.4rem; align-items: center; flex-wrap: wrap;">
                <a href="users.php<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>" class="btn <?= empty($roleFilter) ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                    Semua (<?= $countSiswa + $countAdmin + $countSuper ?>)
                </a>
                <a href="users.php?role=siswa<?= $q !== '' ? '&q=' . urlencode($q) : '' ?>" class="btn <?= $roleFilter === 'siswa' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                    Siswa (<?= (int)$countSiswa ?>)
                </a>
                <a href="users.php?role=admin<?= $q !== '' ? '&q=' . urlencode($q) : '' ?>" class="btn <?= $roleFilter === 'admin' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                    Admin (<?= (int)$countAdmin ?>)
                </a>
                <a href="users.php?role=super_admin<?= $q !== '' ? '&q=' . urlencode($q) : '' ?>" class="btn <?= $roleFilter === 'super_admin' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                    Super Admin (<?= (int)$countSuper ?>)
                </a>
            </div>
        <?php else: ?>
            <div style="font-weight: 700; color: var(--color-text-main);">
                <i class="fa-solid fa-user-graduate me-1 text-primary"></i> Daftar Siswa (<?= (int)$countSiswa ?> Siswa)
            </div>
        <?php endif; ?>

        <!-- Search Bar -->
        <form action="users.php" method="GET" style="display: flex; gap: 0.5rem; min-width: 260px;">
            <?php if (!empty($roleFilter)): ?>
                <input type="hidden" name="role" value="<?= e($roleFilter) ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= e($q) ?>" class="form-control" style="padding: 0.45rem 0.85rem; font-size: 0.88rem;" placeholder="Cari nama atau email...">
            <button type="submit" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-search"></i>
            </button>
            <?php if ($q !== ''): ?>
                <a href="users.php<?= !empty($roleFilter) ? '?role=' . urlencode($roleFilter) : '' ?>" class="btn btn-outline btn-sm" title="Reset">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- User Table -->
    <?php if (empty($users)): ?>
        <div style="text-align: center; padding: 3.5rem 1rem;">
            <i class="fa-solid fa-users-slash" style="font-size: 3rem; color: var(--color-text-light); margin-bottom: 0.75rem;"></i>
            <h4 style="font-size: 1.15rem; font-weight: 700;">Tidak Ada Pengguna Ditemukan</h4>
            <p style="color: var(--color-text-muted); font-size: 0.9rem; margin-bottom: 1.25rem;">
                Coba sesuaikan kata kunci pencarian atau tambahkan pengguna baru.
            </p>
            <a href="user_form.php" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-user-plus"></i> Tambah Pengguna
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Nama Lengkap</th>
                        <th>Alamat Email</th>
                        <th>Hak Akses (Role)</th>
                        <th>Jumlah Project</th>
                        <th style="text-align: right; width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($users as $u): ?>
                        <tr>
                            <td style="color: var(--color-text-light); font-weight: 600;"><?= $no++ ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div class="user-avatar" style="width: 34px; height: 34px; font-size: 0.85rem;">
                                        <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <strong style="color: var(--color-text-main); display: block; font-size: 0.95rem;">
                                            <?= e($u['nama']) ?>
                                        </strong>
                                        <?php if ((int)$u['id'] === (int)$currentUserId): ?>
                                            <span style="font-size: 0.72rem; color: var(--color-primary); font-weight: 700;">(Akun Anda)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <code style="font-family: inherit; font-size: 0.88rem; color: var(--color-text-muted);">
                                    <?= e($u['email']) ?>
                                </code>
                            </td>
                            <td>
                                <?= role_badge($u['role']) ?>
                            </td>
                            <td>
                                <span class="badge" style="background: var(--color-surface-muted); color: var(--color-text-main); border: 1px solid var(--color-border);">
                                    <i class="fa-solid fa-cubes me-1 text-primary"></i> <?= (int)$u['total_project'] ?> Project
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.35rem;">
                                    <!-- Edit User -->
                                    <a href="user_form.php?id=<?= $u['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Edit Pengguna">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>

                                    <!-- Hapus User (Proteksi tidak bisa hapus diri sendiri) -->
                                    <?php if ((int)$u['id'] !== (int)$currentUserId): ?>
                                        <a href="user_action.php?action=delete&id=<?= $u['id'] ?>" class="btn btn-danger btn-sm btn-icon btn-confirm-delete" data-item-title="<?= e($u['nama']) ?>" title="Hapus Pengguna">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-outline btn-sm btn-icon" disabled style="opacity: 0.4; cursor: not-allowed;" title="Tidak dapat menghapus akun yang sedang aktif digunakan">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
