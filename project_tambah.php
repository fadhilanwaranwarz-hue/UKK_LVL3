<?php
// ============================================================================
// project_tambah.php - Form Tambah Project + JS Preview
// Sesuai Ketentuan Rubrik Penilaian
// Etalase Digital Stella - SMK Telkom Lampung
// ============================================================================

require_once __DIR__ . '/config/kuis_db.php';
require_once __DIR__ . '/middleware/auth_check.php';

// Wajib Login untuk Menambah Project
auth_check('auth/login.php');

$currentUser = current_user();
$currentRole = current_role();
$currentUserId = current_user_id();

$error = '';
$namaProject = trim($_POST['nama_project'] ?? '');
$deskripsi   = trim($_POST['deskripsi'] ?? '');
$linkProject = trim($_POST['link_project'] ?? '');
$demo        = trim($_POST['demo'] ?? '');
$targetUserId = $currentUserId;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Jika Admin / Super Admin, bisa memilih pemilik project (siswa)
    if (($currentRole === 'super_admin' || $currentRole === 'admin') && !empty($_POST['user_id'])) {
        $targetUserId = (int)$_POST['user_id'];
    }

    if (empty($namaProject) || empty($deskripsi) || empty($linkProject)) {
        $error = 'Harap lengkapi semua kolom yang wajib diisi (Nama Project, Deskripsi, dan Link Project)!';
    } else {
        // Proses Upload Gambar
        $filename = 'default-project.svg';
        $uploadSuccess = true;

        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['gambar'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $error = 'Gagal mengunggah file gambar (Error code: ' . $file['error'] . ')';
                $uploadSuccess = false;
            } elseif ($file['size'] > 3 * 1024 * 1024) {
                $error = 'Ukuran gambar maksimal 3 Megabyte!';
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
                    $filename = 'proj_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                        $error = 'Gagal menyimpan file gambar ke server.';
                        $uploadSuccess = false;
                    }
                }
            }
        }

        if ($uploadSuccess && empty($error)) {
            $stmt = $pdo->prepare("INSERT INTO projects (user_id, nama_project, deskripsi, gambar, link_project, demo) 
                                   VALUES (:uid, :nama, :deskripsi, :gambar, :link, :demo)");
            $stmt->execute([
                'uid'       => $targetUserId,
                'nama'      => $namaProject,
                'deskripsi' => $deskripsi,
                'gambar'    => $filename,
                'link'      => $linkProject,
                'demo'      => !empty($demo) ? $demo : null
            ]);

            flash_set('success', 'Project karya "<strong>' . e($namaProject) . '</strong>" berhasil ditambahkan dan dipublikasikan!');
            
            if ($currentRole === 'super_admin' || $currentRole === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }
            exit;
        }
    }
}

// Ambil list siswa untuk pemilihan pemilik jika yang login adalah admin/super_admin
$students = [];
if ($currentRole === 'super_admin' || $currentRole === 'admin') {
    $students = $pdo->query("SELECT id, nama, email FROM users WHERE role = 'siswa' ORDER BY nama ASC")->fetchAll();
}

$pageTitle = 'Tambah Project Baru (JS Preview)';
$activePage = 'add_project';
$pageCss = 'form.css';

require __DIR__ . '/includes/header.php';
?>

