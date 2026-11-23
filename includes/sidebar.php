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
    } elseif (str_contains($currentScript, 'monitoring')) {
        $activeMenu = 'monitoring';
        if (str_contains($currentUri, 'ruang=kelas-1') || str_contains($currentScript, 'kelas-1')) $activeSubmenu = 'kelas-1';
        elseif (str_contains($currentUri, 'ruang=kelas-2') || str_contains($currentScript, 'kelas-2')) $activeSubmenu = 'kelas-2';
        elseif (str_contains($currentUri, 'ruang=kelas-3') || str_contains($currentScript, 'kelas-3')) $activeSubmenu = 'kelas-3';
        elseif (str_contains($currentUri, 'ruang=kelas-4') || str_contains($currentScript, 'kelas-4')) $activeSubmenu = 'kelas-4';
        elseif (str_contains($currentUri, 'ruang=kelas-5') || str_contains($currentScript, 'kelas-5')) $activeSubmenu = 'kelas-5';
        elseif (str_contains($currentUri, 'ruang=kelas-6') || str_contains($currentScript, 'kelas-6')) $activeSubmenu = 'kelas-6';
        elseif (str_contains($currentUri, 'ruang=aset-umum') || str_contains($currentScript, 'aset-umum')) $activeSubmenu = 'aset-umum';
    } elseif (in_array($currentScript, ['pemeliharaan.php', 'rawat.php', 'perbaikan.php', 'maintenance.php', 'peminjaman.php'])) {
        $activeMenu = 'pemeliharaan';
    } elseif (in_array($currentScript, ['laporan.php', 'report.php', 'cetak.php'])) {
        $activeMenu = 'laporan';
    }
}

// Helper path agar link fleksibel (bisa dipanggil dari root maupun dari subfolder /pages/)
$isInPages = (strpos($_SERVER['PHP_SELF'] ?? '', '/pages/') !== false);
$basePath  = $basePath ?? ($isInPages ? './' : 'pages/');
$homePath  = $homePath ?? ($isInPages ? '../index.php' : 'index.php');
?>

<!-- Phosphor Icons (jika belum dimuat di header) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/bold/style.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css"/>

<style>
:root {
    --sb-maroon-primary: #852019;
    --sb-maroon-dark: #6d1c16;
    --sb-maroon-deep: #3a1210;
    --sb-maroon-hover: rgba(255, 255, 255, 0.12);
    --sb-text-white: #ffffff;
    --sb-text-muted: rgba(255, 255, 255, 0.78);
    --sb-active-bg: #ffffff;
    --sb-active-text: #852019;
    --sb-badge-purple: #986dca;
    --sb-border-subtle: rgba(255, 255, 255, 0.14);
    --sb-font: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Sidebar Container */
.app-sidebar {
    width: 260px;
    height: 100vh;
    max-height: 100vh;
    background: var(--sb-maroon-primary);
    background: linear-gradient(180deg, var(--sb-maroon-primary) 0%, var(--sb-maroon-dark) 100%);
    color: var(--sb-text-white);
    display: flex;
    flex-direction: column;
    font-family: var(--sb-font);
    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
    user-select: none;
    position: sticky;
    top: 0;
    z-index: 100;
    overflow: hidden;
    box-sizing: border-box;
}

/* Brand / Header */
.sidebar-brand {
    padding: 16px 16px 14px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid var(--sb-border-subtle);
    text-decoration: none;
    color: var(--sb-text-white);
    transition: opacity 0.2s ease;
    flex-shrink: 0;
}

.sidebar-brand:hover {
    opacity: 0.95;
}

.sidebar-brand-icon {
    width: 38px;
    height: 38px;
    border-radius: 9px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--sb-text-white);
    font-size: 20px;
    border: 1px solid rgba(255, 255, 255, 0.25);
    flex-shrink: 0;
}

