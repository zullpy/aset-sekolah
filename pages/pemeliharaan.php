<?php
/**
 * Halaman Pemeliharaan Aset Sekolah
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/aset-kelas-helper.php';
require_auth(['administrator', 'operator']);

$currentUser = current_user();
$isOperatorRole = ($currentUser && ($currentUser['role'] ?? '') === 'operator');
$operatorKelasId = $isOperatorRole ? detect_operator_kelas_id($currentUser) : null;

$pageTitle     = ($operatorKelasId !== null)
    ? ('Pemeliharaan Kelas ' . $operatorKelasId . ' - Sistem Informasi Inventaris Aset SDN Budi Karya')
    : 'Pemeliharaan Aset - Sistem Informasi Inventaris Aset SDN Budi Karya';
$activeMenu    = 'pemeliharaan';
$activeSubmenu = '';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/dashboard.css'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/kib.css'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/monitoring.css'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/pemeliharaan.css?v=' . filemtime(dirname(__DIR__) . '/assets/css/pemeliharaan.css')); ?>">

<div class="app-layout">
    <?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-container pml-container">

            <!-- Header Halaman -->
            <div class="kib-page-header pml-page-header">
                <div class="kib-header-left">
                    <h1 class="kib-page-title pml-page-title">
                        <?php echo ($operatorKelasId !== null) ? ('PEMELIHARAAN KELAS ' . $operatorKelasId) : 'PEMELIHARAAN'; ?>
                    </h1>
                    <p class="kib-page-subtitle pml-page-subtitle">
                        <?php echo ($operatorKelasId !== null)
                            ? ('Ringkasan informasi tahapan pemeliharaan aset Kelas ' . $operatorKelasId)
                            : 'Ringkasan informasi tahapan pemeliharaan aset sekolah'; ?>
                    </p>
                </div>
            </div>

            <!-- Timeline 5 Tahapan Pemeliharaan -->
            <section class="pml-timeline-card" aria-label="Timeline Tahapan Pemeliharaan Aset">
                <div class="pml-timeline-scroll">
                    <div class="pml-timeline-stage-wrap">
                        <!-- Jalur kurva bergelombang SVG menghubungkan 5 titik tahap -->
                        <svg class="pml-timeline-svg" viewBox="0 0 1000 288" preserveAspectRatio="none" aria-hidden="true">
                            <path
                                d="M 35 130 C 60 120, 78 114, 100 114 C 180 114, 220 178, 300 178 C 380 178, 420 114, 500 114 C 580 114, 620 178, 700 178 C 780 178, 820 114, 900 114 C 922 114, 940 120, 965 130"
                                fill="none"
                                stroke="#9ca3af"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                vector-effect="non-scaling-stroke"
                            />
                        </svg>

                        <!-- Tahap 1 (Ganjil - Atas) -->
                        <div class="pml-stage pml-stage--odd" style="--stage-x: 10%; --stage-color: #d9363e;">
                            <div class="pml-stage-circle">
                                <i class="ph-bold ph-warning" aria-hidden="true"></i>
                            </div>
                            <span class="pml-stage-stem" aria-hidden="true"></span>
                            <span class="pml-stage-dot" aria-hidden="true"></span>
                            <div class="pml-stage-meta">
                                <div class="pml-stage-label">Perlu pemeliharaan</div>
                                <div class="pml-stage-count-box">
                                    <strong class="pml-stage-count" id="pmlCountPerlu">0</strong>
                                    <span>aset</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tahap 2 (Genap - Bawah) -->
                        <div class="pml-stage pml-stage--even" style="--stage-x: 30%; --stage-color: #2f62b8;">
                            <div class="pml-stage-circle">
                                <i class="ph-bold ph-gear" aria-hidden="true"></i>
                            </div>
                            <span class="pml-stage-stem" aria-hidden="true"></span>
                            <span class="pml-stage-dot" aria-hidden="true"></span>
                            <div class="pml-stage-meta">
                                <div class="pml-stage-label">Sedang ditindak lanjut</div>
                                <div class="pml-stage-count-box">
                                    <strong class="pml-stage-count" id="pmlCountSedang">0</strong>
                                    <span>aset</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tahap 3 (Ganjil - Atas) -->
                        <div class="pml-stage pml-stage--odd" style="--stage-x: 50%; --stage-color: #f08a24;">
                            <div class="pml-stage-circle">
                                <i class="ph-bold ph-wrench" aria-hidden="true"></i>
                            </div>
                            <span class="pml-stage-stem" aria-hidden="true"></span>
                            <span class="pml-stage-dot" aria-hidden="true"></span>
                            <div class="pml-stage-meta">
                                <div class="pml-stage-label">Selesai ditindak lanjut</div>
                                <div class="pml-stage-count-box">
                                    <strong class="pml-stage-count" id="pmlCountSelesai">0</strong>
                                    <span>aset</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tahap 4 (Genap - Bawah) -->
                        <div class="pml-stage pml-stage--even" style="--stage-x: 70%; --stage-color: #2e9e57;">
                            <div class="pml-stage-circle">
                                <i class="ph-bold ph-arrows-clockwise" aria-hidden="true"></i>
                            </div>
                            <span class="pml-stage-stem" aria-hidden="true"></span>
                            <span class="pml-stage-dot" aria-hidden="true"></span>
                            <div class="pml-stage-meta">
                                <div class="pml-stage-label">Digunakan kembali</div>
                                <div class="pml-stage-count-box">
                                    <strong class="pml-stage-count" id="pmlCountDigunakan">0</strong>
                                    <span>aset</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tahap 5 (Ganjil - Atas) -->
                        <div class="pml-stage pml-stage--odd" style="--stage-x: 90%; --stage-color: #8e44ad;">
                            <div class="pml-stage-circle">
                                <i class="ph-bold ph-box-arrow-down" aria-hidden="true"></i>
                            </div>
                            <span class="pml-stage-stem" aria-hidden="true"></span>
                            <span class="pml-stage-dot" aria-hidden="true"></span>
                            <div class="pml-stage-meta">
                                <div class="pml-stage-label">Disimpan</div>
                                <div class="pml-stage-count-box">
                                    <strong class="pml-stage-count" id="pmlCountDisimpan">0</strong>
                                    <span>aset</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Card Daftar Riwayat Pemeliharaan -->
            <section class="kib-card pml-list-card" aria-label="Daftar Riwayat Pemeliharaan Aset">
                <div class="pml-list-header">
                    <h2 class="pml-list-title">Daftar riwayat pemeliharaan aset</h2>

                    <div class="pml-list-actions">
                        <button
                            type="button"
                            class="btn-kib-filter pml-btn-filter"
                            id="btnPmlFilter"
                            title="Filter Data"
                            aria-label="Filter Data"
                        >
                            <i class="ph-bold ph-sliders" aria-hidden="true"></i>
                            <span class="pml-filter-badge" id="pmlFilterBadge" aria-hidden="true">0</span>
                        </button>
                        <button type="button" class="btn-kib-add pml-btn-add" id="btnPmlTambah">
                            <i class="ph-bold ph-plus" aria-hidden="true"></i>
                            <span>Tambah Proses</span>
                        </button>
                    </div>
                </div>

                <div class="kib-table-wrapper pml-table-wrapper">
                    <table class="kib-table pml-table">
                        <thead>
                            <tr>
                                <th class="col-no">No</th>
                                <th class="th-center">Kode Aset</th>
                                <th>Nama Aset</th>
                                <th class="th-center">Lokasi</th>
                                <th class="th-center">Kondisi</th>
                                <th class="th-center">Jumlah</th>
                                <th class="th-center">Tanggal Update</th>
                                <th class="th-center">Aktivitas</th>
                            </tr>
                        </thead>
                        <tbody id="pmlTableBody">
                            <tr>
                                <td colspan="8" class="kib-empty-row">
                                    <i class="ph-bold ph-spinner"></i>
                                    Memuat data pemeliharaan aset...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="kib-table-footer">
                    <div class="kib-range-wrapper">
                        <span class="range-label">Range</span>
                        <div class="range-options" id="pmlRangeOptions">
                            <button type="button" class="range-btn" data-range="5">5</button>
                            <button type="button" class="range-btn active" data-range="10">10</button>
                            <button type="button" class="range-btn" data-range="25">25</button>
                            <button type="button" class="range-btn" data-range="50">50</button>
                            <button type="button" class="range-btn" data-range="all">all</button>
                        </div>
                    </div>

                    <div class="kib-pagination" id="pmlPagination"></div>
                </div>
            </section>

        </div>
    </main>
</div>

<!-- Modal Filter Data (Komponen modal sama dengan halaman Monitoring) -->
<div class="kib-modal-overlay" id="pmlModalFilter" aria-hidden="true">
    <div class="kib-modal-dialog kib-modal-dialog--filter pml-modal-dialog--filter" role="dialog" aria-modal="true" aria-labelledby="pmlFilterTitle">
        <div class="kib-modal-header">
            <div class="kib-modal-header-left">
                <span class="kib-modal-icon-green" aria-hidden="true">
                    <i class="ph-bold ph-sliders"></i>
                </span>
                <h3 class="kib-modal-title" id="pmlFilterTitle">Filter Data</h3>
            </div>
            <button type="button" class="kib-modal-close pml-modal-close-solid" data-modal-close aria-label="Tutup">
                <i class="ph-bold ph-x"></i>
            </button>
        </div>

        <div class="kib-modal-body">
            <div class="mon-filter-grid pml-filter-grid">
                <div class="kib-form-group mon-filter-full">
                    <label class="kib-label" for="pmlFilterKeyword">Kata Kunci</label>
                    <div class="kib-input-icon-wrapper">
                        <i class="ph-bold ph-magnifying-glass kib-input-icon pml-icon-green" aria-hidden="true"></i>
                        <input
                            type="text"
                            id="pmlFilterKeyword"
                            class="kib-input kib-input--has-icon pml-placeholder-italic"
                            placeholder="Masukkan kata kunci..."
                            autocomplete="off"
                        >
                    </div>
                </div>

                <div class="kib-form-group">
                    <label class="kib-label" for="pmlFilterNamaAset">Nama Aset</label>
                    <select id="pmlFilterNamaAset" class="kib-select">
                        <option value="">Pilih nama aset</option>
                    </select>
                </div>

                <div class="kib-form-group">
                    <label class="kib-label" for="pmlFilterKondisiAwal">Kondisi Awal</label>
                    <select id="pmlFilterKondisiAwal" class="kib-select">
                        <option value="">Semua Kondisi</option>
                        <option value="Baik">Baik</option>
                        <option value="Rusak Ringan">Rusak Ringan</option>
                        <option value="Rusak Berat">Rusak Berat</option>
                        <option value="Hilang">Hilang</option>
                    </select>
                </div>

                <div class="kib-form-group">
                    <label class="kib-label" for="pmlFilterLokasi">Lokasi</label>
                    <select id="pmlFilterLokasi" class="kib-select">
                        <option value="">Pilih Lokasi</option>
                    </select>
                </div>

                <div class="kib-form-group">
                    <label class="kib-label" for="pmlFilterKondisiAkhir">Kondisi Akhir</label>
                    <select id="pmlFilterKondisiAkhir" class="kib-select">
                        <option value="">Semua Kondisi</option>
                        <option value="Baik">Baik</option>
                        <option value="Rusak Ringan">Rusak Ringan</option>
                        <option value="Rusak Berat">Rusak Berat</option>
                        <option value="Hilang">Hilang</option>
                    </select>
                </div>

                <div class="kib-form-group">
                    <label class="kib-label" for="pmlFilterStatus">Status Pemeliharaan</label>
                    <select id="pmlFilterStatus" class="kib-select">
                        <option value="">Pilih Status Pemeliharaan</option>
                        <option value="Perlu pemeliharaan">Perlu pemeliharaan</option>
                        <option value="Sedang ditindak lanjut">Sedang ditindak lanjut</option>
                        <option value="Selesai ditindak lanjut">Selesai ditindak lanjut</option>
                        <option value="Digunakan kembali">Digunakan kembali</option>
                        <option value="Disimpan">Disimpan</option>
                    </select>
                </div>

                <div class="kib-form-group">
                    <label class="kib-label" for="pmlFilterTglDari">Tanggal Update</label>
                    <div class="pml-date-range-pair">
                        <input type="date" id="pmlFilterTglDari" class="kib-input pml-date-range-input" title="Pilih Tanggal Dari" aria-label="Tanggal Update Dari">
                        <span class="pml-date-sep" aria-hidden="true">s/d</span>
                        <input type="date" id="pmlFilterTglSampai" class="kib-input pml-date-range-input" title="Pilih Tanggal Sampai" aria-label="Tanggal Update Sampai">
                    </div>
                </div>
            </div>
        </div>

        <div class="kib-modal-footer pml-filter-footer-center">
            <button type="button" class="btn-kib-modal-reset pml-btn-reset" id="btnPmlResetFilter">
                <i class="ph-bold ph-funnel-x" aria-hidden="true"></i>
                <span>Reset</span>
            </button>
            <button type="button" class="btn-kib-modal-apply pml-btn-apply" id="btnPmlApplyFilter">
                Terapkan
            </button>
        </div>
    </div>
</div>

<!-- Modal Proses Pemeliharaan Aset -->
<div class="kib-modal-overlay" id="pmlModalProses" aria-hidden="true">
    <div class="kib-modal-dialog pml-modal-dialog--proses" role="dialog" aria-modal="true" aria-labelledby="pmlProsesTitle">
        <div class="kib-modal-header">
            <div class="kib-modal-header-left">
                <span class="pml-modal-icon-wrench" aria-hidden="true">
                    <i class="ph-bold ph-wrench"></i>
                </span>
                <h3 class="kib-modal-title" id="pmlProsesTitle">Proses Pemeliharaan Aset</h3>
            </div>
            <button type="button" class="kib-modal-close pml-modal-close" data-modal-close aria-label="Tutup">
                <i class="ph-bold ph-x"></i>
            </button>
        </div>

        <form id="pmlProsesForm" novalidate autocomplete="off">
            <input type="hidden" id="pmlFieldSourceId" value="0">
            <input type="hidden" id="pmlFieldKib" value="">
            <input type="hidden" id="pmlFieldAsetId" value="0">

            <div class="kib-modal-body pml-proses-body">
                <!-- Baris 1: Kode Aset (~55%) | Nama Aset (~45%) -->
                <div class="pml-form-row pml-form-row--aset">
                    <div class="pml-form-group">
                        <label class="kib-label" for="pmlFieldKode">Kode Aset</label>
                        <input
                            type="text"
                            id="pmlFieldKode"
                            class="pml-input pml-input--readonly"
                            placeholder="Otomatis terisi"
                            readonly
                            tabindex="-1"
                        >
                    </div>
                    <div class="pml-form-group">
                        <label class="kib-label required" for="pmlFieldNamaAsetSearch">Nama Aset</label>
                        <div class="mon-aset-combobox" id="pmlAsetCombobox">
                            <input type="hidden" id="pmlFieldNamaAset" value="">
                            <div class="mon-aset-input-wrap">
                                <i class="ph-bold ph-magnifying-glass mon-aset-search-icon" aria-hidden="true"></i>
                                <input
                                    type="text"
                                    id="pmlFieldNamaAsetSearch"
                                    class="pml-input mon-aset-search-input"
                                    placeholder="Cari nama atau kode aset..."
                                    autocomplete="off"
                                    role="combobox"
                                    aria-autocomplete="list"
                                    aria-expanded="false"
                                    aria-controls="pmlAsetDropdown"
                                >
                                <button
                                    type="button"
                                    id="pmlFieldNamaAsetClear"
                                    class="mon-aset-clear-btn"
                                    title="Hapus pilihan"
                                    aria-label="Hapus pilihan"
                                    tabindex="-1"
                                >
                                    <i class="ph-bold ph-x"></i>
                                </button>
                                <button
                                    type="button"
                                    id="pmlFieldNamaAsetToggle"
                                    class="mon-aset-toggle-btn"
                                    title="Tampilkan daftar aset"
                                    aria-label="Tampilkan daftar aset"
                                    tabindex="-1"
                                >
                                    <i class="ph-bold ph-caret-down"></i>
                                </button>
                            </div>
                            <div class="mon-aset-dropdown" id="pmlAsetDropdown" role="listbox" aria-label="Daftar Nama Aset"></div>
                        </div>
                        <span class="kib-field-error" id="errPml_aset"></span>
                    </div>
                </div>

                <!-- Baris 2: Lokasi Aset | Kondisi Awal | Jumlah -->
                <div class="pml-form-row pml-form-row--three">
                    <div class="pml-form-group">
                        <label class="kib-label" for="pmlFieldLokasi">Lokasi Aset</label>
                        <input
                            type="text"
                            id="pmlFieldLokasi"
                            class="pml-input pml-input--readonly"
                            placeholder="Otomatis terisi"
                            readonly
                            tabindex="-1"
                        >
                        <span class="kib-field-error" id="errPml_lokasi"></span>
                    </div>
                    <div class="pml-form-group">
                        <label class="kib-label required" for="pmlFieldKondisiAwal">Kondisi Awal</label>
                        <div class="pml-select-wrap">
                            <select id="pmlFieldKondisiAwal" class="pml-select" required>
                                <option value="">Pilih kondisi awal</option>
                                <option value="Baik">Baik</option>
                                <option value="Rusak Ringan">Rusak Ringan</option>
                                <option value="Rusak Berat">Rusak Berat</option>
                                <option value="Hilang">Hilang</option>
                            </select>
                            <i class="ph-bold ph-caret-down pml-select-chevron" aria-hidden="true"></i>
                        </div>
                        <span class="kib-field-error" id="errPml_kondisi_awal"></span>
                    </div>
                    <div class="pml-form-group">
                        <label class="kib-label required" for="pmlFieldJumlah">Jumlah</label>
                        <input
                            type="number"
                            id="pmlFieldJumlah"
                            class="pml-input pml-input--jumlah"
                            min="1"
                            value="1"
                            inputmode="numeric"
                            required
                        >
                        <span class="pml-quota-hint" id="pmlFieldJumlahHint"></span>
                        <span class="kib-field-error" id="errPml_jumlah"></span>
                    </div>
                </div>

                <!-- Baris 3: Status Pemeliharaan | Kondisi Akhir -->
                <div class="pml-form-row pml-form-row--two">
                    <div class="pml-form-group">
                        <label class="kib-label required" for="pmlFieldStatus">Status Pemeliharaan</label>
                        <div class="pml-select-wrap">
                            <select id="pmlFieldStatus" class="pml-select" required>
                                <option value="">Pilih status</option>
                            </select>
                            <i class="ph-bold ph-caret-down pml-select-chevron" aria-hidden="true"></i>
                        </div>
                        <span class="kib-field-error" id="errPml_status"></span>
                    </div>
                    <div class="pml-form-group">
                        <label class="kib-label" for="pmlFieldKondisiAkhir" id="pmlLabelKondisiAkhir">Kondisi Akhir</label>
                        <div class="pml-select-wrap">
                            <select id="pmlFieldKondisiAkhir" class="pml-select">
                                <option value="">Pilih kondisi akhir</option>
                                <option value="Baik">Baik</option>
                                <option value="Rusak Ringan">Rusak Ringan</option>
                                <option value="Rusak Berat">Rusak Berat</option>
                                <option value="Hilang">Hilang</option>
                            </select>
                            <i class="ph-bold ph-caret-down pml-select-chevron" aria-hidden="true"></i>
                        </div>
                        <span class="kib-field-error" id="errPml_kondisi_akhir"></span>
                    </div>
                </div>

                <!-- Baris 4: Tanggal Update di kiri -->
                <div class="pml-form-row pml-form-row--two">
                    <div class="pml-form-group">
                        <label class="kib-label required" for="pmlFieldTanggal">Tanggal Update</label>
                        <div class="pml-date-wrap">
                            <input type="date" id="pmlFieldTanggal" class="pml-input" required>
                            <span class="pml-date-preview" id="pmlFieldTanggalFmt">dd/mm/yy</span>
                        </div>
                        <span class="kib-field-error" id="errPml_tanggal_update"></span>
                    </div>
                </div>

                <!-- Baris 5: Dokumentasi Hasil Pemeliharaan -->
                <div class="pml-form-group pml-form-group--full">
                    <label class="kib-label" for="pmlFieldDokumentasi">Dokumentasi Hasil Pemeliharaan</label>
                    <div class="pml-upload-box" id="pmlUploadDropzone" tabindex="0" role="button" aria-label="Unggah Dokumentasi Hasil Pemeliharaan">
                        <input
                            type="file"
                            id="pmlFieldDokumentasi"
                            class="pml-upload-file-input"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            multiple
                        >
                        <div class="pml-upload-placeholder" id="pmlUploadPlaceholder">
                            <i class="ph-bold ph-image" aria-hidden="true"></i>
                            <span>Klik atau seret foto ke sini (JPG/PNG/WEBP, maks 5MB/foto)</span>
                        </div>
                        <div class="pml-upload-previews" id="pmlUploadPreviews"></div>
                    </div>
                    <span class="kib-field-error" id="errPml_dokumentasi"></span>
                </div>

                <!-- Baris 6: Tombol Simpan Proses -->
                <div class="pml-submit-row">
                    <button type="submit" class="pml-btn-submit" id="btnPmlSimpanProses">Simpan Proses</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Riwayat Tahapan Aset (Ikon Buku) -->
<div class="kib-modal-overlay" id="pmlModalRiwayat" aria-hidden="true">
    <div class="kib-modal-dialog pml-modal-dialog--riwayat" role="dialog" aria-modal="true" aria-labelledby="pmlRiwayatTitle">
        <div class="kib-modal-header">
            <div class="kib-modal-header-left">
                <span class="pml-modal-icon-book" aria-hidden="true">
                    <i class="ph-bold ph-book"></i>
                </span>
                <div>
                    <h3 class="kib-modal-title" id="pmlRiwayatTitle">Riwayat Pemeliharaan Aset</h3>
                    <p class="pml-riwayat-subtitle" id="pmlRiwayatSubtitle">-</p>
                </div>
            </div>
            <button type="button" class="kib-modal-close pml-modal-close-solid" data-modal-close aria-label="Tutup">
                <i class="ph-bold ph-x"></i>
            </button>
        </div>
        <div class="kib-modal-body pml-riwayat-body" id="pmlRiwayatBody">
            <!-- Di-render oleh PemeliharaanEngine -->
        </div>
    </div>
</div>

<!-- Modal Pratinjau Foto Dokumentasi -->
<div class="kib-modal-overlay" id="pmlModalFoto" aria-hidden="true">
    <div class="kib-modal-dialog pml-modal-dialog--foto" role="dialog" aria-modal="true" aria-labelledby="pmlFotoTitle">
        <div class="kib-modal-header">
            <div class="kib-modal-header-left">
                <span class="pml-modal-icon-book" aria-hidden="true">
                    <i class="ph-bold ph-image"></i>
                </span>
                <div>
                    <h3 class="kib-modal-title" id="pmlFotoTitle">Dokumentasi Pemeliharaan</h3>
                    <p class="pml-riwayat-subtitle" id="pmlFotoSubtitle">-</p>
                </div>
            </div>
            <button type="button" class="kib-modal-close pml-modal-close-solid" data-modal-close aria-label="Tutup">
                <i class="ph-bold ph-x"></i>
            </button>
        </div>
        <div class="kib-modal-body pml-foto-body">
            <img id="pmlFotoModalImg" src="" alt="Dokumentasi pemeliharaan" class="pml-foto-preview-img">
        </div>
    </div>
</div>

<script src="<?php echo htmlspecialchars($assetsPath . 'js/pemeliharaan.js'); ?>"></script>
<script>
window.__pemeliharaanEngine = new PemeliharaanEngine({
    listUrl: <?php echo json_encode($appUrlPath . 'api/pemeliharaan-list.php'); ?>,
    opsiUrl: <?php echo json_encode($appUrlPath . 'api/pemeliharaan-opsi.php'); ?>,
    simpanUrl: <?php echo json_encode($appUrlPath . 'api/pemeliharaan-simpan.php'); ?>,
    riwayatUrl: <?php echo json_encode($appUrlPath . 'api/pemeliharaan-riwayat.php'); ?>,
    hapusUrl: <?php echo json_encode($appUrlPath . 'api/pemeliharaan-hapus.php'); ?>,
    baseUrl: <?php echo json_encode($appUrlPath); ?>
});
</script>

</body>
</html>
