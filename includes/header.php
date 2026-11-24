<?php
/**
 * Header Component - Sistem Informasi Aset SDN Budi Karya
 * Dilengkapi dengan Logo Sekolah (assets/logo.webp), Navigasi Atas, Status Pengguna & Mobile Responsive
 */

// Pastikan sesi aktif untuk membaca data admin yang login
require_once __DIR__ . '/auth.php';

// Lindungi komponen internal agar hanya dapat diakses pengguna yang sah
if (empty($skipAuthCheck)) {
    require_auth();
}

// Deteksi path dasar aplikasi & URL web
$projectDir = str_replace('\\', '/', realpath(dirname(__DIR__)));
$docRoot    = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';

if ($docRoot !== '' && str_starts_with($projectDir, $docRoot)) {
    $subPath = substr($projectDir, strlen($docRoot));
    $appUrlPath = '/' . ltrim($subPath, '/');
    $appUrlPath = rtrim($appUrlPath, '/') . '/';
} else {
    $appUrlPath = '/';
}

$scriptUri  = $_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? '';
$isInPages  = (strpos($scriptUri, '/pages/') !== false);
$isInSubdir = $isInPages || (strpos($scriptUri, '/includes/') !== false);

$basePath   = $basePath ?? ($isInPages ? './' : ($appUrlPath . 'pages/'));
$homePath   = $homePath ?? ($appUrlPath . 'index.php');
$assetsPath = $assetsPath ?? ($isInSubdir ? '../assets/' : 'assets/');
$logoUrl    = $appUrlPath . 'assets/logo.webp';

// Tentukan data URI agar logo selalu tampil seketika tanpa error path / 404
$logoLocalFile = dirname(__DIR__) . '/assets/logo.webp';
$logoSrc       = file_exists($logoLocalFile) 
    ? ('data:image/webp;base64,' . base64_encode(file_get_contents($logoLocalFile))) 
    : $logoUrl;

// Informasi Sekolah & Aplikasi
$schoolName = 'SDN Budi Karya';
$appName    = 'Sistem Informasi Inventaris Aset';
$pageTitle  = $pageTitle ?? ($appName . ' - ' . $schoolName);

// Informasi Pengguna Login
$userName    = $_SESSION['username'] ?? 'Admin Aset';
$userNama    = $_SESSION['nama_lengkap'] ?? $userName;
$userRole    = $_SESSION['role'] ?? 'Administrator';
$userInitial = strtoupper(substr($userNama, 0, 1));

// Format Tanggal Bahasa Indonesia
$hariArray = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$bulanArray = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
$hariIni = $hariArray[(int)date('w')] ?? '';
$tanggalStr = date('j') . ' ' . ($bulanArray[(int)date('n')] ?? date('M')) . ' ' . date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Informasi Inventaris dan Manajemen Aset Sekolah SDN Budi Karya">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(get_csrf_token()); ?>">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <!-- Favicon Sekolah -->
    <link rel="icon" type="image/webp" href="<?php echo htmlspecialchars($logoSrc); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($logoSrc); ?>">

    <!-- Google Fonts & Phosphor Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/bold/style.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css"/>

    <!-- Header & Layout Stylesheet & SweetAlert2 Theme -->
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/header.css'); ?>">
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/sweetalert-theme.css'); ?>">
</head>
<body>

<!-- Backdrop untuk Sidebar saat dibuka di layar Mobile/Tablet -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeMobileSidebar()"></div>

<!-- Top Navigation Header -->
<header class="app-header" id="appHeader">
    <!-- Kiri: Tombol Toggle Sidebar & Brand Logo SDN Budi Karya -->
    <div class="header-left">
        <button type="button" class="header-toggle-sidebar" id="headerSidebarToggle" aria-label="Buka Navigasi Sidebar" onclick="toggleMobileSidebar()">
            <i class="ph-bold ph-list"></i>
        </button>

        <a href="<?php echo htmlspecialchars($homePath); ?>" class="header-brand" title="Beranda Aset SDN Budi Karya">
            <div class="header-logo-wrapper">
                <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="Logo SDN Budi Karya" class="header-logo-img" width="40" height="40" loading="eager">
            </div>
            <div class="header-brand-info">
                <span class="header-school-name"><?php echo htmlspecialchars($schoolName); ?></span>
                <span class="header-app-tag"><?php echo htmlspecialchars($appName); ?></span>
            </div>
        </a>
    </div>

    <!-- Tengah: Pencarian Cepat Data Aset -->
    <div class="header-center">
        <div class="header-search-bar">
            <i class="ph-bold ph-magnifying-glass header-search-icon"></i>
            <input type="text" class="header-search-input" placeholder="Cari nama aset, kode barang, ruangan..." aria-label="Cari Aset">
        </div>
    </div>

    <!-- Kanan: Tanggal Hari Ini & Profil Pengguna -->
    <div class="header-right">
        <!-- Tanggal -->
        <div class="header-date-badge" title="Tanggal Sistem">
            <i class="ph-bold ph-calendar-blank"></i>
            <span><?php echo htmlspecialchars($hariIni . ', ' . $tanggalStr); ?></span>
        </div>

        <!-- Profil & Menu Pengguna -->
        <div class="header-user-wrapper">
            <button type="button" class="header-user-btn" id="userMenuBtn" aria-expanded="false" onclick="toggleUserDropdown(event)">
                <div class="header-user-avatar">
                    <?php echo htmlspecialchars($userInitial); ?>
                </div>
                <div class="header-user-meta">
                    <span class="header-user-name"><?php echo htmlspecialchars($userNama); ?></span>
                    <span class="header-user-role"><?php echo htmlspecialchars($userRole); ?></span>
                </div>
                <i class="ph-bold ph-caret-down header-user-caret"></i>
            </button>

            <!-- Dropdown Menu -->
            <div class="header-dropdown-menu" id="userDropdownMenu">
                <div class="header-dropdown-header">
                    <div style="font-size: 13px; font-weight: 700; color: var(--color-slate-800);"><?php echo htmlspecialchars($userNama); ?></div>
                    <span class="header-dropdown-role"><?php echo htmlspecialchars($userRole); ?></span>
                </div>
                <a href="<?php echo htmlspecialchars($basePath . 'kelola-akun.php'); ?>" class="header-dropdown-item">
                    <i class="ph-bold ph-user-gear"></i>
                    <span>Kelola Akun</span>
                </a>
                <div class="header-dropdown-divider"></div>
                <a href="<?php echo htmlspecialchars($basePath . 'logout.php'); ?>" class="header-dropdown-item danger" onclick="confirmLogout(event, this.href);">
                    <i class="ph-bold ph-sign-out"></i>
                    <span>Keluar</span>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- SweetAlert2 Library & Custom Config -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?php echo htmlspecialchars($assetsPath . 'js/sweetalert-init.js'); ?>"></script>

<!-- Header & Navigation Interactions -->
<script src="<?php echo htmlspecialchars($assetsPath . 'js/header.js'); ?>" defer></script>

