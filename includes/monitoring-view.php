<?php
/**
 * Shared View - Monitoring Aset Sekolah (Kelas 1-6, Aset Umum, Semua)
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 * Dipanggil oleh pages/monitoring/*.php
 */

$monitoringLocations = [
    'semua'   => ['label' => 'Semua',     'file' => 'semua.php',     'title' => 'Monitoring - Semua Lokasi', 'subtitle' => 'Ringkasan gabungan hasil pemantauan aset dari seluruh lokasi penempatan (read-only)'],
    'kelas-1' => ['label' => 'Kelas 1',   'file' => 'kelas-1.php',   'title' => 'Monitoring Kelas 1',        'subtitle' => 'Pemantauan kondisi fisik dan jumlah aset yang ditempatkan di Kelas 1'],
    'kelas-2' => ['label' => 'Kelas 2',   'file' => 'kelas-2.php',   'title' => 'Monitoring Kelas 2',        'subtitle' => 'Pemantauan kondisi fisik dan jumlah aset yang ditempatkan di Kelas 2'],
    'kelas-3' => ['label' => 'Kelas 3',   'file' => 'kelas-3.php',   'title' => 'Monitoring Kelas 3',        'subtitle' => 'Pemantauan kondisi fisik dan jumlah aset yang ditempatkan di Kelas 3'],
    'kelas-4' => ['label' => 'Kelas 4',   'file' => 'kelas-4.php',   'title' => 'Monitoring Kelas 4',        'subtitle' => 'Pemantauan kondisi fisik dan jumlah aset yang ditempatkan di Kelas 4'],
    'kelas-5' => ['label' => 'Kelas 5',   'file' => 'kelas-5.php',   'title' => 'Monitoring Kelas 5',        'subtitle' => 'Pemantauan kondisi fisik dan jumlah aset yang ditempatkan di Kelas 5'],
    'kelas-6' => ['label' => 'Kelas 6',   'file' => 'kelas-6.php',   'title' => 'Monitoring Kelas 6',        'subtitle' => 'Pemantauan kondisi fisik dan jumlah aset yang ditempatkan di Kelas 6'],
    'umum'    => ['label' => 'Aset Umum', 'file' => 'aset-umum.php', 'title' => 'Monitoring Aset Umum',      'subtitle' => 'Pemantauan aset umum (perpustakaan, kantor, lapangan, UKS, dan fasilitas bersama)'],
];

$monitoringPenempatan = $monitoringPenempatan ?? 'kelas-1';
if ($monitoringPenempatan === 'aset-umum') {
    $monitoringPenempatan = 'umum';
}
if (!isset($monitoringLocations[$monitoringPenempatan])) {
    $monitoringPenempatan = 'kelas-1';
}

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/aset-kelas-helper.php';
require_auth(['administrator', 'operator']);

$currentUser     = current_user();
$assignedKelasId = (strtolower((string) ($currentUser['role'] ?? '')) === 'operator')
    ? detect_operator_kelas_id($currentUser)
    : null;

if ($assignedKelasId !== null) {
    $expectedPenempatan = 'kelas-' . $assignedKelasId;
    if ($monitoringPenempatan !== $expectedPenempatan) {
        $targetFile = $monitoringLocations[$expectedPenempatan]['file'] ?? ('kelas-' . $assignedKelasId . '.php');
        header('Location: ' . $targetFile);
        exit;
    }
}

$locMeta       = $monitoringLocations[$monitoringPenempatan];
$pageTitle     = $locMeta['title'] . ' - Sistem Informasi Inventaris Aset SDN Budi Karya';
$activeMenu    = 'monitoring';
$activeSubmenu = ($monitoringPenempatan === 'umum') ? 'aset-umum' : $monitoringPenempatan;

