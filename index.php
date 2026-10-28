<?php
// ============================================================================
// index.php - Halaman Utama (Etalase Karya Siswa)
// Sesuai Ketentuan Spesifikasi Tugas
// Etalase Digital Stella - SMK Telkom Lampung
// Pembuat: Ahmad Fadhil Anwar (Kelas XI RPL)
// ============================================================================

require_once __DIR__ . '/config/kuis_db.php';
require_once __DIR__ . '/middleware/auth_check.php';

$currentUser = current_user();
$currentRole = current_role();
$currentUserId = current_user_id();

// Parameter Pencarian
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

// Statistik Ringkas
$totalProjects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'siswa'")->fetchColumn();
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

$pageTitle = 'Etalase Karya Digital Siswa';
$activePage = 'home';
$pageCss = 'main.css';

require __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-glow"></div>
    <div class="hero-panel">
        <div class="container hero-body">
            <div class="hero-badge">
                <i class="fa-solid fa-sparkles"></i>
                Tugas UKK Pemrograman Web &bull; Etalase Digital Stella
            </div>
            <h1 class="hero-title">
                Eksplorasi Inovasi &amp; <span>Karya Digital Siswa</span>
            </h1>
            <p class="hero-desc">
                Pameran proyek digital siswa SMK Telkom Lampung. Portofolio aplikasi, website, dan sistem informasi berbasis PHP PDO Multi-Role.
            </p>

            <!-- Identitas Pribadi Pembuat (Author Card Sesuai LKPD) -->
            <div class="hero-author-card">
                <div class="hero-author-avatar">
                    <img src="assets/images/profile.png" alt="Foto Ahmad Fadhil Anwar">
                </div>
                <div class="hero-author-info">
                    <div class="hero-author-name">Ahmad Fadhil Anwar</div>
                    <div class="hero-author-school">SMK Telkom Lampung &bull; XI Rekayasa Perangkat Lunak</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Counter Bar -->
<div class="stats-bar">
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-number"><?= (int)$totalProjects ?></div>
            <div class="stat-label">Total Karya Terpajang</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?= (int)$totalStudents ?></div>
            <div class="stat-label">Siswa Kontributor</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?= (int)$totalUsers ?></div>
            <div class="stat-label">Pengguna Terdaftar</div>
        </div>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="search-bar-wrap">
    <form action="index.php" method="GET" class="search-form">
        <i class="fa-solid fa-magnifying-glass" style="margin-left: 0.75rem; color: var(--color-text-light); align-self: center;"></i>
        <input type="text" name="q" id="clientSearchInput" class="search-input" 
               placeholder="Cari karya berdasarkan judul, teknologi, atau nama siswa..." 
               value="<?= e($q) ?>">
        <button type="submit" class="btn btn-primary btn-sm">
            Cari Karya
        </button>
        <?php if ($q !== ''): ?>
            <a href="index.php" class="btn btn-outline btn-sm" title="Reset Pencarian">
                <i class="fa-solid fa-xmark"></i>
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Project Cards Grid -->
<div class="projects-grid">
    <?php if (empty($projects)): ?>
        <div style="grid-column: 1 / -1; text-align: center; padding: 4rem 1rem; background: var(--color-surface); border: 1px dashed var(--color-border); border-radius: var(--radius-xl);">
            <div class="brand-icon" style="margin: 0 auto 1.25rem; width: 56px; height: 56px; font-size: 1.5rem; background: var(--color-surface-muted); color: var(--color-text-muted);">
                <i class="fa-solid fa-folder-open"></i>
            </div>
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--color-text-main); margin-bottom: 0.5rem;">
                Belum ada proyek karya yang ditemukan
            </h3>
            <p style="color: var(--color-text-muted); font-size: 0.95rem; margin-bottom: 1.5rem;">
                <?= $q !== '' ? 'Coba ubah kata kunci pencarian Anda.' : 'Jadilah yang pertama menambahkan proyek karya digital ke etalase!' ?>
            </p>
            <?php if ($currentUser): ?>
                <a href="project_tambah.php" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Tambah Proyek Sekarang
                </a>
            <?php else: ?>
                <a href="auth/login.php" class="btn btn-primary">
                    <i class="fa-solid fa-right-to-bracket"></i> Masuk untuk Menambah Karya
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($projects as $project): ?>
            <div class="project-card" id="project-<?= (int)$project['id'] ?>">
                <a href="detail.php?id=<?= (int)$project['id'] ?>" class="card-image-wrap" title="Lihat detail karya">
                    <img src="uploads/projects/<?= e($project['gambar']) ?>" alt="<?= e($project['nama_project']) ?>" class="card-img"
                         onerror="this.src='uploads/projects/default-project.svg'">
                </a>

                <div class="card-body">
                    <h3 class="card-title">
                        <a href="detail.php?id=<?= (int)$project['id'] ?>"><?= e($project['nama_project']) ?></a>
                    </h3>
                    <p class="card-desc"><?= nl2br(e($project['deskripsi'])) ?></p>

                    <div class="card-footer">
                        <div class="author-tag">
                            <div class="author-tag-avatar">
                                <?php if ($project['nama_pembuat'] === 'Ahmad Fadhil Anwar'): ?>
                                    <img src="assets/images/profile.png" alt="Foto Ahmad Fadhil Anwar">
                                <?php else: ?>
                                    <?= e(strtoupper(substr($project['nama_pembuat'], 0, 1))) ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div><?= e($project['nama_pembuat']) ?></div>
                                <div style="font-size: 0.72rem;"><?= role_badge($project['role_pembuat']) ?></div>
                            </div>
                        </div>

                        <div class="project-actions">
                            <?php if (!empty($project['link_project'])): ?>
                                <a href="<?= e($project['link_project']) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm" title="Buka Link Proyek / Repo">
                                    <i class="fa-brands fa-github"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($project['demo'])): ?>
                                <a href="<?= e($project['demo']) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm" title="Live Demo">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Demo
                                </a>
                            <?php endif; ?>

                            <!-- Tombol Edit & Hapus jika memiliki Hak Akses -->
                            <?php if (can_manage_project($project)): ?>
                                <a href="project_edit.php?id=<?= (int)$project['id'] ?>" class="btn btn-outline btn-sm" title="Edit Karya">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="project_hapus.php?id=<?= (int)$project['id'] ?>" class="btn btn-danger btn-sm" title="Hapus Karya"
                                   onclick="return confirm('Hapus karya \'<?= addslashes($project['nama_project']) ?>\'? File gambar juga akan dihapus.');">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
