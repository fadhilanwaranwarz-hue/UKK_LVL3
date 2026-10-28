<?php
// ============================================================================
// detail.php - Halaman Rincian Detail Project
// Fitur Tambahan Terintegrasi
// Etalase Digital Stella - SMK Telkom Lampung
// ============================================================================

require_once __DIR__ . '/config/kuis_db.php';
require_once __DIR__ . '/middleware/auth_check.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash_set('danger', 'ID Project tidak valid.');
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT p.*, u.nama AS nama_pembuat, u.email AS email_pembuat, u.role AS role_pembuat 
                       FROM projects p 
                       JOIN users u ON u.id = p.user_id 
                       WHERE p.id = :id");
$stmt->execute(['id' => $id]);
$project = $stmt->fetch();

if (!$project) {
    flash_set('danger', 'Project yang Anda cari tidak ditemukan atau telah dihapus.');
    header('Location: index.php');
    exit;
}

$pageTitle = $project['nama_project'] . ' - Detail Karya';
$activePage = 'detail';
$pageCss = 'main.css';

require __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumbs Navigation -->
<div style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.88rem; color: var(--color-text-muted);">
    <a href="index.php" style="color: var(--color-text-muted);"><i class="fa-solid fa-house"></i> Beranda</a>
    <span>/</span>
    <span style="color: var(--color-primary); font-weight: 600;"><?= e($project['nama_project']) ?></span>
</div>

<div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-xl); overflow: hidden; box-shadow: var(--shadow-md); margin-bottom: 3rem;">
    <!-- Showcase Banner Image -->
    <div style="width: 100%; max-height: 440px; overflow: hidden; background: #f1f5f9; position: relative;">
        <img src="uploads/projects/<?= e($project['gambar']) ?>" alt="<?= e($project['nama_project']) ?>" 
             style="width: 100%; height: 100%; object-fit: contain; max-height: 440px; display: block;"
             onerror="this.src='uploads/projects/default-project.svg'">
    </div>

    <!-- Content Details -->
    <div style="padding: 2.25rem;">
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span class="badge badge-secondary" style="font-size: 0.9rem; padding: 0.4rem 0.9rem;">
                    <i class="fa-solid fa-graduation-cap text-primary"></i>
                    Karya: <strong><?= e($project['nama_pembuat']) ?></strong>
                </span>
                <?= role_badge($project['role_pembuat']) ?>
            </div>

            <div style="font-size: 0.85rem; color: var(--color-text-light);">
                <i class="fa-regular fa-clock me-1"></i>
                Dipublikasikan: <?= date('d F Y', strtotime($project['created_at'])) ?>
            </div>
        </div>

        <h1 style="font-size: 2.2rem; font-weight: 800; line-height: 1.25; margin-bottom: 1.5rem; color: var(--color-text-main);">
            <?= e($project['nama_project']) ?>
        </h1>

        <!-- Deskripsi Proyek -->
        <div style="background: var(--color-surface-muted); padding: 1.75rem; border-radius: var(--radius-lg); margin-bottom: 2rem; border-left: 4px solid var(--color-primary);">
            <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--color-text-main);">
                <i class="fa-solid fa-file-lines text-primary" style="color: var(--color-primary); margin-right: 0.35rem;"></i> Deskripsi Proyek
            </h3>
            <div style="color: var(--color-text-main); font-size: 1rem; line-height: 1.8; white-space: pre-line;">
                <?= e($project['deskripsi']) ?>
            </div>
        </div>

        <!-- Action Links & Buttons -->
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; padding-top: 1.5rem; border-top: 1px solid var(--color-border);">
            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                <?php if (!empty($project['link_project'])): ?>
                    <a href="<?= e($project['link_project']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Buka Link Repository / Dokumen
                    </a>
                <?php endif; ?>

                <?php if (!empty($project['demo'])): ?>
                    <a href="<?= e($project['demo']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-indigo">
                        <i class="fa-solid fa-play"></i> Buka Live Demo
                    </a>
                <?php endif; ?>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <?php if (can_manage_project($project)): ?>
                    <a href="project_edit.php?id=<?= (int)$project['id'] ?>" class="btn btn-outline">
                        <i class="fa-solid fa-pen-to-square"></i> Edit
                    </a>
                    <a href="project_hapus.php?id=<?= (int)$project['id'] ?>" class="btn btn-danger"
                       onclick="return confirm('Apakah Anda yakin ingin menghapus project ini?');">
                        <i class="fa-solid fa-trash"></i> Hapus
                    </a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-outline">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke Etalase
                </a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
