<?php
// ============================================================================
// dashboard.php - Panel Kontrol Utama Berdasarkan Hak Akses Multi-Role
// ============================================================================
require_once dirname(__DIR__, 2) . '/config/config.php';
login_required();

$currentUser = current_user();
$currentRole = current_role();
$userId = current_user_id();

$pageTitle = 'Dashboard - ' . role_name($currentRole);
$activePage = 'dashboard';

// Filter Tab: 'all' atau 'mine'
$tab = $_GET['tab'] ?? ($currentRole === 'siswa' ? 'mine' : 'all');
$q = trim($_GET['q'] ?? '');

// Statistik
$totalProjects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$myProjectsCount = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE user_id = ?");
$myProjectsCount->execute([$userId]);
$myProjects = $myProjectsCount->fetchColumn();

$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'siswa'")->fetchColumn();
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'super_admin')")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Query Data Project Berdasarkan Tab & Pencarian
$querySql = "SELECT p.*, u.nama AS nama_pembuat, u.email AS email_pembuat, u.role AS role_pembuat 
             FROM projects p 
             JOIN users u ON u.id = p.user_id WHERE 1=1";
$params = [];

if ($tab === 'mine') {
    $querySql .= " AND p.user_id = :userId";
    $params['userId'] = $userId;
}

if ($q !== '') {
    $querySql .= " AND (p.nama_project LIKE :q1 OR p.deskripsi LIKE :q2 OR u.nama LIKE :q3)";
    $paramLike = "%{$q}%";
    $params['q1'] = $paramLike;
    $params['q2'] = $paramLike;
    $params['q3'] = $paramLike;
}

$querySql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($querySql);
$stmt->execute($params);
$projects = $stmt->fetchAll();

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<!-- Header Greeting Panel -->
<div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: 2rem 2.5rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm); display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1.5rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
            <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--color-text-main); margin: 0;">
                Selamat Datang, <?= e($currentUser['nama']) ?>!
            </h1>
            <?= role_badge($currentRole) ?>
        </div>
        <p style="color: var(--color-text-muted); font-size: 0.95rem; margin: 0;">
            <?php if ($currentRole === 'super_admin'): ?>
                Hak akses penuh: Anda dapat mengelola seluruh pengguna, konfigurasi role, serta seluruh project etalase.
            <?php elseif ($currentRole === 'admin'): ?>
                Hak akses administrator: Anda dapat mengelola semua karya project dan mengelola data siswa.
            <?php else: ?>
                Hak akses siswa: Anda dapat mempublikasikan karya project, melihat etalase, serta mengedit &amp; menghapus project milik sendiri.
            <?php endif; ?>
        </p>
    </div>

    <!-- Quick Action Button -->
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <a href="project_form.php" class="btn btn-primary">
            <i class="fa-solid fa-plus-circle"></i> Tambah Project Baru
        </a>
        <?php if ($currentRole === 'super_admin' || $currentRole === 'admin'): ?>
            <a href="users.php" class="btn btn-indigo">
                <i class="fa-solid fa-users-gear"></i> Kelola <?= $currentRole === 'admin' ? 'Data Siswa' : 'Pengguna' ?>
            </a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-outline" target="_blank" title="Buka etalase tampilan pengunjung">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> Lihat Etalase
        </a>
    </div>
</div>

<!-- Metrics Cards Bar -->
<div class="stats-bar" style="margin-top: 0; margin-bottom: 2.5rem;">
    <?php if ($currentRole === 'siswa'): ?>
        <div class="stat-item">
            <div class="stat-icon red">
                <i class="fa-solid fa-laptop-code"></i>
            </div>
            <div>
                <div class="stat-value"><?= (int)$myProjects ?></div>
                <div class="stat-label">Project Milik Saya</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon emerald">
                <i class="fa-solid fa-globe"></i>
            </div>
            <div>
                <div class="stat-value"><?= (int)$totalProjects ?></div>
                <div class="stat-label">Total Project Etalase</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon indigo">
                <i class="fa-solid fa-id-card"></i>
            </div>
            <div>
                <div class="stat-value">Siswa</div>
                <div class="stat-label">Peran Pengguna</div>
            </div>
        </div>
    <?php else: ?>
        <div class="stat-item">
            <div class="stat-icon red">
                <i class="fa-solid fa-folder-open"></i>
            </div>
            <div>
                <div class="stat-value"><?= (int)$totalProjects ?></div>
                <div class="stat-label">Total Semua Project</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon emerald">
                <i class="fa-solid fa-user-graduate"></i>
            </div>
            <div>
                <div class="stat-value"><?= (int)$totalStudents ?></div>
                <div class="stat-label">Total Data Siswa</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon indigo">
                <div class="stat-value"><?= (int)$totalUsers ?></div>
                <div class="stat-label">Total Akun Terdaftar</div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Table Card & Project Management Section -->
