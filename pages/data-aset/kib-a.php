<?php
/**
 * Data Aset - KIB A (Tanah)
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

$pageTitle = 'Data Aset - KIB A - Sistem Informasi Inventaris Aset SDN Budi Karya';
$activeMenu = 'data-aset';
$activeSubmenu = 'kib-a';

require_once __DIR__ . '/../../includes/auth.php';
require_auth('administrator');

require_once __DIR__ . '/../../includes/header.php';
?>

<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/dashboard.css'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/kib.css'); ?>">

<div class="app-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-container">

            <!-- Header Halaman KIB A -->
            <div class="kib-page-header">
                <div class="kib-header-left">
                    <h1 class="kib-page-title">DATA ASET - KIB A</h1>
                    <p class="kib-page-subtitle">Data aset berupa tanah yang dimiliki atau dikuasai sekolah</p>
                </div>
                <div class="kib-header-actions">
                    <button type="button" class="btn-kib-filter" id="btnFilter" title="Filter Data" aria-label="Filter">
                        <i class="ph-bold ph-faders"></i>
                    </button>
                    <button type="button" class="btn-kib-add" id="btnAddAsset">
                        <i class="ph-bold ph-plus"></i>
                        <span>Tambah Aset</span>
                    </button>
                </div>
            </div>

            <!-- Card Utama Konten -->
            <div class="kib-card">

                <!-- Toolbar: Search Bar Real-Time & Meta Count -->
                <div class="kib-toolbar">
                    <div class="kib-search-wrapper">
                        <i class="ph-bold ph-magnifying-glass kib-search-icon"></i>
                        <input
                            type="text"
                            id="kibSearchInput"
                            class="kib-search-input"
                            placeholder="Cari berdasarkan kode, nama, lokasi aset..."
                            autocomplete="off"
                            aria-label="Cari Aset"
                        >
                        <button
                            type="button"
                            id="kibSearchClear"
                            class="kib-search-clear"
                            title="Hapus pencarian"
                            aria-label="Hapus pencarian"
                        >
                            <i class="ph-bold ph-x"></i>
                        </button>
                    </div>

                    <div class="kib-meta-count">
                        Menampilkan <span id="countCurrent">0</span> dari <span id="countTotal">0</span> aset
                    </div>
                </div>

                <!-- Tabel Aset KIB A -->
                <div class="kib-table-wrapper">
                    <table class="kib-table">
                        <thead>
                            <tr>
                                <th class="col-no">No</th>
                                <th>Kode Aset</th>
                                <th>Nama Aset</th>
                                <th class="th-center">No Registrasi</th>
                                <th class="th-center">Luas (m²)</th>
                                <th class="th-center">Tahun Perolehan</th>
                                <th>Lokasi Aset</th>
                                <th>Status Tanah</th>
                                <th>Sertifikat</th>
                                <th>Penggunaan</th>
                                <th class="th-center">Sumber</th>
                                <th class="th-center">Harga</th>
                                <th class="th-center">Lampiran</th>
                                <th class="th-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="kibTableBody">
                            <!-- Baris tabel di-render dinamis oleh JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Footer Tabel & Pagination -->
                <div class="kib-table-footer">
                    <div class="kib-range-wrapper">
                        <span class="range-label">Range</span>
                        <div class="range-options" id="kibRangeOptions">
                            <button type="button" class="range-btn" data-range="5">5</button>
                            <button type="button" class="range-btn" data-range="10">10</button>
                            <button type="button" class="range-btn" data-range="25">25</button>
                            <button type="button" class="range-btn" data-range="50">50</button>
                            <button type="button" class="range-btn active" data-range="all">all</button>
                        </div>
                    </div>

                    <div class="kib-pagination" id="kibPagination">
                        <!-- Tombol pagination di-render dinamis oleh JavaScript -->
                    </div>
                </div>

            </div>

        </div>
    </main>
</div>

<!-- SweetAlert2 (jika belum dimuat di header) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?php echo htmlspecialchars($assetsPath . 'js/kib-engine.js'); ?>"></script>

<script>
window.__kibEngine = new KibEngine({
    kibId: 'kib-a',
    kibName: 'KIB A (Tanah)',
    kibTitle: 'DATA ASET - KIB A',
    subtitle: 'Data aset berupa tanah yang dimiliki atau dikuasai sekolah',
    hasJenis: false,
    defaultRange: 'all',
    columns: [
        { key: 'kode' },
        { key: 'nama' },
        { key: 'no_reg', align: 'center' },
        { key: 'luas', align: 'center' },
        { key: 'tahun', align: 'center' },
        { key: 'lokasi' },
        { key: 'status_tanah' },
        { key: 'sertifikat' },
        { key: 'penggunaan' },
        { key: 'sumber', align: 'center' },
        { key: 'harga', align: 'center' },
        { key: 'lampiran', align: 'center' }
    ],
    formFields: [
        { name: 'kode', label: 'Kode Aset', span: 4, readonly: true, placeholder: 'Otomatis' },
        { name: 'nama', label: 'Nama Aset', span: 4, required: true, placeholder: 'Masukkan nama aset' },
        { name: 'no_reg', label: 'No Registrasi', span: 4, readonly: true, placeholder: 'Otomatis' },
        { name: 'luas', label: 'Luas (m²)', span: 2, required: true, placeholder: 'ex : 5 / 4,5' },
        { name: 'tahun', label: 'Tahun Perolehan', span: 3, required: true, placeholder: 'ex : 2024' },
        { name: 'lokasi', label: 'Lokasi Aset', span: 7, required: true, placeholder: 'Masukkan lokasi aset' },
        { name: 'status_tanah', label: 'Status Tanah', span: 4, required: true, type: 'select', default: 'Hak Guna Bangunan', options: ['Hak Milik', 'Hak Guna Bangunan', 'Hak Pakai', 'Hak Pengelolaan'] },
        { name: 'sertifikat', label: 'No Sertifikat', span: 4, required: true, placeholder: 'Masukkan nomor sertifikat' },
        { name: 'penggunaan', label: 'Penggunaan', span: 4, required: true, placeholder: 'Masukkan jenis penggunaan' },
        { name: 'sumber', label: 'Sumber', span: 4, required: true, type: 'select', default: 'Hibah', options: ['Hibah', 'Pembelian', 'APBN', 'APBD', 'BOS'] },
        { name: 'harga', label: 'Harga (Rp.)', span: 4, type: 'currency', required: true, placeholder: 'ex : 80.000.000' },
        { name: 'lampiran', label: 'Lampiran', span: 4, type: 'file' }
    ]
});
</script>

</body>
</html>
