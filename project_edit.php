<?php
// ============================================================================
// project_edit.php - Form Edit Project
// Sesuai Ketentuan Rubrik Penilaian
// Etalase Digital Stella - SMK Telkom Lampung
// ============================================================================

require_once __DIR__ . '/config/kuis_db.php';
require_once __DIR__ . '/middleware/auth_check.php';

// Wajib Login
auth_check('auth/login.php');

$currentUser = current_user();
$currentRole = current_role();
$currentUserId = current_user_id();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    flash_set('danger', 'ID Project tidak valid.');
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT p.*, u.nama AS nama_pemilik, u.email AS email_pemilik 
                       FROM projects p 
                       JOIN users u ON u.id = p.user_id 
                       WHERE p.id = :id");
$stmt->execute(['id' => $id]);
$project = $stmt->fetch();

if (!$project) {
    flash_set('danger', 'Project tidak ditemukan dalam basis data.');
    header('Location: index.php');
    exit;
}

// Cek Hak Akses Edit: Siswa hanya boleh edit milik sendiri!
if (!can_manage_project($project)) {
    flash_set('danger', 'Akses ditolak! Anda tidak memiliki wewenang untuk mengedit project milik pengguna lain.');
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaProject = trim($_POST['nama_project'] ?? '');
    $deskripsi   = trim($_POST['deskripsi'] ?? '');
    $linkProject = trim($_POST['link_project'] ?? '');
    $demo        = trim($_POST['demo'] ?? '');
    $targetUserId = $project['user_id'];

    if (($currentRole === 'super_admin' || $currentRole === 'admin') && !empty($_POST['user_id'])) {
        $targetUserId = (int)$_POST['user_id'];
    }

    if (empty($namaProject) || empty($deskripsi) || empty($linkProject)) {
        $error = 'Harap isi semua kolom wajib (Nama Project, Deskripsi, dan Link Project)!';
    } else {
        $newFilename = $project['gambar'];
        $uploadSuccess = true;

        // Cek jika ada pergantian berkas gambar
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['gambar'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $error = 'Gagal mengunggah berkas gambar (Error code: ' . $file['error'] . ')';
                $uploadSuccess = false;
            } elseif ($file['size'] > 3 * 1024 * 1024) {
                $error = 'Ukuran file terlalu besar! Maksimal 3 MB.';
                $uploadSuccess = false;
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
                if (!in_array($ext, $allowed, true)) {
                    $error = 'Format file tidak didukung! Gunakan format JPG, PNG, WEBP, atau SVG.';
                    $uploadSuccess = false;
                } else {
                    $uploadDir = __DIR__ . '/uploads/projects/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $uploadedName = 'proj_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $uploadedName)) {
                        // Hapus file gambar lama jika bukan aset bawaan
                        $defaultImages = ['default-project.svg', 'kantinku-preview.svg', 'sim-alumni.svg', 'stella-library.svg'];
                        if (!empty($project['gambar']) && !in_array($project['gambar'], $defaultImages, true)) {
                            $oldPath = $uploadDir . $project['gambar'];
                            if (is_file($oldPath)) {
                                @unlink($oldPath);
                            }
                        }
                        $newFilename = $uploadedName;
                    } else {
                        $error = 'Gagal memindahkan file ke folder uploads.';
                        $uploadSuccess = false;
                    }
                }
            }
        }

        if ($uploadSuccess && empty($error)) {
            $update = $pdo->prepare("UPDATE projects SET 
                                        user_id = :uid,
                                        nama_project = :nama, 
                                        deskripsi = :deskripsi, 
                                        gambar = :gambar, 
                                        link_project = :link, 
                                        demo = :demo 
                                     WHERE id = :id");
            $update->execute([
                'uid'       => $targetUserId,
                'nama'      => $namaProject,
                'deskripsi' => $deskripsi,
                'gambar'    => $newFilename,
                'link'      => $linkProject,
                'demo'      => !empty($demo) ? $demo : null,
                'id'        => $id
            ]);

            flash_set('success', 'Project "<strong>' . e($namaProject) . '</strong>" berhasil diperbarui!');
            
            if ($currentRole === 'super_admin' || $currentRole === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }
            exit;
        }
    }
}

// Ambil list siswa untuk re-assign jika Admin / Super Admin
$students = [];
if ($currentRole === 'super_admin' || $currentRole === 'admin') {
    $students = $pdo->query("SELECT id, nama, email FROM users WHERE role = 'siswa' ORDER BY nama ASC")->fetchAll();
}

$pageTitle = 'Edit Project: ' . $project['nama_project'];
$activePage = 'edit_project';
$pageCss = 'form.css';

require __DIR__ . '/includes/header.php';
?>

