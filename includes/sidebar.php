<?php
/**
 * Sidebar Component - Sistem Informasi Aset SDN Budi Karya
 * Desain sesuai visual UI Kit: Tema Maroon (#852019), state Active Putih, Collapsible Submenu
 */

// Deteksi halaman & parameter aktif
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$currentQuery  = $_SERVER['QUERY_STRING'] ?? '';
$currentUri    = $_SERVER['REQUEST_URI'] ?? '';

// Fallback variabel jika belum di-set di halaman pemanggil
$activeMenu    = $activeMenu ?? '';
$activeSubmenu = $activeSubmenu ?? '';

// Auto-detect menu aktif berdasarkan URL jika tidak di-override
if (empty($activeMenu)) {
    if (in_array($currentScript, ['index.php', 'dashboard.php', ''])) {
        $activeMenu = 'dashboard';
    } elseif (in_array($currentScript, ['kelola-akun.php', 'users.php', 'akun.php', 'user.php'])) {
        $activeMenu = 'kelola-akun';
    } elseif (str_starts_with($currentScript, 'kib-') || in_array($currentScript, ['data-aset.php', 'aset.php'])) {
        $activeMenu = 'data-aset';
        if ($currentScript === 'kib-a.php') $activeSubmenu = 'kib-a';
        elseif ($currentScript === 'kib-b.php') $activeSubmenu = 'kib-b';
        elseif ($currentScript === 'kib-c.php') $activeSubmenu = 'kib-c';
        elseif ($currentScript === 'kib-d.php') $activeSubmenu = 'kib-d';
        elseif ($currentScript === 'kib-e.php') $activeSubmenu = 'kib-e';
        elseif ($currentScript === 'kib-f.php') $activeSubmenu = 'kib-f';
    } elseif (str_contains($currentUri, '/pages/monitoring/') || str_contains($currentScript, 'monitoring')) {
        $activeMenu = 'monitoring';
        if ($currentScript === 'kelas-1.php' || str_contains($currentUri, 'ruang=kelas-1')) $activeSubmenu = 'kelas-1';
        elseif ($currentScript === 'kelas-2.php' || str_contains($currentUri, 'ruang=kelas-2')) $activeSubmenu = 'kelas-2';
        elseif ($currentScript === 'kelas-3.php' || str_contains($currentUri, 'ruang=kelas-3')) $activeSubmenu = 'kelas-3';
        elseif ($currentScript === 'kelas-4.php' || str_contains($currentUri, 'ruang=kelas-4')) $activeSubmenu = 'kelas-4';
        elseif ($currentScript === 'kelas-5.php' || str_contains($currentUri, 'ruang=kelas-5')) $activeSubmenu = 'kelas-5';
        elseif ($currentScript === 'kelas-6.php' || str_contains($currentUri, 'ruang=kelas-6')) $activeSubmenu = 'kelas-6';
        elseif ($currentScript === 'aset-umum.php' || $currentScript === 'umum.php' || str_contains($currentUri, 'ruang=aset-umum') || str_contains($currentUri, 'ruang=umum')) $activeSubmenu = 'aset-umum';
        elseif ($currentScript === 'semua.php') $activeSubmenu = 'semua';
    } elseif (in_array($currentScript, ['pemeliharaan.php', 'rawat.php', 'perbaikan.php', 'maintenance.php', 'peminjaman.php'])) {
        $activeMenu = 'pemeliharaan';
    } elseif (in_array($currentScript, ['laporan.php', 'report.php', 'cetak.php'])) {
        $activeMenu = 'laporan';
    }
}

// Helper path agar link fleksibel (bisa dipanggil dari root maupun dari subfolder /pages/)
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
$isInSubSub = (strpos($scriptUri, '/pages/data-aset/') !== false) || (strpos($scriptUri, '/pages/monitoring/') !== false);
$isInPages  = (strpos($scriptUri, '/pages/') !== false);
$isInSubdir = $isInPages || (strpos($scriptUri, '/includes/') !== false);

