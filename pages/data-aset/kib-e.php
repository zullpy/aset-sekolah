<?php
/**
 * Data Aset - KIB E (Buku)
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

$pageTitle = 'Data Aset - KIB E - Sistem Informasi Inventaris Aset SDN Budi Karya';
$activeMenu = 'data-aset';
$activeSubmenu = 'kib-e';

require_once __DIR__ . '/../../includes/header.php';
?>

<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/dashboard.css'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/kib.css'); ?>">

<div class="app-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-container">

            <!-- Header Halaman KIB E -->
            <div class="kib-page-header">
                <div class="kib-header-left">
                    <h1 class="kib-page-title">DATA ASET - KIB E</h1>
                    <p class="kib-page-subtitle">Data aset berupa buku yang dimiliki sekolah</p>
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
                            placeholder="Cari berdasarkan kode, jenis, nama buku, pengarang..."
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

                <!-- Tabel Aset KIB E dengan Grouped Header -->
                <div class="kib-table-wrapper">
                    <table class="kib-table">
                        <thead>
                            <tr>
                                <th rowspan="2" class="col-no">No</th>
                                <th rowspan="2">Kode Aset</th>
                                <th rowspan="2">Jenis Buku</th>
                                <th rowspan="2">Nama Buku</th>
                                <th rowspan="2" class="th-center">No Registrasi</th>
                                <th colspan="4" class="th-center">Spesifikasi Buku</th>
                                <th rowspan="2" class="th-center">Tahun Perolehan</th>
                                <th rowspan="2" class="th-center">Jumlah</th>
                                <th rowspan="2" class="th-center">Harga Satuan</th>
                                <th rowspan="2" class="th-center">Harga Total</th>
                                <th rowspan="2" class="th-center">Sumber</th>
                                <th rowspan="2" class="th-center">Lampiran</th>
                                <th rowspan="2" class="th-center">Aksi</th>
                            </tr>
                            <tr>
                                <th>Judul</th>
                                <th>Pengarang</th>
                                <th>Penerbit</th>
                                <th class="th-center">Tahun Terbit</th>
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
    kibId: 'kib-e',
    kibName: 'KIB E (Buku)',
    kibTitle: 'DATA ASET - KIB E',
    subtitle: 'Data aset berupa buku yang dimiliki sekolah',
    hasJenis: true,
    jenisLabel: 'Jenis Buku',
    defaultRange: 5,
    columns: [
        { key: 'kode' },
        { key: 'jenis' },
        { key: 'nama' },
        { key: 'no_reg', align: 'center' },
        { key: 'spesifikasi_judul' },
        { key: 'spesifikasi_pengarang' },
        { key: 'spesifikasi_penerbit' },
        { key: 'spesifikasi_tahun', align: 'center' },
        { key: 'tahun', align: 'center' },
        { key: 'jumlah', align: 'center' },
        { key: 'harga_satuan', align: 'center' },
        { key: 'harga_total', align: 'center' },
        { key: 'sumber', align: 'center' },
        { key: 'lampiran', align: 'center' }
    ],
    formFields: [
        { name: 'kode', label: 'Kode Aset', span: 4, readonly: true, placeholder: 'Otomatis' },
        { name: 'jenis', label: 'Jenis Aset', span: 4, required: true, type: 'select', default: 'Alat Peraga', options: ['Alat Peraga', 'Buku Teks Utama', 'Buku Teks Pendamping', 'Buku Pengayaan', 'Buku Panduan Pendidik', 'Buku Referensi', 'Lainnya'] },
        { name: 'nama', label: 'Nama Buku', span: 4, required: true, placeholder: 'Masukkan nama buku' },
        { name: 'no_reg', label: 'No Registrasi', span: 4, readonly: true, placeholder: 'Otomatis' },
        { name: 'spesifikasi_judul', label: 'Judul Buku', span: 4, required: true, placeholder: 'Masukkan judul buku' },
        { name: 'spesifikasi_pengarang', label: 'Penulis', span: 4, required: true, placeholder: 'Masukkan nama penulis' },
        { name: 'spesifikasi_penerbit', label: 'Penerbit', span: 4, required: true, placeholder: 'Masukkan penerbit buku' },
        { name: 'spesifikasi_tahun', label: 'Tahun Terbit', span: 4, required: true, placeholder: 'ex : 2024' },
        { name: 'sumber', label: 'Sumber', span: 4, required: true, type: 'select', default: 'Hibah', options: ['Hibah', 'Pembelian', 'APBN', 'APBD', 'BOS'] },
        { name: 'tahun', label: 'Tahun Perolehan', span: 3, required: true, placeholder: 'ex : 2024' },
        { name: 'jumlah', label: 'Jumlah', span: 3, type: 'number', narrow: true, default: 1, min: 1, required: true },
        { name: 'harga_satuan', label: 'Harga Satuan (Rp.)', span: 3, type: 'currency', required: true, placeholder: 'ex : 100.000' },
        { name: 'harga_total', label: 'Harga Total (Rp.)', span: 3, type: 'currency', readonly: true, computed: 'jumlah * harga_satuan', placeholder: 'Otomatis' },
        { name: 'lampiran', label: 'Lampiran', span: 12, type: 'file' }
    ],
    initialData: [
        {
            id: 1,
            kode: 'KIB-E/2023/001',
            jenis: 'Buku Teks Utama',
            nama: 'Bahasa Indonesia: Aku Bisa!',
            no_reg: '000001',
            spesifikasi_judul: 'Bahasa Indonesia: Aku Bisa! Kelas I',
            spesifikasi_pengarang: 'Sofie Dewayani',
            spesifikasi_penerbit: 'Pusat Perbukuan Kemendikbud',
            spesifikasi_tahun: '2023',
            tahun: '2023',
            jumlah: '50 Eks',
            harga_satuan: 'Rp 22.000',
            harga_total: 'Rp 1.100.000',
            sumber: 'BOS',
            lampiran: 'Faktur_Buku_Indo1.pdf'
        },
        {
            id: 2,
            kode: 'KIB-E/2023/002',
            jenis: 'Buku Teks Utama',
            nama: 'IPAS: Belajar Bersama Temanmu',
            no_reg: '000001',
            spesifikasi_judul: 'Ilmu Pengetahuan Alam dan Sosial SD Kelas IV',
            spesifikasi_pengarang: 'Amalia Fitri dkk.',
            spesifikasi_penerbit: 'Pusat Perbukuan Kemendikbudristek',
            spesifikasi_tahun: '2023',
            tahun: '2023',
            jumlah: '45 Eks',
            harga_satuan: 'Rp 25.000',
            harga_total: 'Rp 1.125.000',
            sumber: 'BOS',
            lampiran: 'Faktur_Buku_IPAS4.pdf'
        },
        {
            id: 3,
            kode: 'KIB-E/2022/003',
            jenis: 'Buku Pengayaan',
            nama: 'Pengembaraan Timun Mas',
            no_reg: '000001',
            spesifikasi_judul: 'Cerita Rakyat Nusantara: Timun Mas',
            spesifikasi_pengarang: 'Rini Kurniasih',
            spesifikasi_penerbit: 'Balai Pustaka',
            spesifikasi_tahun: '2022',
            tahun: '2022',
            jumlah: '25 Eks',
            harga_satuan: 'Rp 18.000',
            harga_total: 'Rp 450.000',
            sumber: 'Hibah',
            lampiran: 'BAST_Hibah_Buku.pdf'
        },
        {
            id: 4,
            kode: 'KIB-E/2022/004',
            jenis: 'Buku Panduan Pendidik',
            nama: 'Panduan Guru Matematika SD',
            no_reg: '000001',
            spesifikasi_judul: 'Buku Panduan Guru Matematika SD Kelas I',
            spesifikasi_pengarang: 'Tim Gakko Tosho',
            spesifikasi_penerbit: 'Kemendikbudristek',
            spesifikasi_tahun: '2022',
            tahun: '2022',
            jumlah: '12 Eks',
            harga_satuan: 'Rp 35.000',
            harga_total: 'Rp 420.000',
            sumber: 'BOS',
            lampiran: 'Kuitansi_Buku_Guru.pdf'
        },
        {
            id: 5,
            kode: 'KIB-E/2021/005',
            jenis: 'Buku Referensi',
            nama: 'Kamus Besar Bahasa Indonesia',
            no_reg: '000001',
            spesifikasi_judul: 'KBBI Edisi Kelima',
            spesifikasi_pengarang: 'Badan Pengembangan Bahasa',
            spesifikasi_penerbit: 'Balai Pustaka',
            spesifikasi_tahun: '2021',
            tahun: '2021',
            jumlah: '4 Eks',
            harga_satuan: 'Rp 450.000',
            harga_total: 'Rp 1.800.000',
            sumber: 'APBD',
            lampiran: 'SPK_Kamus_2021.pdf'
        },
        {
            id: 6,
            kode: 'KIB-E/2020/006',
            jenis: 'Buku Pengayaan',
            nama: 'Ensiklopedia Cilik Sains',
            no_reg: '000001',
            spesifikasi_judul: 'Mengenal Tata Surya dan Antariksa',
            spesifikasi_pengarang: 'Drs. Hendra Wardhana',
            spesifikasi_penerbit: 'Erlangga for Kids',
            spesifikasi_tahun: '2020',
            tahun: '2020',
            jumlah: '28 Eks',
            harga_satuan: 'Rp 120.000',
            harga_total: 'Rp 3.360.000',
            sumber: 'BOS',
            lampiran: 'Faktur_Ensiklopedia.pdf'
        }
    ]
});
</script>

</body>
</html>
