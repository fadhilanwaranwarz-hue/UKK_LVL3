<?php
// ============================================================================
// middleware/auth_check.php - Proteksi Sesi & Hak Akses (3 Role)
// Role yang didukung: 'super_admin', 'admin', 'siswa'
// Etalase Digital Stella - SMK Telkom Lampung
// ============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah pengguna saat ini sudah login
 */
if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool {
        return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }
}

/**
 * Mendapatkan data pengguna yang sedang login
 */
if (!function_exists('current_user')) {
    function current_user(): ?array {
        return $_SESSION['user'] ?? null;
    }
}

/**
 * Mendapatkan role pengguna saat ini ('super_admin', 'admin', 'siswa')
 */
if (!function_exists('current_role')) {
    function current_role(): ?string {
        return $_SESSION['user']['role'] ?? null;
    }
}

/**
 * Mendapatkan ID pengguna saat ini
 */
if (!function_exists('current_user_id')) {
    function current_user_id(): int {
        return (int)($_SESSION['user']['id'] ?? 0);
    }
}

/**
 * Mendapatkan path dasar redirect yang benar dari root maupun subfolder
 */
if (!function_exists('get_base_redirect')) {
    function get_base_redirect(string $path): string {
        // Cek jika script dipanggil dari subfolder (seperti /admin/ atau /auth/)
        $isInSubdir = (basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) !== '' && 
                       in_array(basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')), ['admin', 'auth', 'middleware', 'config'], true));
        if ($isInSubdir) {
            return '../' . ltrim($path, '/');
        }
        return ltrim($path, '/');
    }
}

/**
 * Proteksi Autentikasi: Wajib Login
 * Jika belum login, redirect ke halaman login
 */
if (!function_exists('auth_check')) {
    function auth_check(?string $redirect = null): void {
        if (!is_logged_in()) {
            if (function_exists('flash_set')) {
                flash_set('warning', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
            }
            $target = $redirect ?? get_base_redirect('auth/login.php');
            header("Location: {$target}");
            exit;
        }
    }
}

// Alias untuk kompatibilitas kode
if (!function_exists('login_required')) {
    function login_required(?string $redirect = null): void {
        auth_check($redirect);
    }
}

/**
 * Proteksi Hak Akses (Role-Based Access Control)
 * Mendukung 3 Role: 'super_admin', 'admin', 'siswa'
 */
if (!function_exists('require_role')) {
    function require_role($allowedRoles, ?string $redirect = null): void {
        auth_check($redirect);

        $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
        $userRole = current_role();

        if (!in_array($userRole, $roles, true)) {
            if (function_exists('flash_set')) {
                flash_set('danger', 'Akses ditolak! Anda tidak memiliki izin untuk membuka halaman tersebut.');
            }
            $target = $redirect ?? get_base_redirect('index.php');
            header("Location: {$target}");
            exit;
        }
    }
}

// Alias untuk kompatibilitas
if (!function_exists('role_required')) {
    function role_required($allowedRoles, ?string $redirect = null): void {
        require_role($allowedRoles, $redirect);
    }
}

/**
 * Proteksi Hak Akses Project:
 * - Super Admin & Admin: Berhak kelola semua project
 * - Siswa: Hanya berhak kelola project miliknya sendiri
 */
if (!function_exists('can_manage_project')) {
    function can_manage_project(?array $project): bool {
        if (!is_logged_in() || empty($project)) {
            return false;
        }
        $role = current_role();
        if ($role === 'super_admin' || $role === 'admin') {
            return true;
        }
        if ($role === 'siswa' && (int)$project['user_id'] === current_user_id()) {
            return true;
        }
        return false;
    }
}

/**
 * Helper Tampilan Nama Role
 */
if (!function_exists('role_name')) {
    function role_name(?string $role): string {
        $names = [
            'super_admin' => 'Super Admin',
            'admin'       => 'Admin',
            'siswa'       => 'Siswa'
        ];
        return $names[$role] ?? ucfirst($role ?? '');
    }
}

/**
 * Helper HTML Badge Role
 */
if (!function_exists('role_badge')) {
    function role_badge(?string $role): string {
        switch ($role) {
            case 'super_admin':
                return '<span class="badge badge-super"><i class="fa-solid fa-crown me-1"></i> Super Admin</span>';
            case 'admin':
                return '<span class="badge badge-admin"><i class="fa-solid fa-shield-halved me-1"></i> Admin</span>';
            case 'siswa':
                return '<span class="badge badge-siswa"><i class="fa-solid fa-graduation-cap me-1"></i> Siswa</span>';
            default:
                return '<span class="badge badge-secondary">' . htmlspecialchars($role ?? '') . '</span>';
        }
    }
}