$pagesPath      = $isInSubSub ? '../' : ($isInPages ? './' : ($appUrlPath . 'pages/'));
$kibPath        = $isInSubSub
    ? ((strpos($scriptUri, '/pages/data-aset/') !== false) ? './' : '../data-aset/')
    : ($isInPages ? 'data-aset/' : ($appUrlPath . 'pages/data-aset/'));
$monitoringPath = $isInSubSub
    ? ((strpos($scriptUri, '/pages/monitoring/') !== false) ? './' : '../monitoring/')
    : ($isInPages ? 'monitoring/' : ($appUrlPath . 'pages/monitoring/'));
$basePath       = $basePath ?? $pagesPath;
$homePath       = $homePath ?? ($appUrlPath . 'index.php');

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
?>

<!-- Phosphor Icons (jika belum dimuat di header) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/bold/style.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css"/>

<!-- Sidebar Stylesheet -->
<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/sidebar.css'); ?>">

<aside class="app-sidebar" id="appSidebar">
    <!-- Navigation Menu -->
    <ul class="sidebar-nav">
        <!-- 1. Dashboard -->
        <li class="sidebar-item <?php echo ($activeMenu === 'dashboard') ? 'active' : ''; ?>">
            <a href="<?php echo htmlspecialchars($basePath . 'dashboard.php'); ?>" class="sidebar-link">
                <div class="sidebar-link-content">
                    <i class="ph-bold ph-squares-four sidebar-icon"></i>
                    <span>Dashboard</span>
                </div>
            </a>
        </li>

        <!-- 2. Kelola Akun -->
        <li class="sidebar-item <?php echo ($activeMenu === 'kelola-akun') ? 'active' : ''; ?>">
            <a href="<?php echo htmlspecialchars($basePath . 'kelola-akun.php'); ?>" class="sidebar-link">
                <div class="sidebar-link-content">
                    <i class="ph-bold ph-users-three sidebar-icon"></i>
                    <span>Kelola Akun</span>
                </div>
            </a>
        </li>

        <!-- 3. Data Aset (Collapsible Dropdown) -->
        <?php $isDataAsetOpen = ($activeMenu === 'data-aset'); ?>
        <li class="sidebar-item sidebar-group <?php echo $isDataAsetOpen ? 'is-open active' : ''; ?>" id="group-data-aset">
            <button type="button" class="sidebar-btn" onclick="toggleSidebarGroup('group-data-aset')" aria-expanded="<?php echo $isDataAsetOpen ? 'true' : 'false'; ?>">
                <div class="sidebar-btn-content">
                    <i class="ph-bold ph-package sidebar-icon"></i>
                    <span>Data Aset</span>
                </div>
                <i class="ph-bold ph-caret-down sidebar-caret"></i>
            </button>
            <ul class="sidebar-submenu">
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kib-a') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($kibPath . 'kib-a.php'); ?>" class="sidebar-sublink">
                        <span>KIB A</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kib-b') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($kibPath . 'kib-b.php'); ?>" class="sidebar-sublink">
                        <span>KIB B</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kib-c') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($kibPath . 'kib-c.php'); ?>" class="sidebar-sublink">
                        <span>KIB C</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kib-d') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($kibPath . 'kib-d.php'); ?>" class="sidebar-sublink">
                        <span>KIB D</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kib-e') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($kibPath . 'kib-e.php'); ?>" class="sidebar-sublink">
                        <span>KIB E</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kib-f') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($kibPath . 'kib-f.php'); ?>" class="sidebar-sublink">
                        <span>KIB F</span>
                    </a>
                </li>
            </ul>
        </li>

        <!-- 4. Monitoring (Collapsible Dropdown) -->
        <?php $isMonitoringOpen = ($activeMenu === 'monitoring'); ?>
        <li class="sidebar-item sidebar-group <?php echo $isMonitoringOpen ? 'is-open active' : ''; ?>" id="group-monitoring">
            <button type="button" class="sidebar-btn" onclick="toggleSidebarGroup('group-monitoring')" aria-expanded="<?php echo $isMonitoringOpen ? 'true' : 'false'; ?>">
                <div class="sidebar-btn-content">
                    <i class="ph-bold ph-desktop sidebar-icon"></i>
                    <span>Monitoring</span>
                </div>
                <i class="ph-bold ph-caret-down sidebar-caret"></i>
            </button>
            <ul class="sidebar-submenu">
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kelas-1') ? 'active' : ''; ?>" data-monitoring-subitem="kelas-1">
                    <a href="<?php echo htmlspecialchars($monitoringPath . 'kelas-1.php'); ?>" class="sidebar-sublink">
                        <span>Kelas 1</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kelas-2') ? 'active' : ''; ?>" data-monitoring-subitem="kelas-2">
                    <a href="<?php echo htmlspecialchars($monitoringPath . 'kelas-2.php'); ?>" class="sidebar-sublink">
                        <span>Kelas 2</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kelas-3') ? 'active' : ''; ?>" data-monitoring-subitem="kelas-3">
                    <a href="<?php echo htmlspecialchars($monitoringPath . 'kelas-3.php'); ?>" class="sidebar-sublink">
                        <span>Kelas 3</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kelas-4') ? 'active' : ''; ?>" data-monitoring-subitem="kelas-4">
                    <a href="<?php echo htmlspecialchars($monitoringPath . 'kelas-4.php'); ?>" class="sidebar-sublink">
                        <span>Kelas 4</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kelas-5') ? 'active' : ''; ?>" data-monitoring-subitem="kelas-5">
                    <a href="<?php echo htmlspecialchars($monitoringPath . 'kelas-5.php'); ?>" class="sidebar-sublink">
                        <span>Kelas 5</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kelas-6') ? 'active' : ''; ?>" data-monitoring-subitem="kelas-6">
                    <a href="<?php echo htmlspecialchars($monitoringPath . 'kelas-6.php'); ?>" class="sidebar-sublink">
                        <span>Kelas 6</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'aset-umum') ? 'active' : ''; ?>" data-monitoring-subitem="umum">
                    <a href="<?php echo htmlspecialchars($monitoringPath . 'aset-umum.php'); ?>" class="sidebar-sublink">
                        <span>Aset Umum</span>
                    </a>
                </li>
            </ul>
        </li>

        <!-- 5. Pemeliharaan -->
        <li class="sidebar-item <?php echo ($activeMenu === 'pemeliharaan') ? 'active' : ''; ?>">
            <a href="<?php echo htmlspecialchars($basePath . 'pemeliharaan.php'); ?>" class="sidebar-link">
                <div class="sidebar-link-content">
                    <i class="ph-bold ph-wrench sidebar-icon"></i>
                    <span>Pemeliharaan</span>
                </div>
            </a>
        </li>

        <!-- 6. Laporan -->
        <li class="sidebar-item <?php echo ($activeMenu === 'laporan') ? 'active' : ''; ?>">
            <a href="<?php echo htmlspecialchars($basePath . 'laporan.php'); ?>" class="sidebar-link">
                <div class="sidebar-link-content">
                    <i class="ph-bold ph-file-text sidebar-icon"></i>
                    <span>Laporan</span>
                </div>
            </a>
        </li>
    </ul>

    <!-- Footer Profile & Logout -->
    <div class="sidebar-footer">
        <a href="<?php echo htmlspecialchars($basePath . 'kelola-akun.php'); ?>" class="sidebar-user" title="Profil Admin">
            <div class="sidebar-user-avatar">
                <i class="ph-fill ph-user"></i>
            </div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin Aset'); ?></span>
                <span class="sidebar-user-role"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Administrator'); ?></span>
            </div>
        </a>
        <a href="<?php echo htmlspecialchars($basePath . 'logout.php'); ?>" class="sidebar-logout-btn" title="Keluar / Logout" onclick="confirmLogout(event, this.href);">
            <i class="ph-bold ph-sign-out"></i>
        </a>
    </div>
</aside>

<!-- Sidebar Interactions -->
<script src="<?php echo htmlspecialchars($assetsPath . 'js/sidebar.js'); ?>" defer></script>

