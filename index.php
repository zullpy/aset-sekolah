<?php
/**
 * Halaman Pemilihan Peran (Landing / Role Selection)
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Deteksi path dasar aplikasi & file aset
$projectDir = str_replace('\\', '/', realpath(__DIR__));
$docRoot    = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';

if ($docRoot !== '' && str_starts_with($projectDir, $docRoot)) {
    $subPath = substr($projectDir, strlen($docRoot));
    $appUrlPath = '/' . ltrim($subPath, '/');
    $appUrlPath = rtrim($appUrlPath, '/') . '/';
} else {
    $appUrlPath = '/';
}

$assetsPath     = 'assets/';
$logoLocalFile  = __DIR__ . '/assets/logo.webp';
$bgLocalFile    = __DIR__ . '/assets/sekolah.webp';

// Gambar Logo Sekolah
$logoSrc = file_exists($logoLocalFile)
    ? ('data:image/webp;base64,' . base64_encode(file_get_contents($logoLocalFile)))
    : ($appUrlPath . 'assets/logo.webp');

// Gambar Background Sekolah
$bgSchoolUrl = file_exists($bgLocalFile)
    ? ('data:image/webp;base64,' . base64_encode(file_get_contents($bgLocalFile)))
    : ($appUrlPath . 'assets/sekolah.webp');

// Target URL login
$loginAdminUrl    = file_exists(__DIR__ . '/pages/login.php') ? 'pages/login.php?role=administrator' : 'login.php?role=administrator';
$loginOperatorUrl = file_exists(__DIR__ . '/pages/login.php') ? 'pages/login.php?role=operator' : 'login.php?role=operator';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Pilih peran untuk masuk ke Sistem Inventaris Aset Sekolah SDN Budi Karya">
    <title>Pilih Peran - Sistem Informasi Inventaris Aset SDN Budi Karya</title>

    <!-- Favicon -->
    <link rel="icon" type="image/webp" href="<?php echo htmlspecialchars($logoSrc); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($logoSrc); ?>">

    <!-- Google Fonts & Phosphor Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/bold/style.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css"/>

    <!-- Stylesheet Khusus Index / Role Selection -->
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/index.css'); ?>">
</head>
<body>

<div class="role-page-wrapper" style="--bg-school-image: url('<?php echo htmlspecialchars($bgSchoolUrl); ?>');">
    <main class="role-container">
        <!-- Logo & Header Branding -->
        <header class="role-brand-section">
            <div class="role-logo-wrapper">
                <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="Logo SDN Budi Karya" class="role-logo-img" width="76" height="76" loading="eager">
            </div>
            <h1 class="role-school-title">SDN Budikarya</h1>
            <p class="role-subtitle">Pilih peran untuk masuk ke Sistem Inventaris Aset Sekolah</p>
        </header>

        <!-- Kartu Pemilihan Peran (Administrator & Operator) -->
        <div class="role-cards-grid">
            <!-- 1. Administrator Card -->
            <a href="<?php echo htmlspecialchars($loginAdminUrl); ?>" class="role-card" id="card-admin" title="Masuk sebagai Administrator">
                <div class="role-card-icon-box">
                    <i class="ph-bold ph-shield-check"></i>
                </div>
                <h2 class="role-card-title">Administrator</h2>
                <p class="role-card-desc">Akses penuh pengelolaan data aset sekolah, inventaris ruangan, kelola akun, dan pelaporan.</p>
                <div class="role-card-action">
                    <span>Masuk sebagai Admin</span>
                    <i class="ph-bold ph-arrow-right"></i>
                </div>
            </a>

            <!-- 2. Operator Card -->
            <a href="<?php echo htmlspecialchars($loginOperatorUrl); ?>" class="role-card" id="card-operator" title="Masuk sebagai Operator">
                <div class="role-card-icon-box">
                    <i class="ph-bold ph-identification-card"></i>
                </div>
                <h2 class="role-card-title">Operator</h2>
                <p class="role-card-desc">Pencatatan inventaris barang, pemantauan kondisi ruang kelas, serta pemeliharaan berkala.</p>
                <div class="role-card-action">
                    <span>Masuk sebagai Operator</span>
                    <i class="ph-bold ph-arrow-right"></i>
                </div>
            </a>
        </div>

        <!-- Footer Note -->
        <footer class="role-footer-note">
            &copy; <?php echo date('Y'); ?> SDN Budikarya. Sistem Informasi Inventaris Aset.
        </footer>
    </main>
</div>

</body>
</html>
