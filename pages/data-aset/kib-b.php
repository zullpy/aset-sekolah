<?php
/**
 * Data Aset - KIB B (Peralatan & Mesin)
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

$pageTitle = 'Data Aset - KIB B - Sistem Informasi Inventaris Aset SDN Budi Karya';
$activeMenu = 'data-aset';
$activeSubmenu = 'kib-b';

require_once __DIR__ . '/../../includes/header.php';
?>

<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/dashboard.css'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/kib.css'); ?>">

<div class="app-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-container">

            <!-- Header Halaman KIB B -->
            <div class="kib-page-header">
                <div class="kib-header-left">
                    <h1 class="kib-page-title">DATA ASET - KIB B</h1>
                    <p class="kib-page-subtitle">Data aset berupa peralatan dan mesin yang dimiliki sekolah</p>
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
                            placeholder="Cari berdasarkan kode, jenis, nama, merk..."
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

                <!-- Tabel Aset KIB B -->
                <div class="kib-table-wrapper">
                    <table class="kib-table">
                        <thead>
                            <tr>
                                <th class="col-no">No</th>
                                <th>Kode Aset</th>
                                <th>Jenis Aset</th>
                                <th>Nama Aset</th>
                                <th class="th-center">No Registrasi</th>
                                <th>Merk</th>
                                <th>Spesifikasi</th>
                                <th>Bahan</th>
                                <th>No Pabrik/Serial</th>
                                <th class="th-center">Tahun Perolehan</th>
                                <th class="th-center">Jumlah</th>
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
                            <button type="button" class="range-btn active" data-range="5">5</button>
                            <button type="button" class="range-btn" data-range="10">10</button>
                            <button type="button" class="range-btn" data-range="25">25</button>
                            <button type="button" class="range-btn" data-range="50">50</button>
                            <button type="button" class="range-btn" data-range="all">all</button>
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
    kibId: 'kib-b',
    kibName: 'KIB B (Peralatan & Mesin)',
    kibTitle: 'DATA ASET - KIB B',
    subtitle: 'Data aset berupa peralatan dan mesin yang dimiliki sekolah',
    hasJenis: true,
    jenisLabel: 'Jenis Aset',
    defaultRange: 5,
    columns: [
        { key: 'kode' },
        { key: 'jenis' },
        { key: 'nama' },
        { key: 'no_reg', align: 'center' },
        { key: 'merk' },
        { key: 'spesifikasi' },
        { key: 'bahan' },
        { key: 'no_pabrik' },
        { key: 'tahun', align: 'center' },
        { key: 'jumlah', align: 'center' },
        { key: 'harga', align: 'center' },
        { key: 'sumber', align: 'center' },
        { key: 'lampiran', align: 'center' }
    ],
    formFields: [
        { name: 'kode', label: 'Kode Aset', span: 4, readonly: true, placeholder: 'Otomatis' },
        { name: 'jenis', label: 'Jenis Aset', span: 4, required: true, type: 'select', default: 'Alat Peraga', options: ['Alat Peraga', 'Elektronik', 'Meubelair', 'Mesin', 'Kendaraan', 'Lainnya'] },
        { name: 'nama', label: 'Nama Aset', span: 4, required: true, placeholder: 'Masukkan nama aset' },
        { name: 'no_reg', label: 'No Registrasi', span: 4, readonly: true, placeholder: 'Otomatis' },
        { name: 'merk', label: 'Merk', span: 4, required: true, placeholder: 'Masukkan merk / tipe aset' },
        { name: 'spesifikasi', label: 'Spesifikasi', span: 4, required: true, placeholder: 'Masukkan spesifikasi aset' },
        { name: 'bahan', label: 'Bahan', span: 4, required: true, placeholder: 'ex : Kayu / Plastik' },
        { name: 'no_pabrik', label: 'No Pabrik / Serial', span: 4, placeholder: 'Masukkan nomor serial' },
        { name: 'tahun', label: 'Tahun Perolehan', span: 4, required: true, placeholder: 'ex : 2024' },
        { name: 'sumber', label: 'Sumber', span: 3, required: true, type: 'select', default: 'Hibah', options: ['Hibah', 'Pembelian', 'APBN', 'APBD', 'BOS'] },
        { name: 'harga', label: 'Harga (Rp.)', span: 3, type: 'currency', required: true, placeholder: 'ex : 80.000.000' },
        { name: 'jumlah', label: 'Jumlah', span: 2, type: 'number', narrow: true, default: 1, min: 1, required: true },
        { name: 'lampiran', label: 'Lampiran', span: 4, type: 'file' }
    ],
    initialData: [
        {
            id: 1,
            kode: 'KIB-B/2024/001',
            jenis: 'Elektronik',
            nama: 'Laptop ASUS Expertbook',
            no_reg: '000001',
            merk: 'ASUS B1400',
            spesifikasi: '14 Inchi / Core i5',
            bahan: 'Plastik & Logam',
            no_pabrik: 'SN-ASUS-98124',
            tahun: '2024',
            jumlah: '10 Unit',
            harga: 'Rp 8.500.000',
            sumber: 'BOS',
            lampiran: 'Faktur_Asus_2024.pdf'
        },
        {
            id: 2,
            kode: 'KIB-B/2023/001',
            jenis: 'Elektronik',
            nama: 'Printer Epson L3210',
            no_reg: '000001',
            merk: 'EcoTank L3210',
            spesifikasi: 'A4 / Inkjet',
            bahan: 'Plastik & Komponen',
            no_pabrik: 'EP-L3210-4491',
            tahun: '2023',
            jumlah: '2 Unit',
            harga: 'Rp 2.450.000',
            sumber: 'BOS',
            lampiran: 'Kuitansi_Epson.pdf'
        },
        {
            id: 3,
            kode: 'KIB-B/2022/002',
            jenis: 'Meubelair',
            nama: 'Meja Belajar Siswa',
            no_reg: '000001',
            merk: 'Olympic Wooden',
            spesifikasi: '60 × 50 × 75 cm',
            bahan: 'Kayu Jati & MDF',
            no_pabrik: '-',
            tahun: '2022',
            jumlah: '45 Buah',
            harga: 'Rp 350.000',
            sumber: 'APBD',
            lampiran: 'BAST_Meubelair_2022.pdf'
        },
        {
            id: 4,
            kode: 'KIB-B/2022/003',
            jenis: 'Meubelair',
            nama: 'Kursi Belajar Siswa',
            no_reg: '000002',
            merk: 'Chitose Student',
            spesifikasi: 'Standard SD',
            bahan: 'Pipa Besi & Kayu',
            no_pabrik: '-',
            tahun: '2022',
            jumlah: '78 Buah',
            harga: 'Rp 220.000',
            sumber: 'APBD',
            lampiran: 'BAST_Meubelair_2022.pdf'
        },
        {
            id: 5,
            kode: 'KIB-B/2021/004',
            jenis: 'Alat Peraga',
            nama: 'Meja Tenis Meja',
            no_reg: '000001',
            merk: 'Double Fish',
            spesifikasi: '274 × 152 × 76 cm',
            bahan: 'Partikel Board & Besi',
            no_pabrik: 'DF-TM-2021-08',
            tahun: '2021',
            jumlah: '1 Set',
            harga: 'Rp 4.200.000',
            sumber: 'BOS',
            lampiran: 'Nota_Tenis_Meja.pdf'
        },
        {
            id: 6,
            kode: 'KIB-B/2020/005',
            jenis: 'Elektronik',
            nama: 'Proyektor InFocus IN112x',
            no_reg: '000001',
            merk: 'InFocus',
            spesifikasi: '3800 Lumens SVGA',
            bahan: 'Komposit / Logam',
            no_pabrik: 'INF-3800-221',
            tahun: '2020',
            jumlah: '2 Unit',
            harga: 'Rp 5.100.000',
            sumber: 'BOS',
            lampiran: 'Faktur_InFocus.pdf'
        },
        {
            id: 7,
            kode: 'KIB-B/2019/006',
            jenis: 'Elektronik',
            nama: 'Komputer Desktop Guru',
            no_reg: '000001',
            merk: 'Lenovo ThinkCentre',
            spesifikasi: 'Core i3 / 8GB RAM / 500GB HDD',
            bahan: 'Logam & Sirkuit',
            no_pabrik: 'LNV-TC-5520',
            tahun: '2019',
            jumlah: '3 Unit',
            harga: 'Rp 6.000.000',
            sumber: 'BOS',
            lampiran: 'Nota_Lenovo.pdf'
        }
    ]
});
</script>

</body>
</html>
