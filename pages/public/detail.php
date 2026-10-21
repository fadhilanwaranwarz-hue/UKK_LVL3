<?php
// ============================================================================
// detail.php - Halaman Rincian Detail Project
// ============================================================================
require_once dirname(__DIR__, 2) . '/config/config.php';

$id = (int)($_GET['id'] ?? 0);

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

$pageTitle = $project['nama_project'] . ' - Detail Project';
$activePage = 'detail';

require dirname(__DIR__, 2) . '/includes/header.php';
?>

<!-- Breadcrumbs Navigation -->
<div style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.88rem; color: var(--color-text-muted);">
    <a href="index.php" style="color: var(--color-text-muted);"><i class="fa-solid fa-house"></i> Beranda</a>
    <span>/</span>
    <a href="index.php" style="color: var(--color-text-muted);">Etalase Karya</a>
    <span>/</span>
    <span style="color: var(--color-primary); font-weight: 600;"><?= e($project['nama_project']) ?></span>
</div>

<div class="detail-card">
    <!-- Showcase Banner Image -->
    <div class="detail-cover">
        <img src="uploads/projects/<?= e($project['gambar']) ?>" alt="<?= e($project['nama_project']) ?>" onerror="this.src='uploads/projects/default-project.svg'">
    </div>

    <!-- Content Details -->
    <div class="detail-body">
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span class="author-chip" style="font-size: 0.95rem; padding: 0.35rem 0.9rem;">
                    <i class="fa-solid fa-graduation-cap"></i>
                    Karya: <?= e($project['nama_pembuat']) ?>
                </span>
                <?= role_badge($project['role_pembuat']) ?>
            </div>

            <div style="font-size: 0.85rem; color: var(--color-text-light);">
                <i class="fa-regular fa-clock me-1"></i>
                Diupload pada: <?= date('d F Y, H:i', strtotime($project['created_at'])) ?> WIB
            </div>
        </div>

        <h1 style="font-size: 2.25rem; font-weight: 800; line-height: 1.25; margin-bottom: 1.5rem; color: var(--color-text-main);">
            <?= e($project['nama_project']) ?>
        </h1>

        <!-- Deskripsi Proyek -->
        <div style="background: var(--color-surface-muted); padding: 1.75rem; border-radius: var(--radius-lg); margin-bottom: 2rem; border-left: 4px solid var(--color-primary);">
            <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--color-text-main);">
                <i class="fa-solid fa-file-lines me-2 text-primary"></i>Deskripsi Proyek
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
                        <i class="fa-solid fa-link"></i> Buka Link Pendukung
                    </a>
                <?php endif; ?>

                <?php if (!empty($project['demo'])): ?>
                    <a href="<?= e($project['demo']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-indigo">
                        <i class="fa-solid fa-play"></i> Kunjungi Live Demo
                    </a>
                <?php endif; ?>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <?php if (can_manage_project($project)): ?>
                    <a href="project_form.php?id=<?= $project['id'] ?>" class="btn btn-outline">
                        <i class="fa-solid fa-pen-to-square"></i> Edit
                    </a>
                    <a href="project_action.php?action=delete&id=<?= $project['id'] ?>" class="btn btn-danger btn-confirm-delete" data-item-title="<?= e($project['nama_project']) ?>">
                        <i class="fa-solid fa-trash"></i> Hapus
                    </a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-outline">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
