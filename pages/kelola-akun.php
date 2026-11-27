<?php
/**
 * Halaman Kelola Akun - Sistem Informasi Inventaris Aset SDN Budi Karya
 * Lokasi: pages/kelola-akun.php
 */

$pageTitle = 'Kelola Akun - Sistem Informasi Inventaris Aset SDN Budi Karya';
$activeMenu = 'kelola-akun';

require_once __DIR__ . '/../includes/auth.php';
require_auth('administrator');

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/dashboard.css'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/kelola-akun.css?v=' . filemtime(__DIR__ . '/../assets/css/kelola-akun.css')); ?>">

<div class="app-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-container">

            <!-- Header Halaman Kelola Akun -->
            <div class="account-page-header">
                <div class="account-header-left">
                    <h1 class="dash-page-title">KELOLA AKUN</h1>
                    <p class="dash-page-subtitle">Daftar akun pengguna sistem inventaris aset sekolah</p>
                </div>
                <div class="account-header-actions">
                    <button type="button" class="btn-account-filter" id="btnFilter" title="Filter & Pengaturan" aria-label="Filter">
                        <i class="ph-bold ph-faders"></i>
                    </button>
                    <button type="button" class="btn-account-add" id="btnAddAccount">
                        <i class="ph-bold ph-plus"></i>
                        <span>Tambah Akun</span>
                    </button>
                </div>
            </div>

            <!-- Card Utama Konten -->
            <div class="dash-card account-card">

                <!-- Search Bar Real-Time & Toggle Kolom Password -->
                <div class="account-toolbar">
                    <div class="account-search-wrapper">
                        <i class="ph-bold ph-magnifying-glass account-search-icon"></i>
                        <input
                            type="text"
                            id="accountSearchInput"
                            class="account-search-input"
                            placeholder="Cari berdasarkan nama..."
                            autocomplete="off"
                            aria-label="Cari berdasarkan nama"
                        >
                        <button
                            type="button"
                            id="accountSearchClear"
                            class="account-search-clear"
                            title="Hapus pencarian"
                            aria-label="Hapus pencarian"
                        >
                            <i class="ph-bold ph-x"></i>
                        </button>
                    </div>

                    <div class="account-toolbar-right">
                        <label class="toggle-password-col" for="togglePasswordCol">
                            <input type="checkbox" id="togglePasswordCol" class="toggle-password-input">
                            <span class="toggle-password-switch"></span>
                            <span class="toggle-password-label">Tampilkan kolom Password</span>
                        </label>
                        <div class="account-meta-count" id="accountMetaCount">
                            Menampilkan <span id="countCurrent">0</span> dari <span id="countTotal">0</span> akun
                        </div>
                    </div>
                </div>

                <!-- Tabel Akun Pengguna -->
                <div class="account-table-wrapper">
                    <table class="account-table">
                        <thead>
                            <tr>
                                <th class="col-no">No</th>
                                <th class="col-nama">Nama</th>
                                <th class="col-username">Username</th>
                                <th class="col-password" id="thPassword" style="display: none;">Password</th>
                                <th class="col-peran">Peran</th>
                                <th class="col-status">Status</th>
                                <th class="col-aktivitas">Aktivitas</th>
                            </tr>
                        </thead>
                        <tbody id="accountTableBody">
                            <!-- Baris tabel di-render dinamis oleh JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Footer Tabel & Pagination -->
                <div class="account-table-footer">
                    <!-- Kiri: Pilihan Range / Rows per page -->
                    <div class="account-range-wrapper">
                        <span class="range-label">Range</span>
                        <div class="range-options" id="rangeOptions">
                            <button type="button" class="range-btn" data-range="5">5</button>
                            <button type="button" class="range-btn" data-range="10">10</button>
                            <button type="button" class="range-btn" data-range="25">25</button>
                            <button type="button" class="range-btn" data-range="50">50</button>
                            <button type="button" class="range-btn active" data-range="all">all</button>
                        </div>
                    </div>

                    <!-- Kanan: Pagination Navigasi -->
                    <div class="account-pagination" id="accountPagination">
                        <!-- Tombol pagination di-render dinamis oleh JavaScript -->
                    </div>
                </div>

            </div>

        </div>

        <!-- ===================================================================
             Modal 1: Filter Data
             =================================================================== -->
        <div class="modal-overlay" id="modalFilter" aria-hidden="true">
            <div class="modal-dialog modal-dialog--filter" role="dialog" aria-modal="true" aria-labelledby="modalFilterTitle">
                <div class="modal-header">
                    <div class="modal-header-left">
                        <span class="modal-header-icon modal-header-icon--green">
                            <i class="ph-bold ph-faders"></i>
                        </span>
                        <h3 class="modal-title" id="modalFilterTitle">Filter Data</h3>
                    </div>
                    <button type="button" class="modal-btn-close" data-modal-close aria-label="Tutup modal">
                        <i class="ph-bold ph-x"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="modal-form-group">
                        <label for="filterKeyword" class="modal-label">Kata Kunci</label>
                        <div class="modal-input-icon-wrapper">
                            <i class="ph-bold ph-magnifying-glass modal-field-icon"></i>
                            <input
                                type="text"
                                id="filterKeyword"
                                class="modal-input modal-input--has-icon"
                                placeholder="Masukkan kata kunci ..."
                                autocomplete="off"
                            >
                        </div>
                    </div>

                    <div class="modal-form-group">
                        <label for="filterNama" class="modal-label">Nama Pengguna</label>
                        <select id="filterNama" class="modal-select">
                            <option value="">Pilih Nama Pengguna</option>
                        </select>
                    </div>

                    <div class="modal-form-group">
                        <label for="filterPeran" class="modal-label">Peran</label>
                        <select id="filterPeran" class="modal-select">
                            <option value="">Semua Peran</option>
                            <option value="Administrator">Administrator</option>
                            <option value="Operator">Operator</option>
                        </select>
                    </div>

                    <div class="modal-form-group">
                        <label for="filterStatus" class="modal-label">Status Akun</label>
                        <select id="filterStatus" class="modal-select">
                            <option value="">Semua Status</option>
                            <option value="Aktif">Aktif</option>
                            <option value="Nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer modal-footer--space-between">
                    <button type="button" class="btn-modal-reset" id="btnResetFilter">
                        <i class="ph-bold ph-arrows-counter-clockwise"></i>
                        <span>Reset</span>
                    </button>
                    <button type="button" class="btn-modal-primary" id="btnApplyFilter">
                        Terapkan
                    </button>
                </div>
            </div>
        </div>

        <!-- ===================================================================
             Modal 2: Tambah Akun
             =================================================================== -->
        <div class="modal-overlay" id="modalAddAccount" aria-hidden="true">
            <div class="modal-dialog modal-dialog--account" role="dialog" aria-modal="true" aria-labelledby="modalAddTitle">
                <div class="modal-header">
                    <h3 class="modal-title" id="modalAddTitle">Tambah Akun</h3>
                    <button type="button" class="modal-btn-close" data-modal-close aria-label="Tutup modal">
                        <i class="ph-bold ph-x"></i>
                    </button>
                </div>

                <form id="formAddAccount" novalidate autocomplete="off">
                    <div class="modal-body">
                        <div class="modal-form-group">
                            <label for="addNama" class="modal-label required">Nama</label>
                            <input
                                type="text"
                                id="addNama"
                                class="modal-input"
                                placeholder="Masukkan nama lengkap"
                            >
                            <span class="field-error" id="errorAddNama"></span>
                        </div>

                        <div class="modal-form-group">
                            <label for="addUsername" class="modal-label required">Username</label>
                            <input
                                type="text"
                                id="addUsername"
                                class="modal-input"
                                placeholder="Masukkan username"
                            >
                            <span class="field-error" id="errorAddUsername"></span>
                        </div>

                        <div class="modal-form-group">
                            <label for="addPeran" class="modal-label required">Peran</label>
                            <select id="addPeran" class="modal-select">
                                <option value="">Pilih peran</option>
                                <option value="Administrator">Administrator</option>
                                <option value="Operator">Operator</option>
                            </select>
                            <span class="field-error" id="errorAddPeran"></span>
                        </div>

                        <div class="modal-form-group">
                            <label for="addEmail" class="modal-label required">Email</label>
                            <input
                                type="email"
                                id="addEmail"
                                class="modal-input"
                                placeholder="contoh@sdnbudikarya.sch.id"
                            >
                            <span class="field-error" id="errorAddEmail"></span>
                        </div>

                        <div class="modal-form-group">
                            <label for="addPassword" class="modal-label required">Password</label>
                            <div class="modal-password-input-wrapper">
                                <input
                                    type="password"
                                    id="addPassword"
                                    class="modal-input modal-input--password"
                                    placeholder="Minimal 6 karakter"
                                >
                                <button
                                    type="button"
                                    class="btn-password-toggle"
                                    data-target="addPassword"
                                    title="Tampilkan/Sembunyikan Password"
                                    aria-label="Tampilkan atau sembunyikan password"
                                >
                                    <i class="ph-bold ph-eye"></i>
                                </button>
                            </div>
                            <span class="field-error" id="errorAddPassword"></span>
                        </div>

                        <div class="modal-form-group">
                            <label for="addConfirmPassword" class="modal-label required">Konfirmasi Password</label>
                            <div class="modal-password-input-wrapper">
                                <input
                                    type="password"
                                    id="addConfirmPassword"
                                    class="modal-input modal-input--password"
                                    placeholder="Ulangi password"
                                >
                                <button
                                    type="button"
                                    class="btn-password-toggle"
                                    data-target="addConfirmPassword"
                                    title="Tampilkan/Sembunyikan Password"
                                    aria-label="Tampilkan atau sembunyikan password"
                                >
                                    <i class="ph-bold ph-eye"></i>
                                </button>
                            </div>
                            <span class="field-error" id="errorAddConfirmPassword"></span>
                        </div>
                    </div>

                    <div class="modal-footer modal-footer--center">
                        <button type="submit" class="btn-modal-primary btn-modal-primary--submit">
                            Simpan Akun
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===================================================================
             Modal 3: Edit Akun
             =================================================================== -->
        <div class="modal-overlay" id="modalEditAccount" aria-hidden="true">
            <div class="modal-dialog modal-dialog--account" role="dialog" aria-modal="true" aria-labelledby="modalEditTitle">
                <div class="modal-header">
                    <h3 class="modal-title" id="modalEditTitle">Edit Akun</h3>
                    <button type="button" class="modal-btn-close" data-modal-close aria-label="Tutup modal">
                        <i class="ph-bold ph-x"></i>
                    </button>
                </div>

                <form id="formEditAccount" novalidate autocomplete="off">
                    <input type="hidden" id="editAccountId" value="">

                    <div class="modal-body">
                        <div class="modal-form-group">
                            <label for="editNama" class="modal-label required">Nama</label>
                            <input
                                type="text"
                                id="editNama"
                                class="modal-input"
                                placeholder="Masukkan nama lengkap"
                            >
                            <span class="field-error" id="errorEditNama"></span>
                        </div>

                        <div class="modal-form-group">
                            <label for="editUsername" class="modal-label required">Username</label>
                            <input
                                type="text"
                                id="editUsername"
                                class="modal-input"
                                placeholder="Masukkan username"
                            >
                            <span class="field-error" id="errorEditUsername"></span>
                        </div>

                        <div class="modal-form-group">
                            <label for="editPeran" class="modal-label required">Peran</label>
                            <select id="editPeran" class="modal-select">
                                <option value="">Pilih peran</option>
                                <option value="Administrator">Administrator</option>
                                <option value="Operator">Operator</option>
                            </select>
                            <span class="field-error" id="errorEditPeran"></span>
                        </div>

                        <div class="modal-form-group">
                            <label for="editEmail" class="modal-label required">Email</label>
                            <input
                                type="email"
                                id="editEmail"
                                class="modal-input"
                                placeholder="contoh@sdnbudikarya.sch.id"
                            >
                            <span class="field-error" id="errorEditEmail"></span>
                        </div>

                        <div class="modal-form-group">
                            <label for="editPassword" class="modal-label">Password</label>
                            <div class="modal-password-input-wrapper">
                                <input
                                    type="password"
                                    id="editPassword"
                                    class="modal-input modal-input--password"
                                    placeholder="Kosongkan jika tidak diubah"
                                >
                                <button
                                    type="button"
                                    class="btn-password-toggle"
                                    data-target="editPassword"
                                    title="Tampilkan/Sembunyikan Password"
                                    aria-label="Tampilkan atau sembunyikan password"
                                >
                                    <i class="ph-bold ph-eye"></i>
                                </button>
                            </div>
                            <span class="field-error" id="errorEditPassword"></span>
                        </div>

                        <div class="modal-form-group">
                            <label for="editConfirmPassword" class="modal-label">Konfirmasi Password</label>
                            <div class="modal-password-input-wrapper">
                                <input
                                    type="password"
                                    id="editConfirmPassword"
                                    class="modal-input modal-input--password"
                                    placeholder="Kosongkan jika tidak diubah"
                                >
                                <button
                                    type="button"
                                    class="btn-password-toggle"
                                    data-target="editConfirmPassword"
                                    title="Tampilkan/Sembunyikan Password"
                                    aria-label="Tampilkan atau sembunyikan password"
                                >
                                    <i class="ph-bold ph-eye"></i>
                                </button>
                            </div>
                            <span class="field-error" id="errorEditConfirmPassword"></span>
                        </div>
                    </div>

                    <div class="modal-footer modal-footer--center">
                        <button type="submit" class="btn-modal-primary btn-modal-primary--submit">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script src="<?php echo htmlspecialchars($assetsPath . 'js/kelola-akun.js?v=' . filemtime(__DIR__ . '/../assets/js/kelola-akun.js')); ?>"></script>

</body>
</html>
