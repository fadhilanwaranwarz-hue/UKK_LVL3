<?php
// ============================================================================
// includes/header.php - Global Header & Navbar Template
// Etalase Digital Stella - SMK Telkom Lampung
// ============================================================================

require_once __DIR__ . '/../config/kuis_db.php';
require_once __DIR__ . '/../middleware/auth_check.php';

$currentUser = current_user();
$currentRole = current_role();
$pageTitle = isset($pageTitle) ? $pageTitle . ' - Etalase Digital Stella' : 'Etalase Digital - SMK Telkom Lampung';
$activePage = $activePage ?? '';
$pageCss = $pageCss ?? '';
$flash = flash_get();

// Deteksi subfolder agar path CSS/assets/link selalu akurat
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$isSubfolder = (strpos($scriptName, '/admin/') !== false || strpos($scriptName, '/auth/') !== false);
$baseUrl = $isSubfolder ? '../' : '';
$pageCssPath = __DIR__ . '/../assets/css/' . $pageCss;
$pageCssVersion = $pageCss !== '' && is_file($pageCssPath) ? filemtime($pageCssPath) : false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="Etalase Digital Karya Siswa SMK Telkom Lampung - Project UKK / LKPD Pemrograman Web & Hak Akses">
    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Core & Global CSS Sesuai Ketentuan -->
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/style.php.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/style.css">
    <?php if (!empty($pageCss)): ?>
        <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/<?= e($pageCss) ?><?= $pageCssVersion !== false ? '?v=' . (int)$pageCssVersion : '' ?>">
    <?php endif; ?>
</head>
<body class="<?= $activePage === 'home' ? 'page-home' : '' ?>">

<!-- Navigation Header -->
<header class="navbar">
    <div class="container nav-container">
        <a href="<?= $baseUrl ?>index.php" class="nav-brand">
            <div class="brand-icon brand-logo-container">
                <img src="<?= $baseUrl ?>assets/images/logots.png" alt="Logo SMK Telkom Lampung" class="brand-logo">
            </div>
            <div>
                Etalase Digital
                <span class="brand-sub">SMK Telkom Lampung</span>
            </div>
        </a>

        <!-- Mobile Menu Button -->
        <button class="menu-toggle" id="menuToggle" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>

        <!-- Navigation Links -->
        <nav>
            <ul class="nav-menu" id="navMenu">
                <li>
                    <a href="<?= $baseUrl ?>index.php" class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>">
                        <i class="fa-solid fa-compass"></i> Katalog Karya
                    </a>
                </li>
                <?php if ($currentUser): ?>
                    <?php if ($currentRole === 'super_admin' || $currentRole === 'admin'): ?>
                        <li>
                            <a href="<?= $baseUrl ?>admin/index.php" class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                                <i class="fa-solid fa-gauge-high"></i> Dashboard Admin
                            </a>
                        </li>
                    <?php endif; ?>
                    <li>
                        <a href="<?= $baseUrl ?>project_tambah.php" class="nav-link <?= $activePage === 'add_project' ? 'active' : '' ?>">
                            <i class="fa-solid fa-circle-plus"></i> Tambah Project
                        </a>
                    </li>
                    <?php if ($currentRole === 'super_admin'): ?>
                        <li>
                            <a href="<?= $baseUrl ?>admin/users.php" class="nav-link <?= $activePage === 'users' ? 'active' : '' ?>">
                                <i class="fa-solid fa-users-gear"></i> Kelola User
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Right Side: User Menu / Login -->
        <div class="nav-actions">
            <?php if ($currentUser): ?>
                <a href="<?= $baseUrl ?>profile.php" class="nav-user" title="Buka profil saya">
                    <div class="user-avatar">
                        <?php if (!empty($currentUser['avatar']) && is_file(__DIR__ . '/../uploads/profiles/' . $currentUser['avatar'])): ?>
                            <img src="<?= $baseUrl ?>uploads/profiles/<?= e($currentUser['avatar']) ?>" alt="">
                        <?php else: ?>
                            <?= e(strtoupper(substr($currentUser['nama'], 0, 1))) ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-details">
                        <span class="user-name"><?= e($currentUser['nama']) ?></span>
                        <div style="font-size: 0.72rem;">
                            <?= role_badge($currentRole) ?>
                        </div>
                    </div>
                </a>
                <a href="<?= $baseUrl ?>auth/logout.php" class="btn btn-outline btn-sm" title="Keluar dari Akun">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar
                </a>
            <?php else: ?>
                <a href="<?= $baseUrl ?>auth/login.php" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-right-to-bracket"></i> Masuk Akun
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Main Wrapper -->
<main class="main-content">
    <div class="container">
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
                <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : ($flash['type'] === 'danger' ? 'fa-circle-xmark' : 'fa-circle-info') ?> fa-lg"></i>
                <div><?= $flash['message'] ?></div>
            </div>
        <?php endif; ?>
