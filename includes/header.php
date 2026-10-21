<?php
// header.php
require_once __DIR__ . '/../config/config.php';
$currentUser = current_user();
$currentRole = current_role();
$pageTitle = isset($pageTitle) ? $pageTitle . ' - Etalase Digital Stella' : 'Etalase Digital - SMK Telkom Lampung';
$activePage = $activePage ?? '';
$flash = flash_get();
$brandLogoPath = __DIR__ . '/../assets/images/logots.png';
$brandLogoVersion = md5_file($brandLogoPath);
$stylePath = __DIR__ . '/../assets/css/style.css';
$styleVersion = filemtime($stylePath);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="Etalase Digital Karya Siswa SMK Telkom Lampung - Project UKK / LKPD Pemrograman Web & Hak Akses">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= e($styleVersion) ?>">
</head>
<body class="<?= $activePage === 'home' ? 'page-home' : '' ?>">

<!-- Navigation Header -->
<header class="navbar">
    <div class="container nav-container">
        <a href="index.php" class="nav-brand">
            <div class="brand-icon brand-logo-container">
                <img src="assets/images/logots.png?v=<?= e($brandLogoVersion) ?>" alt="Logo SMK Telkom Lampung" class="brand-logo">
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
                    <a href="index.php" class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>">
                        <i class="fa-solid fa-compass"></i> Katalog Karya
                    </a>
                </li>
                <?php if ($currentUser): ?>
                    <li>
                        <a href="dashboard.php" class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                            <i class="fa-solid fa-gauge-high"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="project_form.php" class="nav-link <?= $activePage === 'add_project' ? 'active' : '' ?>">
                            <i class="fa-solid fa-circle-plus"></i> Tambah Project
                        </a>
                    </li>
                    <?php if ($currentRole === 'super_admin' || $currentRole === 'admin'): ?>
                        <li>
                            <a href="users.php" class="nav-link <?= $activePage === 'users' ? 'active' : '' ?>">
                                <i class="fa-solid fa-users-gear"></i> Kelola <?= $currentRole === 'admin' ? 'Siswa' : 'Pengguna' ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li>
                        <a href="profile.php" class="nav-link <?= $activePage === 'profile' ? 'active' : '' ?>">
                            <i class="fa-solid fa-user"></i> Profil
                        </a>
                    </li>
                    <?php endif; ?>
            </ul>
        </nav>

        <!-- Right Side: User Menu / Login -->
        <div class="nav-actions">
            <?php if ($currentUser): ?>
                <a href="profile.php" class="nav-user" title="Buka profil akun">
                    <div class="user-avatar">
                        <?php if (!empty($currentUser['avatar'])): ?>
                            <img src="uploads/profiles/<?= e(rawurlencode($currentUser['avatar'])) ?>" alt="">
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
                <a href="logout.php" class="btn btn-outline btn-sm" title="Keluar dari Akun">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-sm">
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
                <div><?= e($flash['message']) ?></div>
            </div>
        <?php endif; ?>