.sidebar-brand-text {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.sidebar-brand-title {
    font-size: 14px;
    font-weight: 800;
    letter-spacing: 0.4px;
    text-transform: uppercase;
}

.sidebar-brand-subtitle {
    font-size: 10.5px;
    font-weight: 500;
    color: var(--sb-text-muted);
    letter-spacing: 0.2px;
}

/* Menu Navigation */
.sidebar-nav {
    flex: 1;
    min-height: 0;
    padding: 10px 10px;
    display: flex;
    flex-direction: column;
    gap: 3px;
    overflow-y: auto;
    overflow-x: hidden;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
}

/* Scrollbar Custom */
.sidebar-nav::-webkit-scrollbar {
    width: 4px;
}
.sidebar-nav::-webkit-scrollbar-track {
    background: transparent;
}
.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
}
.sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.35);
}

/* Menu Item & Buttons */
.sidebar-item {
    list-style: none;
    margin: 0;
    padding: 0;
}

.sidebar-link,
.sidebar-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    border-radius: 8px;
    text-decoration: none;
    color: var(--sb-text-white);
    background: transparent;
    border: none;
    font-family: inherit;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
    box-sizing: border-box;
    text-align: left;
}

.sidebar-link-content,
.sidebar-btn-content {
    display: flex;
    align-items: center;
    gap: 10px;
}

.sidebar-icon {
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: transform 0.2s ease;
}

/* Hover state */
.sidebar-link:hover,
.sidebar-btn:hover {
    background: var(--sb-maroon-hover);
    color: var(--sb-text-white);
    transform: translateX(2px);
}

/* Active State (Seperti di UI Kit: Background Putih & Teks Maroon) */
.sidebar-item.active > .sidebar-link,
.sidebar-group.active > .sidebar-btn {
    background: var(--sb-active-bg);
    color: var(--sb-active-text);
    font-weight: 700;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.18);
    transform: none;
}

.sidebar-item.active > .sidebar-link .sidebar-icon,
.sidebar-group.active > .sidebar-btn .sidebar-icon,
.sidebar-group.active > .sidebar-btn .sidebar-caret {
    color: var(--sb-active-text);
}

/* Caret Arrow for Dropdown */
.sidebar-caret {
    font-size: 14px;
    color: var(--sb-text-muted);
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), color 0.2s ease;
}

.sidebar-group.is-open > .sidebar-btn .sidebar-caret {
    transform: rotate(180deg);
}

/* Collapsible Submenu */
.sidebar-submenu {
    list-style: none;
    margin: 2px 0 4px 10px;
    padding: 2px 0 2px 10px;
    border-left: 2px solid rgba(255, 255, 255, 0.2);
    display: flex;
    flex-direction: column;
    gap: 2px;
    overflow: hidden;
    transition: max-height 0.28s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.2s ease;
}

/* Keadaan tertutup */
.sidebar-group:not(.is-open) > .sidebar-submenu {
    max-height: 0;
    opacity: 0;
    padding-top: 0;
    padding-bottom: 0;
    margin-top: 0;
    margin-bottom: 0;
    border-left-color: transparent;
    pointer-events: none;
}

/* Keadaan terbuka */
.sidebar-group.is-open > .sidebar-submenu {
    max-height: 350px;
    opacity: 1;
}

/* Submenu Links */
.sidebar-sublink {
    display: flex;
    align-items: center;
    padding: 5px 10px;
    border-radius: 6px;
    text-decoration: none;
    color: var(--sb-text-muted);
    font-size: 12.5px;
    font-weight: 500;
    line-height: 1.25;
    transition: all 0.15s ease;
}

.sidebar-sublink:hover {
    color: var(--sb-text-white);
    background: rgba(255, 255, 255, 0.08);
    transform: translateX(2px);
}

/* Active Submenu Link */
.sidebar-subitem.active > .sidebar-sublink {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
    font-weight: 700;
    border-left: 3px solid #ffffff;
}

/* Footer / User Profile */
.sidebar-footer {
    padding: 10px 14px;
    border-top: 1px solid var(--sb-border-subtle);
    background: var(--sb-maroon-deep);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}

.sidebar-user {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: var(--sb-text-white);
    overflow: hidden;
}

.sidebar-user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    border: 1.5px solid rgba(255, 255, 255, 0.3);
}

.sidebar-user-info {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    line-height: 1.2;
}