<div class="form-card">
    <div class="form-card-header">
        <div class="brand-icon" style="margin: 0 auto 1rem; width: 48px; height: 48px; font-size: 1.4rem;">
            <i class="fa-solid fa-folder-plus"></i>
        </div>
        <h1 style="text-align: center;">Tambah Project Karya Baru</h1>
        <p style="text-align: center;">Publikasikan portofolio karya digital Anda ke etalase dengan fitur Real-time JS Preview</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div><?= e($error) ?></div>
        </div>
    <?php endif; ?>

    <form action="project_tambah.php" method="POST" enctype="multipart/form-data" class="form-grid">
        <!-- Pilihan Pemilik Project untuk Admin/Super Admin -->
        <?php if ($currentRole === 'super_admin' || $currentRole === 'admin'): ?>
            <div class="form-group">
                <label for="user_id" class="form-label">
                    Pemilik Project (Akun Siswa) <span class="required">*</span>
                </label>
                <select name="user_id" id="user_id" class="form-control" required>
                    <option value="<?= $currentUserId ?>">-- Saya Sendiri (<?= e($currentUser['nama']) ?>) --</option>
                    <optgroup label="Daftar Akun Siswa">
                        <?php foreach ($students as $stu): ?>
                            <option value="<?= (int)$stu['id'] ?>" <?= ($targetUserId === (int)$stu['id']) ? 'selected' : '' ?>>
                                <?= e($stu['nama']) ?> (<?= e($stu['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
                <div class="form-hint">Sebagai Administrator, Anda dapat mengaitkan karya ini ke siswa yang bersangkutan.</div>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="nama_project" class="form-label">
                Nama / Judul Project <span class="required">*</span>
            </label>
            <input type="text" name="nama_project" id="nama_project" class="form-control" 
                   placeholder="Contoh: KantinKu - Aplikasi Pemesanan Kantin Digital" 
                   required value="<?= e($namaProject) ?>">
        </div>

        <div class="form-group">
            <label for="deskripsi" class="form-label">
                Deskripsi Lengkap Project <span class="required">*</span>
            </label>
            <textarea name="deskripsi" id="deskripsi" class="form-control" rows="4" 
                      placeholder="Jelaskan latar belakang masalah, fitur utama, teknologi yang digunakan (PHP, MySQL, CSS)..." 
                      required><?= e($deskripsi) ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="link_project" class="form-label">
                    Link Repository / Dokumentasi <span class="required">*</span>
                </label>
                <input type="url" name="link_project" id="link_project" class="form-control" 
                       placeholder="https://github.com/username/project" 
                       required value="<?= e($linkProject) ?>">
            </div>

            <div class="form-group">
                <label for="demo" class="form-label">
                    Link Demo Langsung (Opsional)
                </label>
                <input type="url" name="demo" id="demo" class="form-control" 
                       placeholder="https://demo.project.sch.id" 
                       value="<?= e($demo) ?>">
            </div>
        </div>

        <!-- Bagian Upload Gambar dengan Fitur JS Preview -->
        <div class="form-group">
            <label class="form-label">
                Gambar / Thumbnail Project <span class="required">*</span>
            </label>
            <div class="upload-dropzone" id="dropzoneArea">
                <div class="upload-icon">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                </div>
                <div style="font-weight: 700; color: var(--color-text-main); margin-bottom: 0.25rem;">
                    Pilih File Gambar atau Tarik ke Sini
                </div>
                <div class="form-hint">Format didukung: JPG, PNG, WEBP, SVG (Maksimal 3 MB)</div>
                <input type="file" name="gambar" id="projectImageInput" accept="image/*">
            </div>

            <!-- Real-time JS Preview Box -->
            <div class="image-preview-wrapper" id="previewBox">
                <div class="preview-thumb-box">
                    <img src="" alt="Pratinjau Gambar" id="previewImgElement" class="preview-thumb-img">
                </div>
                <div class="preview-meta">
                    <div class="preview-filename" id="previewFileName">filename.png</div>
                    <div class="preview-filesize" id="previewFileSize">0 KB</div>
                    <div class="preview-badge-status">
                        <i class="fa-solid fa-circle-check"></i> Siap Diunggah &amp; Ditampilkan
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="index.php" class="btn btn-outline">Batal</a>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-paper-plane"></i> Publikasikan Project
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
    const dropzoneArea = document.getElementById('dropzoneArea');

    if (fileInput) {
        fileInput.addEventListener('change', function (e) {
            handleFileSelect(e.target.files[0]);
        });
    }

    function handleFileSelect(file) {
        if (!file) return;

        // Validasi tipe file via JS
        if (!file.type.startsWith('image/')) {
            alert('File yang dipilih harus berupa gambar (JPG, PNG, WEBP, atau SVG)!');
            fileInput.value = '';
            previewBox.classList.remove('active');
            return;
        }

        // Validasi ukuran (3MB)
        if (file.size > 3 * 1024 * 1024) {
            alert('Ukuran file melebihi batas maksimal 3 MB!');
            fileInput.value = '';
            previewBox.classList.remove('active');
            return;
        }

        // Tampilkan info file
        previewFileName.textContent = file.name;
        previewFileSize.textContent = (file.size / 1024).toFixed(1) + ' KB';

        // Baca file menggunakan FileReader API untuk Live Preview instan
        const reader = new FileReader();
        reader.onload = function (event) {
            previewImg.src = event.target.result;
            previewBox.classList.add('active');
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