require_once __DIR__ . '/header.php';
$rawNamaLengkap  = trim((string) ($currentUser['nama_lengkap'] ?? ''));
$rawUsername     = trim((string) ($currentUser['username'] ?? ''));
$connFile        = dirname(__DIR__) . '/config/conn.php';
if (!empty($currentUser['id']) && file_exists($connFile)) {
    require_once $connFile;
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmtActor = $pdo->prepare("SELECT nama_lengkap, username FROM users WHERE id = ? LIMIT 1");
        $stmtActor->execute([(int) $currentUser['id']]);
        if ($actorRow = $stmtActor->fetch(PDO::FETCH_ASSOC)) {
            $dbNama = trim((string) ($actorRow['nama_lengkap'] ?? ''));
            $dbUser = trim((string) ($actorRow['username'] ?? ''));
            if ($dbNama !== '') {
                $rawNamaLengkap = $dbNama;
            }
            if ($dbUser !== '') {
                $rawUsername = $dbUser;
            }
        }
    }
}
$currentUserName = $rawNamaLengkap !== '' ? $rawNamaLengkap : ($rawUsername !== '' ? $rawUsername : 'Administrator');
$isReadonlyInit  = ($monitoringPenempatan === 'semua');
$isClassInit     = str_starts_with($monitoringPenempatan, 'kelas-');
?>

<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/dashboard.css'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/kib.css'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/monitoring.css'); ?>">