<div class="form-card">
    <div class="form-card-header">
        <div class="brand-icon" style="margin: 0 auto 1rem; width: 48px; height: 48px; font-size: 1.4rem;">
            <i class="fa-solid fa-pen-to-square"></i>
        </div>
        <h1 style="text-align: center;">Edit Data Project</h1>
        <p style="text-align: center;">Perbarui detail informasi karya portofolio digital Anda</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div><?= e($error) ?></div>
        </div>
    <?php endif; ?>

    <form action="project_edit.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data" class="form-grid">
        <input type="hidden" name="id" value="<?= $id ?>">

        <?php if ($currentRole === 'super_admin' || $currentRole === 'admin'): ?>
            <div class="form-group">
                <label for="user_id" class="form-label">
                    Pemilik Project (Siswa) <span class="required">*</span>
                </label>
                <select name="user_id" id="user_id" class="form-control" required>
                    <option value="<?= (int)$project['user_id'] ?>">
                        -- Saat Ini: <?= e($project['nama_pemilik']) ?> (<?= e($project['email_pemilik']) ?>) --
                    </option>
                    <optgroup label="Ubah ke Akun Siswa Lain">
                        <?php foreach ($students as $stu): ?>
                            <option value="<?= (int)$stu['id'] ?>" <?= ((int)$project['user_id'] === (int)$stu['id']) ? 'selected' : '' ?>>
                                <?= e($stu['nama']) ?> (<?= e($stu['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="nama_project" class="form-label">
                Nama / Judul Project <span class="required">*</span>
            </label>
            <input type="text" name="nama_project" id="nama_project" class="form-control" 
                   required value="<?= e($_POST['nama_project'] ?? $project['nama_project']) ?>">
        </div>

        <div class="form-group">
            <label for="deskripsi" class="form-label">
                Deskripsi Lengkap Project <span class="required">*</span>
            </label>
            <textarea name="deskripsi" id="deskripsi" class="form-control" rows="4" 
                      required><?= e($_POST['deskripsi'] ?? $project['deskripsi']) ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="link_project" class="form-label">
                    Link Repository / Dokumentasi <span class="required">*</span>
                </label>
                <input type="url" name="link_project" id="link_project" class="form-control" 
                       required value="<?= e($_POST['link_project'] ?? $project['link_project']) ?>">
            </div>

            <div class="form-group">
                <label for="demo" class="form-label">Link Demo Langsung</label>
                <input type="url" name="demo" id="demo" class="form-control" 
                       value="<?= e($_POST['demo'] ?? $project['demo']) ?>">
            </div>
        </div>

        <!-- Gambar Saat Ini & Form Penggantian dengan JS Preview -->
        <div class="form-group">
            <label class="form-label">Gambar Project</label>
            
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem; padding: 0.75rem; background: var(--color-surface-muted); border: 1px solid var(--color-border); border-radius: var(--radius-md);">
                <div style="width: 80px; height: 50px; border-radius: var(--radius-sm); overflow: hidden; background: #fff; border: 1px solid var(--color-border); flex: 0 0 80px;">
                    <img src="uploads/projects/<?= e($project['gambar']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div style="font-size: 0.85rem; color: var(--color-text-muted);">
                    Gambar aktif saat ini: <strong><?= e($project['gambar']) ?></strong><br>
                    <small>Pilih gambar baru di bawah jika ingin mengganti.</small>
                </div>
            </div>

            <div class="upload-dropzone" id="dropzoneArea">
                <div class="upload-icon">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                </div>
                <div style="font-weight: 700; color: var(--color-text-main); margin-bottom: 0.25rem;">
                    Pilih File Gambar Baru (Opsional)
                </div>
                <div class="form-hint">Format didukung: JPG, PNG, WEBP, SVG (Maksimal 3 MB)</div>
                <input type="file" name="gambar" id="projectImageInput" accept="image/*">
            </div>

            <!-- Real-time JS Preview Box untuk Berkas Baru -->
            <div class="image-preview-wrapper" id="previewBox">
                <div class="preview-thumb-box">
                    <img src="" alt="Pratinjau Baru" id="previewImgElement" class="preview-thumb-img">
                </div>
                <div class="preview-meta">
                    <div class="preview-filename" id="previewFileName">filename.png</div>
                    <div class="preview-filesize" id="previewFileSize">0 KB</div>
                    <div class="preview-badge-status">
                        <i class="fa-solid fa-arrows-rotate"></i> Gambar baru siap menggantikan gambar lama
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= ($currentRole === 'super_admin' || $currentRole === 'admin') ? 'admin/index.php' : 'index.php' ?>" class="btn btn-outline">
                Batal
            </a>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan Project
            </button>
        </div>
    </form>
</div>

<!-- JavaScript Real-time Image Preview Engine -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('projectImageInput');
    const previewBox = document.getElementById('previewBox');
    const previewImg = document.getElementById('previewImgElement');
    const previewFileName = document.getElementById('previewFileName');
    const previewFileSize = document.getElementById('previewFileSize');

    if (fileInput) {
        fileInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                alert('File yang dipilih harus berupa gambar!');
                fileInput.value = '';
                previewBox.classList.remove('active');
                return;
            }

            if (file.size > 3 * 1024 * 1024) {
                alert('Ukuran file maksimal 3 MB!');
                fileInput.value = '';
                previewBox.classList.remove('active');
                return;
            }

            previewFileName.textContent = file.name;
            previewFileSize.textContent = (file.size / 1024).toFixed(1) + ' KB';

            const reader = new FileReader();
            reader.onload = function (event) {
                previewImg.src = event.target.result;
                previewBox.classList.add('active');
            };
            reader.readAsDataURL(file);
        });
    }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
