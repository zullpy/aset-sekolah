<?php
/**
 * Data Aset - KIB D (Jalan, Irigasi & Jaringan)
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

$pageTitle = 'Data Aset - KIB D - Sistem Informasi Inventaris Aset SDN Budi Karya';
$activeMenu = 'data-aset';
$activeSubmenu = 'kib-d';

require_once __DIR__ . '/../../includes/header.php';
?>

<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/dashboard.css'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/kib.css'); ?>">

<div class="app-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-container">

            <!-- Header Halaman KIB D -->
            <div class="kib-page-header">
                <div class="kib-header-left">
                    <h1 class="kib-page-title">DATA ASET - KIB D</h1>
                    <p class="kib-page-subtitle">Data aset berupa jalan, irigasi dan jaringan yang dimiliki sekolah</p>
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
                            placeholder="Cari berdasarkan kode, nama aset, konstruksi..."
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

                <!-- Tabel Aset KIB D -->
                <div class="kib-table-wrapper">
                    <table class="kib-table">
                        <thead>
                            <tr>
                                <th class="col-no">No</th>
                                <th>Kode Aset</th>
                                <th>Nama Aset</th>
                                <th class="th-center">No Registrasi</th>
                                <th>Konstruksi</th>
                                <th class="th-center">Ukuran</th>
                                <th class="th-center">Luas (m²)</th>
                                <th class="th-center">Tahun Perolehan</th>
                                <th>No. &amp; Tanggal Dokumen</th>
                                <th class="th-center">Harga</th>
                                <th class="th-center">Sumber</th>
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
    kibId: 'kib-d',
    kibName: 'KIB D (Jalan, Irigasi & Jaringan)',
    kibTitle: 'DATA ASET - KIB D',
    subtitle: 'Data aset berupa jalan, irigasi dan jaringan yang dimiliki sekolah',
    hasJenis: false,
    defaultRange: 'all',
    columns: [
        { key: 'kode' },
        { key: 'nama' },
        { key: 'no_reg', align: 'center' },
        { key: 'konstruksi' },
        { key: 'ukuran', align: 'center' },
        { key: 'luas', align: 'center' },
        { key: 'tahun', align: 'center' },
        { key: 'dokumen' },
        { key: 'harga', align: 'center' },
        { key: 'sumber', align: 'center' },
        { key: 'lampiran', align: 'center' }
    ],
    formFields: [
        { name: 'kode', label: 'Kode Aset', span: 4, readonly: true, placeholder: 'Otomatis' },
        { name: 'nama', label: 'Nama Aset', span: 4, required: true, placeholder: 'Masukkan nama aset' },
        { name: 'no_reg', label: 'No Registrasi', span: 4, readonly: true, placeholder: 'Otomatis' },
        { name: 'konstruksi', label: 'Konstruksi', span: 5, required: true, placeholder: 'ex : Paving Blok / Semen' },
        { name: 'ukuran', label: 'Ukuran (m)', span: 4, required: true, placeholder: 'ex : 15 × 0,5' },
        { name: 'luas', label: 'Luas (m²)', span: 3, placeholder: 'ex : 5 / 4,5' },
        { name: 'dokumen', label: 'No & Tanggal Dokumen', span: 5, required: true, placeholder: 'Masukkan nomor & tanggal dokumen' },
        { name: 'tahun', label: 'Tahun Perolehan', span: 4, required: true, placeholder: 'ex : 2024' },
        { name: 'sumber', label: 'Sumber', span: 3, required: true, type: 'select', default: 'Hibah', options: ['Hibah', 'Pembelian', 'APBN', 'APBD', 'BOS'] },
        { name: 'harga', label: 'Harga (Rp.)', span: 4, type: 'currency', required: true, placeholder: 'ex : 80.000.000' },
        { name: 'lampiran', label: 'Lampiran', span: 4, type: 'file' }
    ],
    initialData: [
        {
            id: 1,
            kode: 'KIB-D/2021/001',
            nama: 'Jalan Paving Blok Halaman',
            no_reg: '000001',
            konstruksi: 'Paving Blok',
            ukuran: '45 × 2 m',
            luas: '90',
            tahun: '2021',
            dokumen: '600/012/Disdik/2021, 14 Juni 2021',
            harga: 'Rp 28.500.000',
            sumber: 'APBD',
            lampiran: 'SPK_Paving_2021.pdf'
        },
        {
            id: 2,
            kode: 'KIB-D/2020/002',
            nama: 'Jaringan Pipa Air Bersih & Sumur Bor',
            no_reg: '000001',
            konstruksi: 'Pipa PVC & Pompa',
            ukuran: '120 m',
            luas: '-',
            tahun: '2020',
            dokumen: '600/088/Sarpras/2020, 20 Des 2020',
            harga: 'Rp 14.200.000',
            sumber: 'BOS',
            lampiran: 'Dokumen_Sumur_2020.pdf'
        },
        {
            id: 3,
            kode: 'KIB-D/2019/003',
            nama: 'Parit / Drainase Terbuka',
            no_reg: '000001',
            konstruksi: 'Pasangan Batu Kali',
            ukuran: '60 × 0.4 m',
            luas: '24',
            tahun: '2019',
            dokumen: '600/005/Disdik/2019, 10 Mei 2019',
            harga: 'Rp 9.800.000',
            sumber: 'APBD',
            lampiran: 'BA_Parit_2019.pdf'
        },
        {
            id: 4,
            kode: 'KIB-D/2018/004',
            nama: 'Jaringan Kabel Listrik Terbuka & Panel',
            no_reg: '000001',
            konstruksi: 'Kabel Tembaga NYM',
            ukuran: '80 m',
            luas: '-',
            tahun: '2018',
            dokumen: '600/001/Sarpras/2018, 05 Jan 2018',
            harga: 'Rp 6.500.000',
            sumber: 'BOS',
            lampiran: 'Nota_Kabel_2018.pdf'
        }
    ]
});
</script>

</body>
</html>