<div class="app-layout">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-container">

            <!-- Header Halaman Monitoring -->
            <div class="kib-page-header">
                <div class="kib-header-left">
                    <h1 class="kib-page-title" id="monPageTitle"><?php echo htmlspecialchars($locMeta['title']); ?></h1>
                    <p class="kib-page-subtitle" id="monPageSubtitle"><?php echo htmlspecialchars($locMeta['subtitle']); ?></p>
                </div>
                <div class="kib-header-actions">
                    <button type="button" class="btn-kib-filter" id="btnMonFilter" title="Filter Data" aria-label="Filter">
                        <i class="ph-bold ph-faders"></i>
                    </button>
                    <button
                        type="button"
                        class="btn-kib-add"
                        id="btnAddMonitoring"
                        style="<?php echo $isReadonlyInit ? 'display:none;' : ''; ?>"
                    >
                        <i class="ph-bold ph-plus"></i>
                        <span>Tambah Monitoring</span>
                    </button>
                </div>
            </div>

            <?php if (strtolower((string) ($currentUser['role'] ?? '')) !== 'operator'): ?>
            <!-- Tabs Navigasi Lokasi untuk Administrator -->
            <div class="mon-location-tabs" role="tablist" aria-label="Pilih Lokasi Monitoring">
                <a href="<?php echo htmlspecialchars($monitoringPath . 'semua.php'); ?>" class="mon-tab-pill <?php echo ($monitoringPenempatan === 'semua') ? 'is-active' : ''; ?>">
                    <i class="ph-bold ph-squares-four"></i>
                    <span>Semua Lokasi</span>
                </a>
                <?php for ($k = 1; $k <= 6; $k++): ?>
                <?php $locKey = 'kelas-' . $k; ?>
                <a href="<?php echo htmlspecialchars($monitoringPath . 'kelas-' . $k . '.php'); ?>" class="mon-tab-pill <?php echo ($monitoringPenempatan === $locKey) ? 'is-active' : ''; ?>">
                    <i class="ph-bold ph-chalkboard-teacher"></i>
                    <span>Kelas <?php echo $k; ?></span>
                </a>
                <?php endfor; ?>
                <a href="<?php echo htmlspecialchars($monitoringPath . 'aset-umum.php'); ?>" class="mon-tab-pill <?php echo ($monitoringPenempatan === 'umum') ? 'is-active' : ''; ?>">
                    <i class="ph-bold ph-buildings"></i>
                    <span>Aset Umum</span>
                </a>
            </div>
            <?php endif; ?>

            <!-- Banner Mode Read-Only untuk tab Semua -->
            <div class="mon-readonly-banner <?php echo $isReadonlyInit ? 'show' : ''; ?>" id="monReadonlyBanner">
                <i class="ph-bold ph-info"></i>
                <span>Mode lihat saja (read-only). Pilih tab <strong>Kelas 1 - Kelas 6</strong> atau <strong>Aset Umum</strong> untuk menambah, mengubah, atau menghapus catatan monitoring.</span>
            </div>

            <!-- 5 Kartu Ringkasan Kondisi -->
            <section class="dash-summary-grid mon-summary-grid" id="monSummaryCards" aria-label="Ringkasan Kondisi Aset">
                <div class="dash-stat-card dash-stat-card--blue">
                    <div class="dash-stat-top">
                        <div class="dash-stat-icon"><i class="ph-bold ph-chart-bar"></i></div>
                        <span class="dash-stat-label">Total Aset</span>
                    </div>
                    <div class="dash-stat-value">0</div>
                    <div class="dash-stat-desc">Memuat ringkasan...</div>
                </div>
                <div class="dash-stat-card dash-stat-card--green">
                    <div class="dash-stat-top">
                        <div class="dash-stat-icon"><i class="ph-bold ph-check-circle"></i></div>
                        <span class="dash-stat-label">Baik</span>
                    </div>
                    <div class="dash-stat-value">0</div>
                    <div class="dash-stat-desc">(0%) dari total unit termonitor</div>
                </div>
                <div class="dash-stat-card dash-stat-card--orange">
                    <div class="dash-stat-top">
                        <div class="dash-stat-icon"><i class="ph-bold ph-warning"></i></div>
                        <span class="dash-stat-label">Rusak Ringan</span>
                    </div>
                    <div class="dash-stat-value">0</div>
                    <div class="dash-stat-desc">(0%) dari total unit termonitor</div>
                </div>
                <div class="dash-stat-card dash-stat-card--red">
                    <div class="dash-stat-top">
                        <div class="dash-stat-icon"><i class="ph-bold ph-x-circle"></i></div>
                        <span class="dash-stat-label">Rusak Berat</span>
                    </div>
                    <div class="dash-stat-value">0</div>
                    <div class="dash-stat-desc">(0%) dari total unit termonitor</div>
                </div>
                <div class="dash-stat-card dash-stat-card--gray">
                    <div class="dash-stat-top">
                        <div class="dash-stat-icon"><i class="ph-bold ph-minus-circle"></i></div>
                        <span class="dash-stat-label">Hilang</span>
                    </div>
                    <div class="dash-stat-value">0</div>
                    <div class="dash-stat-desc">(0%) dari total unit termonitor</div>
                </div>
            </section>

            <!-- Card Tabel Monitoring -->
            <div class="kib-card">
                <div class="kib-toolbar">
                    <div class="kib-search-wrapper">
                        <i class="ph-bold ph-magnifying-glass kib-search-icon"></i>
                        <input
                            type="text"
                            id="monSearchInput"
                            class="kib-search-input"
                            placeholder="Cari berdasarkan kode, nama aset, jenis, petugas..."
                            autocomplete="off"
                            aria-label="Cari Data Monitoring"
                        >
                        <button
                            type="button"
                            id="monSearchClear"
                            class="kib-search-clear"
                            title="Hapus pencarian"
                            aria-label="Hapus pencarian"
                        >
                            <i class="ph-bold ph-x"></i>
                        </button>
                    </div>

                    <div class="kib-meta-count">
                        Menampilkan <span id="monCountCurrent">0</span> dari <span id="monCountTotal">0</span> catatan
                    </div>
                </div>

                <div class="kib-table-wrapper">
                    <table class="kib-table">
                        <thead>
                            <tr>
                                <th class="col-no">No</th>
                                <th>Kode Aset</th>
                                <th>Nama Aset</th>
                                <th class="th-center">KIB</th>
                                <th>Jenis Aset</th>
                                <th class="th-center" id="monThLokasi" style="<?php echo $isReadonlyInit ? '' : 'display:none;'; ?>">Lokasi</th>
                                <th class="th-center">Jumlah</th>
                                <th class="th-center">Kondisi</th>
                                <th class="th-center">Tanggal Update</th>
                                <th>Petugas</th>
                                <th class="th-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="monTableBody">
                            <!-- Di-render oleh MonitoringEngine -->
                        </tbody>
                    </table>
                </div>

                <div class="kib-table-footer">
                    <div class="kib-range-wrapper">
                        <span class="range-label">Range</span>
                        <div class="range-options" id="monRangeOptions">
                            <button type="button" class="range-btn" data-range="5">5</button>
                            <button type="button" class="range-btn" data-range="10">10</button>
                            <button type="button" class="range-btn" data-range="25">25</button>
                            <button type="button" class="range-btn" data-range="50">50</button>
                            <button type="button" class="range-btn active" data-range="all">all</button>
                        </div>
                    </div>

                    <div class="kib-pagination" id="monPagination"></div>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Modal Filter Monitoring (tanpa field lokasi) -->
