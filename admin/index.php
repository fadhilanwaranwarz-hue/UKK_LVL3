<?php
// ============================================================================
// admin/index.php - Dashboard Admin / Super Admin
// Etalase Digital Stella - SMK Telkom Lampung
// ============================================================================

require_once __DIR__ . '/../config/kuis_db.php';
require_once __DIR__ . '/../middleware/auth_check.php';

// Proteksi Hak Akses: HANYA Admin dan Super Admin
require_role(['super_admin', 'admin'], '../index.php');

$currentUser = current_user();
$currentRole = current_role();

// Pencarian Project di Dashboard
$q = trim($_GET['q'] ?? '');

$sql = "SELECT p.*, u.nama AS nama_pembuat, u.email AS email_pembuat, u.role AS role_pembuat 
        FROM projects p 
        JOIN users u ON u.id = p.user_id";
$params = [];

if ($q !== '') {
    $sql .= " WHERE p.nama_project LIKE :q1 OR p.deskripsi LIKE :q2 OR u.nama LIKE :q3";
    $like = "%{$q}%";
    $params['q1'] = $like;
    $params['q2'] = $like;
    $params['q3'] = $like;
}

$sql .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();

// Statistik
$totalProjects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$totalSiswa    = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'siswa'")->fetchColumn();
$totalAdmin    = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$totalSuper    = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'super_admin'")->fetchColumn();

$pageTitle = 'Dashboard Admin & Super Admin';
$activePage = 'dashboard';
$pageCss = 'admin.css';

require __DIR__ . '/../includes/header.php';
?>

<!-- Header Toolbar -->
<div class="admin-toolbar">
    <div class="admin-title-area">
        <h1>Dashboard Manajemen Etalase</h1>
        <p>
            Selamat datang, <strong><?= e($currentUser['nama']) ?></strong> (<?= role_badge($currentRole) ?>). 
            Panel kendali karya digital SMK Telkom Lampung.
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <a href="../project_tambah.php" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> Tambah Project
        </a>
        <?php if ($currentRole === 'super_admin'): ?>
            <a href="users.php" class="btn btn-indigo btn-sm">
                <i class="fa-solid fa-users-gear"></i> Kelola Seluruh User
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Admin Metrics Stat Cards -->
<div class="admin-stats-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-icon red">
            <i class="fa-solid fa-layer-group"></i>
        </div>
        <div>
            <div class="admin-stat-val"><?= (int)$totalProjects ?></div>
            <div class="admin-stat-lbl">Total Proyek Karya</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon green">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div>
            <div class="admin-stat-val"><?= (int)$totalSiswa ?></div>
            <div class="admin-stat-lbl">Akun Siswa Terdaftar</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon indigo">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <div>
            <div class="admin-stat-val"><?= (int)$totalAdmin ?></div>
            <div class="admin-stat-lbl">Administrator</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon amber">
            <i class="fa-solid fa-crown"></i>
        </div>
        <div>
            <div class="admin-stat-val"><?= (int)$totalSuper ?></div>
            <div class="admin-stat-lbl">Super Admin</div>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="table-card">
    <div class="table-filter-bar">
        <div style="font-weight: 700; font-size: 1.05rem; color: var(--color-text-main); display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-list-check text-primary" style="color: var(--color-primary);"></i>
            Daftar Seluruh Proyek Siswa (<?= count($projects) ?>)
        </div>
        <form method="GET" action="index.php" style="display: flex; gap: 0.5rem;">
            <input type="text" name="q" class="form-control" style="padding: 0.45rem 0.85rem; font-size: 0.88rem; width: 240px;" 
                   placeholder="Cari judul/siswa..." value="<?= e($q) ?>">
            <button type="submit" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-search"></i>
            </button>
            <?php if ($q !== ''): ?>
                <a href="index.php" class="btn btn-outline btn-sm" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th style="width: 100px;">Gambar</th>
                    <th>Nama &amp; Deskripsi Project</th>
                    <th>Pembuat (Siswa)</th>
                    <th>Tautan</th>
                    <th>Tanggal</th>
                    <th style="width: 120px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($projects)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 3rem; color: var(--color-text-muted);">
                            <i class="fa-solid fa-folder-open fa-2x" style="margin-bottom: 0.75rem; display: block; opacity: 0.4;"></i>
                            Tidak ada data project yang ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($projects as $item): ?>
                        <tr>
                            <td style="color: var(--color-text-muted); font-weight: 600;"><?= $no++ ?></td>
                            <td>
                                <div style="width: 72px; height: 48px; border-radius: var(--radius-sm); overflow: hidden; background: #f1f5f9; border: 1px solid var(--color-border);">
                                    <img src="../uploads/projects/<?= e($item['gambar']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--color-text-main); font-size: 0.95rem;">
                                    <?= e($item['nama_project']) ?>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--color-text-muted); max-width: 380px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= e($item['deskripsi']) ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600;"><?= e($item['nama_pembuat']) ?></div>
                                <div style="font-size: 0.75rem;"><?= role_badge($item['role_pembuat']) ?></div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.35rem;">
                                    <?php if (!empty($item['link_project'])): ?>
                                        <a href="<?= e($item['link_project']) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm" title="Repo / Dokumen">
                                            <i class="fa-brands fa-github"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($item['demo'])): ?>
                                        <a href="<?= e($item['demo']) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm" title="Live Demo">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="font-size: 0.82rem; color: var(--color-text-muted); white-space: nowrap;">
                                <?= date('d M Y', strtotime($item['created_at'])) ?>
                            </td>
                            <td>
                                <div class="table-actions" style="justify-content: center;">
                                    <a href="../project_edit.php?id=<?= (int)$item['id'] ?>" class="btn-icon-action" title="Edit Data Project">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <a href="../project_hapus.php?id=<?= (int)$item['id'] ?>" class="btn-icon-action delete" 
                                       title="Hapus Project & Gambar" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus project \'<?= addslashes($item['nama_project']) ?>\' beserta filenya?');">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