<div class="table-card">
    <div class="table-header">
        <!-- Tabs Filter -->
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <a href="dashboard.php?tab=all<?= $q !== '' ? '&q=' . urlencode($q) : '' ?>" class="btn <?= $tab === 'all' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                <i class="fa-solid fa-list-ul"></i> Semua Project (<?= (int)$totalProjects ?>)
            </a>
            <a href="dashboard.php?tab=mine<?= $q !== '' ? '&q=' . urlencode($q) : '' ?>" class="btn <?= $tab === 'mine' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                <i class="fa-solid fa-user-check"></i> Project Saya (<?= (int)$myProjects ?>)
            </a>
        </div>

        <!-- Search Form in Dashboard -->
        <form action="dashboard.php" method="GET" style="display: flex; gap: 0.5rem; min-width: 280px;">
            <input type="hidden" name="tab" value="<?= e($tab) ?>">
            <input type="text" name="q" value="<?= e($q) ?>" class="form-control" style="padding: 0.45rem 0.85rem; font-size: 0.88rem;" placeholder="Cari nama project / pemilik...">
            <button type="submit" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-search"></i>
            </button>
            <?php if ($q !== ''): ?>
                <a href="dashboard.php?tab=<?= e($tab) ?>" class="btn btn-outline btn-sm" title="Hapus pencarian">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table of Projects -->
    <?php if (empty($projects)): ?>
        <div style="text-align: center; padding: 3.5rem 1rem;">
            <i class="fa-solid fa-box-open" style="font-size: 3rem; color: var(--color-text-light); margin-bottom: 0.75rem;"></i>
            <h4 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.35rem;">Tidak Ada Data Project</h4>
            <p style="color: var(--color-text-muted); font-size: 0.9rem; margin-bottom: 1.25rem;">
                <?= $tab === 'mine' ? 'Anda belum menambahkan project karya sendiri.' : 'Tidak ada project yang sesuai dengan filter pencarian.' ?>
            </p>
            <a href="project_form.php" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Tambah Project Sekarang
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Gambar</th>
                        <th>Nama Project</th>
                        <th>Pemilik / Siswa</th>
                        <th>Link &amp; Demo</th>
                        <th>Waktu Buat</th>
                        <th style="text-align: right; width: 160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $p): ?>
                        <?php $isOwner = ((int)$p['user_id'] === (int)$userId); ?>
                        <?php $canManage = can_manage_project($p); ?>
                        <tr>
                            <td>
                                <img src="uploads/projects/<?= e($p['gambar']) ?>" alt="" style="width: 54px; height: 38px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--color-border);" onerror="this.src='uploads/projects/default-project.svg'">
                            </td>
                            <td>
                                <a href="detail.php?id=<?= $p['id'] ?>" style="font-weight: 700; color: var(--color-text-main);">
                                    <?= e($p['nama_project']) ?>
                                </a>
                                <div style="font-size: 0.78rem; color: var(--color-text-muted); max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= e($p['deskripsi']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="author-chip" style="font-size: 0.78rem;">
                                    <i class="fa-solid fa-user-circle"></i> <?= e($p['nama_pembuat']) ?>
                                </span>
                                <?php if ($isOwner): ?>
                                    <span style="font-size: 0.7rem; font-weight: 700; color: var(--color-primary); display: block; margin-top: 0.2rem;">
                                        (Milik Anda)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.4rem; align-items: center;">
                                    <?php if (!empty($p['link_project'])): ?>
                                        <a href="<?= e($p['link_project']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Link Pendukung">
                                            <i class="fa-solid fa-link"></i> Pendukung
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($p['demo'])): ?>
                                        <a href="<?= e($p['demo']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-indigo btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Live Demo">
                                            <i class="fa-solid fa-play"></i> Demo
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="font-size: 0.82rem; color: var(--color-text-muted);">
                                <?= date('d M Y', strtotime($p['created_at'])) ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.35rem;">
                                    <a href="detail.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Lihat Detail">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>

                                    <?php if ($canManage): ?>
                                        <a href="project_form.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Edit Project">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <a href="project_action.php?action=delete&id=<?= $p['id'] ?>" class="btn btn-danger btn-sm btn-icon btn-confirm-delete" data-item-title="<?= e($p['nama_project']) ?>" title="Hapus Project">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-outline btn-sm btn-icon" disabled style="opacity: 0.4; cursor: not-allowed;" title="Hanya pemilik atau Admin yang dapat mengubah project ini">
                                            <i class="fa-solid fa-lock"></i>
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