<div class="kib-modal-overlay" id="monModalFilter" aria-hidden="true">
    <div class="kib-modal-dialog kib-modal-dialog--filter" role="dialog" aria-modal="true" aria-labelledby="monFilterModalTitle">
        <div class="kib-modal-header">
            <div class="kib-modal-header-left">
                <span class="kib-modal-icon-green" aria-hidden="true">
                    <i class="ph-bold ph-faders"></i>
                </span>
                <h3 class="kib-modal-title" id="monFilterModalTitle">Filter Data</h3>
            </div>
            <button type="button" class="kib-modal-close" data-modal-close aria-label="Tutup">
                <i class="ph-bold ph-x"></i>
            </button>
        </div>

        <div class="kib-modal-body">
            <div class="mon-filter-grid">
                <div class="kib-form-group mon-filter-full">
                    <label class="kib-label" for="monFilterKeyword">Kata Kunci</label>
                    <div class="kib-input-icon-wrapper">
                        <i class="ph-bold ph-magnifying-glass kib-input-icon"></i>
                        <input
                            type="text"
                            id="monFilterKeyword"
                            class="kib-input kib-input--has-icon"
                            placeholder="Masukkan kata kunci ..."
                            autocomplete="off"
                        >
                    </div>
                </div>

                <div class="kib-form-group">
                    <label class="kib-label" for="monFilterKib">KIB</label>
                    <select id="monFilterKib" class="kib-select">
                        <option value="">Semua KIB</option>
                        <?php if ($isClassInit): ?>
                            <option value="kib-b">KIB B</option>
                            <option value="kib-e">KIB E</option>
                        <?php else: ?>
                            <option value="kib-a">KIB A</option>
                            <option value="kib-b">KIB B</option>
                            <option value="kib-c">KIB C</option>
                            <option value="kib-d">KIB D</option>
                            <option value="kib-e">KIB E</option>
                            <option value="kib-f">KIB F</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="kib-form-group">
                    <label class="kib-label" for="monFilterJenis">Jenis Aset</label>
                    <select id="monFilterJenis" class="kib-select">
                        <option value="">Semua Jenis Aset</option>
                    </select>
                </div>

                <div class="kib-form-group">
                    <label class="kib-label" for="monFilterKondisi">Kondisi Aset</label>
                    <select id="monFilterKondisi" class="kib-select">
                        <option value="">Semua Kondisi</option>
                        <option value="Baik">Baik</option>
                        <option value="Rusak Ringan">Rusak Ringan</option>
                        <option value="Rusak Berat">Rusak Berat</option>
                        <option value="Hilang">Hilang</option>
                    </select>
                </div>

                <div class="kib-form-group">
                    <label class="kib-label" for="monFilterTglUpdate">Tanggal Update</label>
                    <input type="date" id="monFilterTglUpdate" class="kib-input">
                </div>
            </div>
        </div>

        <div class="kib-modal-footer kib-modal-footer--space-between">
            <button type="button" class="btn-kib-modal-reset" id="btnMonResetFilter">
                <i class="ph-bold ph-arrows-counter-clockwise"></i>
                <span>Reset</span>
            </button>
            <button type="button" class="btn-kib-modal-apply" id="btnMonApplyFilter">
                Terapkan
            </button>
        </div>
    </div>
