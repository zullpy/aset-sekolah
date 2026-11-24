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
                    <h2 class="dash-card-title">Ringkasan Pemeliharaan</h2>
                    <div class="dash-maint-grid" id="maintCards"></div>
                </div>
                <div class="dash-card">
                    <div class="dash-card-title-row">
                        <h2 class="dash-card-title">Aktivitas Terbaru</h2>
                        <a href="#" class="dash-link-all">Lihat Semua</a>
                    </div>
                    <div class="dash-activity-list" id="activityList"></div>
                </div>
            </div>

        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="<?php echo htmlspecialchars($assetsPath . 'js/dashboard.js'); ?>"></script>

</body>
</html>