.sidebar-user-name {
    font-size: 12.5px;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-user-role {
    font-size: 10.5px;
    color: var(--sb-text-muted);
}

.sidebar-logout-btn {
    background: transparent;
    border: none;
    color: var(--sb-text-muted);
    font-size: 17px;
    cursor: pointer;
    padding: 5px;
    border-radius: 6px;
    transition: all 0.15s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}

.sidebar-logout-btn:hover {
    color: #ff8080;
    background: rgba(255, 255, 255, 0.1);
}
</style>

<aside class="app-sidebar" id="appSidebar">
    <!-- Brand Header -->
    <a href="<?php echo htmlspecialchars($homePath); ?>" class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <i class="ph-bold ph-buildings"></i>
        </div>
        <div class="sidebar-brand-text">
            <span class="sidebar-brand-title">ASET SEKOLAH</span>
            <span class="sidebar-brand-subtitle">SDN Budi Karya</span>
        </div>
    </a>

    <!-- Navigation Menu -->
    <ul class="sidebar-nav">
        <!-- 1. Dashboard -->
        <li class="sidebar-item <?php echo ($activeMenu === 'dashboard') ? 'active' : ''; ?>">
            <a href="<?php echo htmlspecialchars($homePath); ?>" class="sidebar-link">
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
                    <a href="<?php echo htmlspecialchars($basePath . 'kib-a.php'); ?>" class="sidebar-sublink">
                        <span>KIB A</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kib-b') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($basePath . 'kib-b.php'); ?>" class="sidebar-sublink">
                        <span>KIB B</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kib-c') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($basePath . 'kib-c.php'); ?>" class="sidebar-sublink">
                        <span>KIB C</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kib-d') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($basePath . 'kib-d.php'); ?>" class="sidebar-sublink">
                        <span>KIB D</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kib-e') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($basePath . 'kib-e.php'); ?>" class="sidebar-sublink">
                        <span>KIB E</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kib-f') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($basePath . 'kib-f.php'); ?>" class="sidebar-sublink">
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
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kelas-1') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($basePath . 'monitoring.php?ruang=kelas-1'); ?>" class="sidebar-sublink">
                        <span>Kelas 1</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kelas-2') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($basePath . 'monitoring.php?ruang=kelas-2'); ?>" class="sidebar-sublink">
                        <span>Kelas 2</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kelas-3') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($basePath . 'monitoring.php?ruang=kelas-3'); ?>" class="sidebar-sublink">
                        <span>Kelas 3</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kelas-4') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($basePath . 'monitoring.php?ruang=kelas-4'); ?>" class="sidebar-sublink">
                        <span>Kelas 4</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kelas-5') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($basePath . 'monitoring.php?ruang=kelas-5'); ?>" class="sidebar-sublink">
                        <span>Kelas 5</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'kelas-6') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($basePath . 'monitoring.php?ruang=kelas-6'); ?>" class="sidebar-sublink">
                        <span>Kelas 6</span>
                    </a>
                </li>
                <li class="sidebar-subitem <?php echo ($activeSubmenu === 'aset-umum') ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($basePath . 'monitoring.php?ruang=aset-umum'); ?>" class="sidebar-sublink">
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
        <a href="<?php echo htmlspecialchars($basePath . 'logout.php'); ?>" class="sidebar-logout-btn" title="Keluar / Logout" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
            <i class="ph-bold ph-sign-out"></i>
        </a>
    </div>
</aside>

<script>
/**
 * Toggle Accordion Submenu Grup
 * @param {string} groupId - ID elemen <li> grup
 */
function toggleSidebarGroup(groupId) {
    const targetGroup = document.getElementById(groupId);
    if (!targetGroup) return;

    const isCurrentlyOpen = targetGroup.classList.contains('is-open');

    // Accordion: tutup submenu lain agar tidak memicu scroll berlebih
    document.querySelectorAll('.sidebar-group').forEach(function(group) {
        if (group !== targetGroup) {
            group.classList.remove('is-open');
            const btn = group.querySelector('.sidebar-btn');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }
    });

    if (isCurrentlyOpen) {
        targetGroup.classList.remove('is-open');
        const button = targetGroup.querySelector('.sidebar-btn');
        if (button) button.setAttribute('aria-expanded', 'false');
    } else {
        targetGroup.classList.add('is-open');
        const button = targetGroup.querySelector('.sidebar-btn');
        if (button) button.setAttribute('aria-expanded', 'true');
    }
}
</script>
