<?php
/**
 * Halaman Dashboard - Sistem Informasi Inventaris Aset SDN Budi Karya
 * Lokasi: pages/dashboard.php
 */

$pageTitle = 'Dashboard - Sistem Informasi Inventaris Aset SDN Budi Karya';
$activeMenu = 'dashboard';

require_once __DIR__ . '/../includes/header.php';

$userRoleDisplay = ucfirst($_SESSION['role'] ?? 'Administrator');
?>

<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/dashboard.css'); ?>">

<div class="app-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-container">

            <div class="dash-page-header">
                <h1 class="dash-page-title">DASHBOARD</h1>
                <p class="dash-page-subtitle">Selamat datang <?php echo htmlspecialchars($userRoleDisplay); ?>, berikut adalah ringkasan data aset sekolah.</p>
            </div>

            <!-- Bagian 1: Kartu Ringkasan -->
            <div class="dash-summary-grid" id="summaryCards"></div>

            <!-- Bagian 2: Charts -->
            <div class="dash-charts-grid">
                <div class="dash-card">
                    <h2 class="dash-card-title">Sebaran Aset Berdasarkan Kategori KIB</h2>
                    <div class="dash-donut-wrapper">
                        <div class="dash-donut-chart-box">
                            <canvas id="kibDonutChart"></canvas>
                        </div>
                        <div class="dash-donut-legend" id="kibLegend"></div>
                    </div>
                </div>
                <div class="dash-card">
                    <h2 class="dash-card-title">Sebaran Aset Berdasarkan Lingkup Operator</h2>
                    <div class="dash-bar-chart-box">
                        <canvas id="operatorBarChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Bagian 3: Pemeliharaan & Aktivitas -->
            <div class="dash-bottom-grid">
                <div class="dash-card">
                    <div class="dash-card-title-row">
                        <h2 class="dash-card-title">Ringkasan Pemeliharaan</h2>
                        <a href="<?php echo htmlspecialchars($pagesPath . 'pemeliharaan.php'); ?>" class="dash-link-all">Lihat Semua</a>
                    </div>
                    <div class="dash-maint-grid" id="maintCards"></div>
                </div>
                <div class="dash-card">
                    <div class="dash-card-title-row">
                        <h2 class="dash-card-title">Aktivitas Terbaru</h2>
                        <button type="button" class="dash-link-all" id="btnAllActivities">Lihat Semua</button>
                    </div>
                    <div class="dash-activity-list" id="activityList"></div>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Modal Semua Aktivitas -->
<div class="dash-modal-overlay" id="dashActivitiesModal" aria-hidden="true">
    <div class="dash-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="dashActModalTitle">
        <div class="dash-modal-header">
            <div class="dash-modal-header-left">
                <span class="dash-modal-icon" aria-hidden="true">
                    <i class="ph-bold ph-clock-counter-clockwise"></i>
                </span>
                <div>
                    <h3 class="dash-modal-title" id="dashActModalTitle">Riwayat Aktivitas</h3>
                    <p class="dash-modal-subtitle" id="dashActModalCount">Memuat aktivitas...</p>
                </div>
            </div>
            <button type="button" class="dash-modal-close" id="btnCloseActModal" aria-label="Tutup">
                <i class="ph-bold ph-x"></i>
            </button>
        </div>

        <div class="dash-modal-search-bar">
            <div class="dash-modal-search-wrap">
                <i class="ph-bold ph-magnifying-glass dash-modal-search-icon" aria-hidden="true"></i>
                <input
                    type="text"
                    id="dashActSearchInput"
                    class="dash-modal-search-input"
                    placeholder="Cari aktivitas atau nama petugas..."
                    autocomplete="off"
                >
            </div>
        </div>

        <div class="dash-modal-body">
            <div class="dash-activity-list" id="dashAllActivitiesList"></div>
            <div class="dash-modal-load-more-wrap" id="dashActLoadMoreWrap" style="display:none;">
                <button type="button" class="dash-btn-load-more" id="btnActLoadMore">Muat Lebih Banyak</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="<?php echo htmlspecialchars($assetsPath . 'js/dashboard.js'); ?>"></script>

</body>
</html>
