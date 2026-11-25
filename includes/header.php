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
    if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }
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
$isInSubSub = (strpos($scriptUri, '/pages/data-aset/') !== false);
$isInPages  = (strpos($scriptUri, '/pages/') !== false);
$isInSubdir = $isInPages || (strpos($scriptUri, '/includes/') !== false);

$pagesPath  = $isInSubSub ? '../' : ($isInPages ? './' : ($appUrlPath . 'pages/'));

if (!isset($assetsPath)) {
    if ($isInSubSub) {
        $assetsPath = '../../assets/';
    } elseif ($isInSubdir) {
        $assetsPath = '../assets/';
    } else {
        $assetsPath = 'assets/';
    }
}
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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

        <a href="<?php echo htmlspecialchars($pagesPath . 'dashboard.php'); ?>" class="header-brand" title="Beranda Aset SDN Budi Karya">
            <div class="header-logo-wrapper">
                <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="Logo SDN Budi Karya" class="header-logo-img" width="40" height="40" loading="eager">
            </div>
            <div class="header-brand-info">
                <span class="header-school-name"><?php echo htmlspecialchars($schoolName); ?></span>
                <span class="header-app-tag"><?php echo htmlspecialchars($appName); ?></span>
            </div>
        </a>
    </div>

    <!-- Kanan: Tanggal Hari Ini & Profil Pengguna -->
    <div class="header-right">
        <!-- Tanggal -->
        <div class="header-date-badge" title="Tanggal Sistem">
            <i class="ph-bold ph-calendar-blank"></i>
            <span><?php echo htmlspecialchars($hariIni . ', ' . $tanggalStr); ?></span>
        </div>
        </div>
    </div>
</header>

<!-- SweetAlert2 Library & Custom Config -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?php echo htmlspecialchars($assetsPath . 'js/sweetalert-init.js'); ?>"></script>

<!-- Header & Navigation Interactions -->
<script src="<?php echo htmlspecialchars($assetsPath . 'js/header.js'); ?>" defer></script>

