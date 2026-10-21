<?php
// ============================================================================
// index.php - Katalog Etalase Digital Publik
// ============================================================================
require_once dirname(__DIR__, 2) . '/config/config.php';

$pageTitle = 'Katalog Karya Siswa';
$activePage = 'home';

// Pencarian
$q = trim($_GET['q'] ?? '');

if ($q !== '') {
    $stmt = $pdo->prepare("SELECT p.*, u.nama AS nama_pembuat, u.email AS email_pembuat, u.role AS role_pembuat 
        FROM projects p 
        JOIN users u ON u.id = p.user_id 
        WHERE p.nama_project LIKE :q1 OR p.deskripsi LIKE :q2 OR u.nama LIKE :q3 
        ORDER BY p.created_at DESC");
    $param = "%{$q}%";
    $stmt->execute(['q1' => $param, 'q2' => $param, 'q3' => $param]);
    $projects = $stmt->fetchAll();
} else {
    $projects = $pdo->query("SELECT p.*, u.nama AS nama_pembuat, u.email AS email_pembuat, u.role AS role_pembuat 
        FROM projects p 
        JOIN users u ON u.id = p.user_id 
        ORDER BY p.created_at DESC")->fetchAll();
}

// Statistik Singkat
$totalProjects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'siswa'")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-glow"></div>
    <div class="hero-glow-2"></div>
    <div class="container hero-body">
        <div class="hero-badge">
            <i class="fa-solid fa-sparkles"></i>
            Proyek UKK Kelas XI &bull; Etalase Digital
        </div>
        <h1 class="hero-title">
            Eksplorasi Inovasi &amp; <span>Karya Digital Siswa</span>
        </h1>
        <p class="hero-desc">
            Etalase pameran karya digital SMK Telkom Lampung. Menyajikan portofolio aplikasi, website, dan proyek kejuruan RPL dengan manajemen hak akses terintegrasi.
        </p>

        <!-- Identitas Pribadi Pembuat -->
        <div class="hero-author-card">
            <div class="hero-author-avatar">
                AF
            </div>
            <div class="hero-author-info">
                <div class="hero-author-name">Ahmad Fadhil Anwar</div>
                <div class="hero-author-school">SMK Telkom Lampung &bull; XI RPL</div>
            </div>
            <span class="badge badge-siswa ms-2">Pengembang</span>
        </div>
    </div>
</section>

<!-- Stats Counter Bar -->
<div class="stats-bar">
    <div class="stat-item">
        <div class="stat-icon red">
            <i class="fa-solid fa-cubes"></i>
        </div>
        <div>
            <div class="stat-value"><?= (int)$totalProjects ?></div>
            <div class="stat-label">Total Karya Project</div>
        </div>
    </div>
    <div class="stat-item">
        <div class="stat-icon emerald">
            <i class="fa-solid fa-user-graduate"></i>
        </div>
        <div>
            <div class="stat-value"><?= (int)$totalStudents ?></div>
            <div class="stat-label">Siswa Kontributor</div>
        </div>
    </div>
    <div class="stat-item">
        <div class="stat-icon indigo">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <div class="stat-value"><?= (int)$totalUsers ?></div>
            <div class="stat-label">Pengguna Terdaftar</div>
        </div>
    </div>
    <div class="stat-item">
        <div class="stat-icon amber">
            <i class="fa-solid fa-shield-check"></i>
        </div>
        <div>
            <div class="stat-value">3 Level</div>
            <div class="stat-label">Hak Akses Role</div>
        </div>
    </div>
</div>

<div style="height: 2rem;"></div>

<!-- Filter & Toolbar -->
<div class="filter-toolbar">
    <form action="index.php" method="GET" class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="q" id="clientSearchInput" value="<?= e($q) ?>" class="search-input" placeholder="Cari karya project atau nama siswa...">
    </form>

    <div style="display: flex; gap: 0.5rem; align-items: center;">
        <?php if ($q !== ''): ?>
            <a href="index.php" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-xmark"></i> Reset Filter
            </a>
        <?php endif; ?>
        <?php if (is_logged_in()): ?>
            <a href="project_form.php" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Tambah Karya Baru
            </a>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-right-to-bracket"></i> Masuk untuk Tambah Project
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Section Header -->
<div style="margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--color-text-main);">
            Koleksi Karya Siswa
        </h2>
        <p style="font-size: 0.9rem; color: var(--color-text-muted);">
            Menampilkan <?= count($projects) ?> project yang telah dipublikasikan di etalase.
        </p>
    </div>
</div>

<!-- Projects Grid -->
<?php if (empty($projects)): ?>
    <div style="text-align: center; padding: 4rem 1rem; background: var(--color-surface); border: 1px dashed var(--color-border); border-radius: var(--radius-xl);">
        <i class="fa-regular fa-folder-open" style="font-size: 3.5rem; color: var(--color-text-light); margin-bottom: 1rem;"></i>
        <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">Tidak Ada Project Ditemukan</h3>
        <p style="color: var(--color-text-muted); max-width: 450px; margin: 0 auto 1.5rem;">
            <?= $q !== '' ? 'Tidak ditemukan project dengan kata kunci "' . e($q) . '". Coba cari dengan kata kunci lain.' : 'Belum ada karya project yang ditambahkan ke sistem.' ?>
        </p>
        <?php if (is_logged_in()): ?>
            <a href="project_form.php" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Tambah Project Pertama
            </a>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary">
                <i class="fa-solid fa-right-to-bracket"></i> Login untuk Menambahkan
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="projects-grid">
        <?php foreach ($projects as $proj): ?>
            <article class="project-card">
                <!-- Media Thumbnail -->
                <div class="card-media">
                    <img src="uploads/projects/<?= e($proj['gambar']) ?>" alt="<?= e($proj['nama_project']) ?>" loading="lazy" onerror="this.src='uploads/projects/default-project.svg'">
                    <div class="card-media-overlay">
                        <?php if (!empty($proj['demo'])): ?>
                            <span class="badge badge-demo" title="Live demo tersedia">
                                <i class="fa-solid fa-bolt me-1"></i>Demo
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="card-content">
                    <div class="card-author">
                        <span class="author-chip" title="Pemilik Project">
                            <i class="fa-solid fa-user-circle"></i>
                            <?= e($proj['nama_pembuat']) ?>
                        </span>
                        <span style="font-size: 0.75rem; color: var(--color-text-light); margin-left: auto;">
                            <?= date('d M Y', strtotime($proj['created_at'])) ?>
                        </span>
                    </div>

                    <h3 class="card-title">
                        <a href="detail.php?id=<?= $proj['id'] ?>"><?= e($proj['nama_project']) ?></a>
                    </h3>

                    <p class="card-desc">
                        <?= e($proj['deskripsi']) ?>
                    </p>

                    <!-- Links & Actions -->
                    <div class="card-actions">
                        <a href="detail.php?id=<?= $proj['id'] ?>" class="btn btn-primary btn-sm flex-fill" style="flex: 1;">
                            <i class="fa-solid fa-eye"></i> Detail
                        </a>

                        <?php if (!empty($proj['link_project'])): ?>
                            <a href="<?= e($proj['link_project']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm btn-icon" title="Buka Link Pendukung">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                        <?php endif; ?>

                        <?php if (can_manage_project($proj)): ?>
                            <a href="project_form.php?id=<?= $proj['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Edit Project">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="project_action.php?action=delete&id=<?= $proj['id'] ?>" class="btn btn-danger btn-sm btn-icon btn-confirm-delete" data-item-title="<?= e($proj['nama_project']) ?>" title="Hapus Project">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
