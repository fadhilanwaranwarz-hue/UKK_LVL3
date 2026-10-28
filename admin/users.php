<?php
// ============================================================================
// admin/users.php - Kelola User (Super Admin Only)
// Sesuai Ketentuan Spesifikasi Tugas
// Etalase Digital Stella - SMK Telkom Lampung
// ============================================================================

require_once __DIR__ . '/../config/kuis_db.php';
require_once __DIR__ . '/../middleware/auth_check.php';

// Hak Akses Ketat: Hanya Super Admin yang diizinkan!
require_role('super_admin', '../index.php');

$currentUser = current_user();
$currentUserId = current_user_id();

// ----------------------------------------------------------------------------
// Pemrosesan Tindakan (Action): Tambah, Edit, Hapus User
// ----------------------------------------------------------------------------
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. TAMBAH USER BARU
    if ($action === 'create') {
        $nama     = trim($_POST['nama'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'siswa';

        if (empty($nama) || empty($email) || empty($password)) {
            flash_set('danger', 'Harap isi semua kolom wajib untuk pengguna baru.');
        } elseif (!in_array($role, ['super_admin', 'admin', 'siswa'], true)) {
            flash_set('danger', 'Role yang dipilih tidak valid.');
        } else {
            // Cek email
            $check = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $check->execute(['email' => $email]);
            if ($check->fetch()) {
                flash_set('danger', 'Email sudah digunakan oleh akun lain.');
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $ins = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (:n, :e, :p, :r)");
                $ins->execute([
                    'n' => $nama,
                    'e' => $email,
                    'p' => $hash,
                    'r' => $role
                ]);
                flash_set('success', 'Pengguna <strong>' . e($nama) . '</strong> (' . role_name($role) . ') berhasil ditambahkan.');
            }
        }
        header('Location: users.php');
        exit;
    }

    // 2. UPDATE USER
    if ($action === 'update') {
        $id       = (int)($_POST['id'] ?? 0);
        $nama     = trim($_POST['nama'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $role     = $_POST['role'] ?? 'siswa';
        $newPass  = $_POST['password'] ?? '';

        if (empty($nama) || empty($email)) {
            flash_set('danger', 'Nama dan email tidak boleh kosong.');
        } elseif (!in_array($role, ['super_admin', 'admin', 'siswa'], true)) {
            flash_set('danger', 'Role yang dipilih tidak valid.');
        } else {
            // Cek duplikasi email pada user lain
            $check = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
            $check->execute(['email' => $email, 'id' => $id]);
            if ($check->fetch()) {
                flash_set('danger', 'Alamat email tersebut sudah dipakai pengguna lain.');
            } else {
                if (!empty($newPass)) {
                    $hash = password_hash($newPass, PASSWORD_BCRYPT);
                    $upd = $pdo->prepare("UPDATE users SET nama = :n, email = :e, role = :r, password = :p WHERE id = :id");
                    $upd->execute(['n' => $nama, 'e' => $email, 'r' => $role, 'p' => $hash, 'id' => $id]);
                } else {
                    $upd = $pdo->prepare("UPDATE users SET nama = :n, email = :e, role = :r WHERE id = :id");
                    $upd->execute(['n' => $nama, 'e' => $email, 'r' => $role, 'id' => $id]);
                }

                // Jika mengedit akun sendiri, perbarui data sesi
                if ($id === $currentUserId) {
                    $_SESSION['user']['nama'] = $nama;
                    $_SESSION['user']['email'] = $email;
                    $_SESSION['user']['role'] = $role;
                }

                flash_set('success', 'Data pengguna <strong>' . e($nama) . '</strong> berhasil diperbarui.');
            }
        }
        header('Location: users.php');
        exit;
    }
}

// 3. DELETE USER (GET)
if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);

    if ($id === $currentUserId) {
        flash_set('danger', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif!');
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $targetUser = $stmt->fetch();

        if ($targetUser) {
            // Hapus file gambar project yang terkait
            $projStmt = $pdo->prepare("SELECT gambar FROM projects WHERE user_id = :uid");
            $projStmt->execute(['uid' => $id]);
            $userProjects = $projStmt->fetchAll();

            $defaults = ['default-project.svg', 'kantinku-preview.svg', 'sim-alumni.svg', 'stella-library.svg'];
            foreach ($userProjects as $pr) {
                if (!empty($pr['gambar']) && !in_array($pr['gambar'], $defaults, true)) {
                    $filePath = dirname(__DIR__) . '/uploads/projects/' . $pr['gambar'];
                    if (is_file($filePath)) {
                        @unlink($filePath);
                    }
                }
            }

            // Hapus user dari database (Cascade akan menghapus projectnya)
            $del = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $del->execute(['id' => $id]);

            flash_set('success', 'Pengguna <strong>' . e($targetUser['nama']) . '</strong> berhasil dihapus.');
        } else {
            flash_set('danger', 'Pengguna tidak ditemukan.');
        }
    }
    header('Location: users.php');
    exit;
}

// ----------------------------------------------------------------------------
// Pengambilan Data Pengguna untuk Tampilan
// ----------------------------------------------------------------------------
$q = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? '';

$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM projects p WHERE p.user_id = u.id) AS total_project 
        FROM users u WHERE 1=1";
$params = [];

if (!empty($roleFilter) && in_array($roleFilter, ['super_admin', 'admin', 'siswa'], true)) {
    $sql .= " AND u.role = :role";
    $params['role'] = $roleFilter;
}

if ($q !== '') {
    $sql .= " AND (u.nama LIKE :q1 OR u.email LIKE :q2)";
    $like = "%{$q}%";
    $params['q1'] = $like;
    $params['q2'] = $like;
}

$sql .= " ORDER BY CASE u.role WHEN 'super_admin' THEN 1 WHEN 'admin' THEN 2 ELSE 3 END, u.nama ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usersList = $stmt->fetchAll();

// Cek apakah sedang mode edit suatu user
$editUser = null;
$editId = (int)($_GET['edit'] ?? 0);
if ($editId > 0) {
    $eStmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $eStmt->execute(['id' => $editId]);
    $editUser = $eStmt->fetch();
}

// Statistik Pengguna
$countTotal = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$countSiswa = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'siswa'")->fetchColumn();
$countAdmin = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$countSuper = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'super_admin'")->fetchColumn();

$pageTitle = 'Kelola Pengguna (Super Admin Only)';
$activePage = 'users';
$pageCss = 'admin.css';

require __DIR__ . '/../includes/header.php';
?>

<!-- Header Toolbar -->
<div class="admin-toolbar">
    <div class="admin-title-area">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
            <h1>Kelola Seluruh Pengguna</h1>
            <span class="tag-super-only">
                <i class="fa-solid fa-lock"></i> Super Admin Only
            </span>
        </div>
        <p>Manajemen data akun seluruh hak akses (Super Admin, Admin, dan Siswa) Stella.</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="index.php" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>
</div>

<!-- Statistik Pengguna -->
<div class="admin-stats-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-icon indigo">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <div class="admin-stat-val"><?= (int)$countTotal ?></div>
            <div class="admin-stat-lbl">Total Pengguna</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon green">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div>
            <div class="admin-stat-val"><?= (int)$countSiswa ?></div>
            <div class="admin-stat-lbl">Akun Siswa</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon red">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <div>
            <div class="admin-stat-val"><?= (int)$countAdmin ?></div>
            <div class="admin-stat-lbl">Admin</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon amber">
            <i class="fa-solid fa-crown"></i>
        </div>
        <div>
            <div class="admin-stat-val"><?= (int)$countSuper ?></div>
            <div class="admin-stat-lbl">Super Admin</div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 340px; gap: 1.75rem; align-items: start;">
    <!-- Bagian Kiri: Tabel Daftar User -->
    <div class="table-card" style="margin-bottom: 0;">
        <div class="table-filter-bar">
            <div style="font-weight: 700; color: var(--color-text-main);">
                Daftar Pengguna (<?= count($usersList) ?>)
            </div>
            <form method="GET" action="users.php" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <select name="role" class="form-control" style="padding: 0.45rem; font-size: 0.85rem; width: 130px;" onchange="this.form.submit()">
                    <option value="">Semua Role</option>
                    <option value="super_admin" <?= $roleFilter === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                    <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="siswa" <?= $roleFilter === 'siswa' ? 'selected' : '' ?>>Siswa</option>
                </select>
                <input type="text" name="q" class="form-control" style="padding: 0.45rem 0.75rem; font-size: 0.85rem; width: 170px;" 
                       placeholder="Cari nama/email..." value="<?= e($q) ?>">
                <button type="submit" class="btn btn-outline btn-sm">
                    <i class="fa-solid fa-search"></i>
                </button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 45px;">No</th>
                        <th>Pengguna</th>
                        <th>Hak Akses (Role)</th>
                        <th>Project</th>
                        <th style="width: 100px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usersList)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2.5rem; color: var(--color-text-muted);">
                                Tidak ada data pengguna yang cocok.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($usersList as $u): ?>
                            <tr>
                                <td style="color: var(--color-text-muted); font-weight: 600;"><?= $no++ ?></td>
                                <td>
                                    <div class="user-cell">
                                        <div class="avatar-cell">
                                            <?= e(strtoupper(substr($u['nama'], 0, 1))) ?>
                                        </div>
                                        <div>
                                            <div class="user-cell-name">
                                                <?= e($u['nama']) ?>
                                                <?php if ((int)$u['id'] === $currentUserId): ?>
                                                    <span style="font-size: 0.72rem; color: var(--color-primary); font-weight: 700;">(Anda)</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="user-cell-email"><?= e($u['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?= role_badge($u['role']) ?>
                                </td>
                                <td>
                                    <span class="badge badge-secondary">
                                        <i class="fa-solid fa-folder-closed"></i> <?= (int)$u['total_project'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions" style="justify-content: center;">
                                        <a href="users.php?edit=<?= (int)$u['id'] ?>" class="btn-icon-action" title="Edit Pengguna">
                                            <i class="fa-solid fa-user-pen"></i>
                                        </a>
                                        <?php if ((int)$u['id'] !== $currentUserId): ?>
                                            <a href="users.php?action=delete&id=<?= (int)$u['id'] ?>" class="btn-icon-action delete" 
                                               title="Hapus Pengguna"
                                               onclick="return confirm('Hapus pengguna \'<?= addslashes($u['nama']) ?>\' beserta seluruh karyanya?');">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bagian Kanan: Form Tambah / Edit User -->
    <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-xl); padding: 1.75rem; box-shadow: var(--shadow-sm);">
        <h3 style="font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid <?= $editUser ? 'fa-user-pen text-indigo' : 'fa-user-plus text-primary' ?>"></i>
            <?= $editUser ? 'Perbarui Pengguna' : 'Tambah Pengguna Baru' ?>
        </h3>

        <form method="POST" action="users.php" style="display: flex; flex-direction: column; gap: 1rem;">
            <input type="hidden" name="action" value="<?= $editUser ? 'update' : 'create' ?>">
            <?php if ($editUser): ?>
                <input type="hidden" name="id" value="<?= (int)$editUser['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" required 
                       value="<?= e($editUser['nama'] ?? '') ?>" placeholder="Nama pengguna...">
            </div>

            <div class="form-group">
                <label class="form-label">Alamat Email</label>
                <input type="email" name="email" class="form-control" required 
                       value="<?= e($editUser['email'] ?? '') ?>" placeholder="email@stella.sch.id">
            </div>

            <div class="form-group">
                <label class="form-label">Hak Akses (Role)</label>
                <select name="role" class="form-control" required>
                    <option value="siswa" <?= ($editUser['role'] ?? '') === 'siswa' ? 'selected' : '' ?>>Siswa (Karya Sendiri)</option>
                    <option value="admin" <?= ($editUser['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin (Kelola Project & Siswa)</option>
                    <option value="super_admin" <?= ($editUser['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Admin (Akses Penuh)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <?= $editUser ? 'Kata Sandi Baru (Opsional)' : 'Kata Sandi' ?>
                </label>
                <input type="password" name="password" class="form-control" 
                       placeholder="<?= $editUser ? 'Kosongkan jika tidak diubah' : 'Minimal 6 karakter' ?>"
                       <?= $editUser ? '' : 'required minlength="6"' ?>>
            </div>

            <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                <button type="submit" class="btn btn-primary btn-sm" style="flex: 1;">
                    <i class="fa-solid fa-save"></i> <?= $editUser ? 'Simpan Perubahan' : 'Tambahkan' ?>
                </button>
                <?php if ($editUser): ?>
                    <a href="users.php" class="btn btn-outline btn-sm">Batal</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