</div>

<!-- Modal Tambah / Edit Monitoring -->
<div class="kib-modal-overlay" id="monModalForm" aria-hidden="true">
    <div class="kib-modal-dialog kib-modal-dialog--form" role="dialog" aria-modal="true" aria-labelledby="monFormTitle">
        <div class="kib-modal-header">
            <div class="kib-modal-header-left">
                <span class="kib-modal-icon-maroon" aria-hidden="true">
                    <i class="ph-bold ph-desktop"></i>
                </span>
                <h3 class="kib-modal-title" id="monFormTitle"><?php echo $isClassInit ? ('Tambah Monitoring - ' . htmlspecialchars($locMeta['label'])) : 'Tambah Monitoring'; ?></h3>
            </div>
            <button type="button" class="kib-modal-close" data-modal-close aria-label="Tutup">
                <i class="ph-bold ph-x"></i>
            </button>
        </div>
        <form id="monAssetForm" novalidate autocomplete="off">
            <div class="kib-modal-body">
                <div class="kib-form-grid">
                    <div class="kib-form-group" style="--field-span: 4;">
                        <label class="kib-label" for="monFieldLokasiDisplay">Lokasi Penempatan</label>
                        <input type="text" id="monFieldLokasiDisplay" class="kib-input kib-input--readonly" readonly tabindex="-1">
                    </div>
                    <div class="kib-form-group" style="--field-span: 8;">
                        <label class="kib-label required" for="monFieldAsetSearch">Nama Aset</label>
                        <div class="mon-aset-combobox" id="monAsetCombobox">
                            <input type="hidden" id="monFieldAset" value="">
                            <div class="mon-aset-input-wrap">
                                <i class="ph-bold ph-magnifying-glass mon-aset-search-icon" aria-hidden="true"></i>
                                <input
                                    type="text"
                                    id="monFieldAsetSearch"
                                    class="kib-input mon-aset-search-input"
                                    placeholder="<?php echo $isClassInit ? 'Cari aset KIB B / KIB E (nama, kode, jenis)...' : 'Cari aset KIB A-F (nama, kode, jenis)...'; ?>"
                                    autocomplete="off"
                                    role="combobox"
                                    aria-autocomplete="list"
                                    aria-expanded="false"
                                    aria-controls="monAsetDropdown"
                                >
                                <button
                                    type="button"
                                    id="monFieldAsetClear"
                                    class="mon-aset-clear-btn"
                                    title="Hapus pilihan"
                                    aria-label="Hapus pilihan"
                                    tabindex="-1"
                                >
                                    <i class="ph-bold ph-x"></i>
                                </button>
                                <button
                                    type="button"
                                    id="monFieldAsetToggle"
                                    class="mon-aset-toggle-btn"
                                    title="Tampilkan daftar aset"
                                    aria-label="Tampilkan daftar aset"
                                    tabindex="-1"
                                >
                                    <i class="ph-bold ph-caret-down"></i>
                                </button>
                            </div>
                            <div class="mon-aset-dropdown" id="monAsetDropdown" role="listbox" aria-label="Daftar Nama Aset"></div>
                        </div>
                        <span class="kib-field-error" id="errMon_aset_key"></span>
                        <div class="mon-empty-asset-hint" id="monEmptyAssetHint">
                            Belum ada aset terdaftar pada Data Aset. Silakan tambahkan aset terlebih dahulu di halaman <a href="<?php echo htmlspecialchars($kibPath . 'kib-b.php'); ?>">Data Aset (KIB B / KIB E)</a>.
                        </div>
                    </div>

                    <div class="kib-form-group" style="--field-span: 4;">
                        <label class="kib-label" for="monFieldKibDisplay">Kategori KIB</label>
                        <input type="text" id="monFieldKibDisplay" class="kib-input kib-input--readonly" readonly tabindex="-1" placeholder="Otomatis">
                    </div>
                    <div class="kib-form-group" style="--field-span: 4;">
                        <label class="kib-label" for="monFieldKodeDisplay">Kode Aset</label>
                        <input type="text" id="monFieldKodeDisplay" class="kib-input kib-input--readonly" readonly tabindex="-1" placeholder="Otomatis">
                    </div>
                    <div class="kib-form-group" style="--field-span: 4;">
                        <label class="kib-label" for="monFieldJenisDisplay">Jenis Aset</label>
                        <input type="text" id="monFieldJenisDisplay" class="kib-input kib-input--readonly" readonly tabindex="-1" placeholder="Otomatis">
                    </div>

                    <div class="kib-form-group" style="--field-span: 3;">
                        <label class="kib-label required" for="monFieldJumlah">Jumlah</label>
                        <input type="number" id="monFieldJumlah" class="kib-input" min="1" value="1" inputmode="numeric" required>
                        <span class="mon-quota-hint" id="monFieldJumlahHint"></span>
                        <span class="kib-field-error" id="errMon_jumlah"></span>
                    </div>
                    <div class="kib-form-group" style="--field-span: 3;">
                        <label class="kib-label required" for="monFieldKondisi">Kondisi</label>
                        <div class="kib-select-wrapper">
                            <select id="monFieldKondisi" class="kib-select" required>
                                <option value="Baik">Baik</option>
                                <option value="Rusak Ringan">Rusak Ringan</option>
                                <option value="Rusak Berat">Rusak Berat</option>
                                <option value="Hilang">Hilang</option>
                            </select>
                            <i class="ph-bold ph-caret-down kib-select-chevron" aria-hidden="true"></i>
                        </div>
                        <span class="kib-field-error" id="errMon_kondisi"></span>
                    </div>
                    <div class="kib-form-group" style="--field-span: 3;">
                        <label class="kib-label required" for="monFieldTglUpdate">Tanggal Update</label>
                        <input type="date" id="monFieldTglUpdate" class="kib-input" required>
                        <span class="kib-field-error" id="errMon_tgl_update"></span>
                    </div>
                    <div class="kib-form-group" style="--field-span: 3;">
                        <label class="kib-label" for="monFieldPetugas">Petugas</label>
                        <input type="text" id="monFieldPetugas" class="kib-input kib-input--readonly" value="<?php echo htmlspecialchars($currentUserName); ?>" readonly tabindex="-1">
                    </div>

                    <div class="kib-form-group" style="--field-span: 12;">
                        <label class="kib-label" for="monFieldCatatan">Catatan / Keterangan Kondisi</label>
                        <textarea id="monFieldCatatan" class="kib-textarea" rows="3" placeholder="Masukkan catatan kondisi fisik atau tindak lanjut yang diperlukan"></textarea>
                    </div>
                </div>
                <div class="kib-modal-submit-row">
                    <button type="submit" class="btn-kib-modal-submit" id="btnMonSubmitForm">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo htmlspecialchars($assetsPath . 'js/monitoring.js'); ?>"></script>
<script>
window.__monitoringEngine = new MonitoringEngine({
    initialPenempatan: <?php echo json_encode($monitoringPenempatan); ?>,
    baseMonitoringUrl: <?php echo json_encode($monitoringPath); ?>,
    apiUrl: '../../includes/ajax-monitoring.php',
    kibBaseUrl: <?php echo json_encode($kibPath); ?>,
    asetKelasUrl: <?php echo json_encode($pagesPath . 'aset-kelas.php'); ?>,
    currentUserName: <?php echo json_encode($currentUserName); ?>,
    defaultRange: 'all'
});
</script>

</body>
</html>
