<?php
/**
 * Data Aset - KIB F (Konstruksi Dalam Pengerjaan)
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

$pageTitle = 'Data Aset - KIB F - Sistem Informasi Inventaris Aset SDN Budi Karya';
$activeMenu = 'data-aset';
$activeSubmenu = 'kib-f';

require_once __DIR__ . '/../../includes/header.php';
?>

<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/dashboard.css'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/kib.css'); ?>">

<div class="app-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-container">

            <!-- Header Halaman KIB F -->
            <div class="kib-page-header">
                <div class="kib-header-left">
                    <h1 class="kib-page-title">DATA ASET - KIB F</h1>
                    <p class="kib-page-subtitle">Data aset dalam kelompok Konstruksi Dalam Pengerjaan (KDP) yang dimiliki sekolah</p>
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
                            placeholder="Cari berdasarkan kode, jenis, nama proyek..."
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

                <!-- Tabel Aset KIB F dengan Grouped Header -->
                <div class="kib-table-wrapper">
                    <table class="kib-table">
                        <thead>
                            <tr>
                                <th rowspan="2" class="col-no">No</th>
                                <th rowspan="2">Kode Aset</th>
                                <th rowspan="2">Jenis Aset</th>
                                <th rowspan="2">Nama Aset</th>
                                <th rowspan="2" class="th-center">No Registrasi</th>
                                <th colspan="2" class="th-center">Konstruksi Bangunan</th>
                                <th rowspan="2" class="th-center">Luas (m²)</th>
                                <th rowspan="2">Letak Bangunan</th>
                                <th rowspan="2">Tanggal &amp; No. Dokumen</th>
                                <th rowspan="2" class="th-center">Tanggal Mulai</th>
                                <th rowspan="2">Status Tanah</th>
                                <th rowspan="2" class="th-center">Biaya</th>
                                <th rowspan="2" class="th-center">Persentase Fisik</th>
                                <th rowspan="2" class="th-center">Status Pengerjaan</th>
                                <th rowspan="2" class="th-center">Sumber</th>
                                <th rowspan="2">Keterangan</th>
                                <th rowspan="2" class="th-center">Lampiran</th>
                                <th rowspan="2" class="th-center">Aksi</th>
                            </tr>
                            <tr>
                                <th class="th-center">Tingkat</th>
                                <th class="th-center">Beton</th>
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
    kibId: 'kib-f',
    kibName: 'KIB F (Konstruksi Dalam Pengerjaan)',
    kibTitle: 'DATA ASET - KIB F',
    subtitle: 'Data aset dalam kelompok Konstruksi Dalam Pengerjaan (KDP) yang dimiliki sekolah',
    hasJenis: true,
    jenisLabel: 'Jenis Aset',
    defaultRange: 'all',
    columns: [
        { key: 'kode' },
        { key: 'jenis' },
        { key: 'nama' },
        { key: 'no_reg', align: 'center' },
        { key: 'tingkat', align: 'center' },
        { key: 'beton', align: 'center' },
        { key: 'luas', align: 'center' },
        { key: 'letak_bangunan' },
        { key: 'dokumen' },
        { key: 'tgl_mulai', align: 'center' },
        { key: 'status_tanah' },
        { key: 'biaya', align: 'center' },
        { key: 'persen_fisik', align: 'center' },
        {
            key: 'status_pengerjaan',
            align: 'center',
            format: function(val) {
                var safeVal = val || '-';
                var cls = 'badge-kib--progress';
                if (safeVal.toLowerCase().includes('selesai') || safeVal.includes('100%')) {
                    cls = 'badge-kib--done';
                }
                return '<span class="badge-kib ' + cls + '">' + safeVal + '</span>';
            }
        },
        { key: 'sumber', align: 'center' },
        { key: 'keterangan' },
        { key: 'lampiran', align: 'center' }
    ],
    formFields: [
        { name: 'kode', label: 'Kode Aset', span: 4, readonly: true, placeholder: 'Otomatis' },
        { name: 'jenis', label: 'Jenis Aset', span: 4, required: true, type: 'select', default: 'Alat Peraga', options: ['Alat Peraga', 'Pembangunan', 'Renovasi', 'Penambahan Fasilitas', 'Lainnya'] },
        { name: 'nama', label: 'Nama Aset', span: 4, required: true, placeholder: 'Masukkan nama aset' },
        { name: 'tingkat', label: 'Tingkat / Tidak', span: 4, required: true, type: 'select', default: 'Tingkat', options: ['Tingkat', 'Tidak Bertingkat'] },
        { name: 'beton', label: 'Beton / Tidak', span: 4, required: true, type: 'select', default: 'Beton', options: ['Beton', 'Tidak Beton'] },
        { name: 'luas', label: 'Luas (m²)', span: 4, placeholder: 'ex : 5 / 4,5' },
        { name: 'letak_bangunan', label: 'Letak Bangunan', span: 4, required: true, placeholder: 'Masukkan lokasi aset' },
        { name: 'dokumen', label: 'Tanggal & No Dokumen', span: 5, required: true, placeholder: 'Masukkan tanggal & nomor dokumen' },
        { name: 'tgl_mulai', label: 'Tanggal Mulai', span: 3, type: 'date', required: true, placeholder: 'dd/mm/yy' },
        { name: 'status_tanah', label: 'Status Tanah', span: 4, required: true, type: 'select', default: 'Hak Guna Bangunan', options: ['Hak Milik', 'Hak Guna Bangunan', 'Hak Pakai', 'Hak Pengelolaan'] },
        { name: 'sumber', label: 'Sumber', span: 4, required: true, type: 'select', default: 'Hibah', options: ['Hibah', 'Pembelian', 'APBN', 'APBD', 'BOS'] },
        { name: 'biaya', label: 'Biaya (Rp.)', span: 4, type: 'currency', required: true, placeholder: 'ex : 80.000.000' },
        { name: 'persen_fisik', label: 'Persentase Fisik', span: 4, required: true, placeholder: 'ex : 90%' },
        { name: 'keterangan', label: 'Keterangan', span: 8, rowSpan: 2, type: 'textarea', placeholder: 'Masukkan spesifikasi aset' },
        { name: 'lampiran', label: 'Lampiran', span: 4, type: 'file' }
    ],
    initialData: [
        {
            id: 1,
            kode: 'KIB-F/2024/001',
            jenis: 'Pembangunan',
            nama: 'Pembangunan Ruang Perpustakaan',
            no_reg: '000001',
            tingkat: 'Tidak',
            beton: 'Beton',
            luas: '56',
            letak_bangunan: 'Sebelah Barat Ruang Kelas 6',
            dokumen: '600/045/Disdik/2024, 10 Maret 2024',
            tgl_mulai: '15 Maret 2024',
            status_tanah: 'Hak Pakai',
            biaya: 'Rp 120.000.000',
            persen_fisik: '65%',
            status_pengerjaan: 'Dalam Pengerjaan',
            sumber: 'APBD',
            keterangan: 'Pekerjaan pasangan dinding & atap',
            lampiran: 'Kontrak_Perpus_2024.pdf'
        },
        {
            id: 2,
            kode: 'KIB-F/2023/002',
            jenis: 'Pembangunan',
            nama: 'Pembangunan Toilet Siswa (4 Pintu)',
            no_reg: '000002',
            tingkat: 'Tidak',
            beton: 'Beton',
            luas: '24',
            letak_bangunan: 'Belakang Ruang Guru',
            dokumen: '600/112/DAK/2023, 05 Juli 2023',
            tgl_mulai: '12 Juli 2023',
            status_tanah: 'Hak Pakai',
            biaya: 'Rp 45.000.000',
            persen_fisik: '100%',
            status_pengerjaan: 'Selesai',
            sumber: 'DAK Fisik',
            keterangan: 'Sudah serah terima pertama (PHO)',
            lampiran: 'BAST_Toilet_2023.pdf'
        },
        {
            id: 3,
            kode: 'KIB-F/2024/003',
            jenis: 'Renovasi',
            nama: 'Renovasi Benteng Pagar Keliling',
            no_reg: '000003',
            tingkat: 'Tidak',
            beton: 'Beton',
            luas: '-',
            letak_bangunan: 'Sisi Timur Batas Sekolah',
            dokumen: '600/018/SPK/2024, 02 Mei 2024',
            tgl_mulai: '20 Mei 2024',
            status_tanah: 'Hak Pakai',
            biaya: 'Rp 35.000.000',
            persen_fisik: '20%',
            status_pengerjaan: 'Perencanaan',
            sumber: 'APBD',
            keterangan: 'Penggalian pondasi pagar',
            lampiran: 'RAB_Benteng_2024.pdf'
        }
    ]
});
</script>

</body>
</html>
