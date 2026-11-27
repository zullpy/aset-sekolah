<?php
/**
 * Halaman Data Aset Kelas - Sistem Informasi Inventaris Aset SDN Budi Karya
 * Lokasi: pages/aset-kelas.php
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/aset-kelas-helper.php';

require_auth(['administrator', 'operator']);

$currentUser = current_user();
$kelasId = resolve_kelas_id($_GET['kelas_id'] ?? null, $currentUser);

if ($kelasId === null) {
    http_response_code(400);
    exit('<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Kelas Tidak Valid</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h2>400 - Parameter Kelas Tidak Valid</h2><p>Pilih kelas antara 1 sampai 6.</p><a href="javascript:history.back()">Kembali</a></body></html>');
}

if (!user_can_access_kelas($currentUser, $kelasId)) {
    http_response_code(403);
    exit('<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Akses Ditolak</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h2>403 - Akses Ditolak</h2><p>Anda tidak memiliki izin untuk mengakses Data Aset Kelas ' . htmlspecialchars((string) $kelasId) . '.</p><a href="javascript:history.back()">Kembali</a></body></html>');
}

$pageTitle     = 'Data Aset Kelas ' . $kelasId . ' - Sistem Informasi Inventaris Aset SDN Budi Karya';
$activeMenu    = 'data-aset';
$activeSubmenu = '';
$csrfToken     = get_csrf_token();

require_once dirname(__DIR__) . '/includes/header.php';
?>

<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/dashboard.css'); ?>">

<style>
/* Gaya Modern Halaman Data Aset Kelas */
.ak-page {
    font-family: 'Inter', 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #0f172a;
}

.ak-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 18px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}

.ak-title {
    margin: 0 0 8px;
    font-size: 1.45rem;
    font-weight: 800;
    letter-spacing: 0.4px;
    color: #0f172a;
    padding-bottom: 10px;
    border-bottom: 3.5px solid #852019;
    display: inline-block;
    text-transform: uppercase;
}

.ak-subtitle {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 500;
    color: #64748b;
}

.ak-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

/* Kartu Total Aset */
.ak-total-card {
    display: inline-flex;
    align-items: center;
    gap: 11px;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 7px 15px 7px 9px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.ak-total-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    background: rgba(133, 32, 25, 0.1);
    color: #852019;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

.ak-total-meta {
    display: flex;
    flex-direction: column;
    line-height: 1.15;
}

.ak-total-label {
    font-size: 0.68rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.ak-total-value {
    font-size: 1.12rem;
    font-weight: 800;
    color: #0f172a;
    font-variant-numeric: tabular-nums;
}

/* Tombol Filter & Klaim Aset */
.ak-btn-filter {
    position: relative;
    width: 42px;
    height: 42px;
    border-radius: 10px;
    border: 1.5px solid #16a34a;
    background: #ffffff;
    color: #16a34a;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.18rem;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    transition: all 0.18s ease;
}

.ak-btn-filter:hover {
    background: #f0fdf4;
    border-color: #15803d;
    color: #15803d;
    transform: translateY(-1px);
}

.ak-filter-badge {
    display: none;
    position: absolute;
    top: -4px;
    right: -4px;
    width: 11px;
    height: 11px;
    border-radius: 999px;
    background: #ef4444;
    border: 2px solid #ffffff;
    box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.35);
}

.ak-filter-badge.is-active {
    display: block;
}

.ak-btn-klaim {
    height: 42px;
    padding: 0 18px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
    color: #ffffff;
    font-size: 0.88rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.24);
    transition: all 0.18s ease;
}

.ak-btn-klaim:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(22, 163, 74, 0.3);
}

.ak-btn-klaim:active {
    transform: translateY(0);
}

/* Kartu & Tabel Data Aset */
.ak-table-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 6px 24px rgba(15, 23, 42, 0.06);
    border: 1px solid #e2e8f0;
}

.ak-table-wrap {
    width: 100%;
    overflow-x: auto;
    border-radius: 12px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
}

.ak-table {
    width: 100%;
    min-width: 780px;
    border-collapse: collapse;
    font-size: 0.875rem;
    color: #1e293b;
}

.ak-table thead th {
    background: #f8fafc;
    color: #475569;
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    text-align: center;
    padding: 13px 14px;
    border-bottom: 1.5px solid #e2e8f0;
    white-space: nowrap;
}

.ak-table tbody tr {
    background: #ffffff;
    transition: background-color 0.15s ease;
}

.ak-table tbody tr:hover {
    background: #f8fafc;
}

.ak-table tbody td {
    padding: 13px 14px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.ak-table tbody tr:last-child td {
    border-bottom: none;
}

.ak-col-center {
    text-align: center;
}

.ak-kode {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 7px;
    background: #f1f5f9;
    color: #0f172a;
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.01em;
    white-space: nowrap;
}

.ak-jenis-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 999px;
    background: #fef2f2;
    color: #852019;
    border: 1px solid #fecaca;
    font-size: 0.76rem;
    font-weight: 600;
    white-space: nowrap;
}

.ak-nama {
    font-weight: 600;
    color: #0f172a;
}

.ak-noreg {
    font-family: 'Inter', monospace;
    font-size: 0.82rem;
    font-weight: 600;
    color: #475569;
    letter-spacing: 0.05em;
}

.ak-qty-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 26px;
    padding: 0 9px;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 0.8rem;
    font-weight: 700;
}

.ak-empty-cell {
    text-align: center;
    padding: 44px 16px !important;
    color: #64748b;
    font-weight: 600;
}

/* Kolom Aktivitas */
.ak-actions {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
}

.ak-icon-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.ak-icon-btn--detail {
    color: #334155;
}

.ak-icon-btn--detail:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #0f172a;
    transform: translateY(-1px);
}

.ak-icon-btn--edit {
    color: #2563eb;
    border-color: #bfdbfe;
    background: #eff6ff;
}

.ak-icon-btn--edit:hover {
    background: #dbeafe;
    border-color: #93c5fd;
    transform: translateY(-1px);
}

.ak-btn-cancel {
    height: 32px;
    padding: 0 11px;
    border: 1px solid #fecaca;
    border-radius: 8px;
    background: #fef2f2;
    color: #dc2626;
    font-size: 0.77rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.ak-btn-cancel:hover {
    background: #dc2626;
    border-color: #dc2626;
    color: #ffffff;
    transform: translateY(-1px);
}

/* Pagination Bar */
.ak-pagination-bar {
    margin-top: 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
}

.ak-range-group,
.ak-page-group {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.ak-range-label {
    font-size: 0.82rem;
    font-weight: 700;
    color: #64748b;
    margin-right: 4px;
}

.ak-pg-btn {
    min-width: 34px;
    height: 33px;
    padding: 0 11px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #334155;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}

.ak-pg-btn:hover:not(:disabled):not(.is-active) {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
}

.ak-pg-btn.is-active {
    background: #c96a6a;
    border-color: #c96a6a;
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(201, 106, 106, 0.28);
}

.ak-pg-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.ak-pg-ellipsis {
    padding: 0 4px;
    color: #94a3b8;
    font-weight: 700;
}

/* Modal Overlay & Dialog */
.ak-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 900;
    background: rgba(15, 23, 42, 0.52);
    backdrop-filter: blur(4px);
    padding: 16px;
    align-items: center;
    justify-content: center;
    overflow-y: auto;
}

.swal2-container {
    z-index: 99999 !important;
}

.ak-modal-overlay.is-open {
    display: flex;
}

.ak-modal {
    width: 100%;
    max-width: 480px;
    background: #ffffff;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 24px 48px rgba(15, 23, 42, 0.2);
    overflow: hidden;
    animation: akModalIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes akModalIn {
    from {
        opacity: 0;
        transform: translateY(12px) scale(0.97);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.ak-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 22px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.ak-modal-title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.ak-modal-close {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #dc2626;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s ease;
}

.ak-modal-close:hover {
    background: #dc2626;
    color: #ffffff;
}

.ak-modal-body {
    padding: 20px 22px 22px;
    max-height: 80vh;
    overflow-y: auto;
}

.ak-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 13px;
}

.ak-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.ak-field--full {
    grid-column: 1 / -1;
}

.ak-label {
    font-size: 0.78rem;
    font-weight: 700;
    color: #334155;
}

.ak-input,
.ak-select {
    width: 100%;
    height: 40px;
    padding: 0 12px;
    border-radius: 9px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #0f172a;
    font-size: 0.86rem;
    font-family: inherit;
    box-sizing: border-box;
    outline: none;
    transition: all 0.15s ease;
}

.ak-input:focus,
.ak-select:focus {
    background: #ffffff;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.ak-input[readonly] {
    background: #f1f5f9;
    border-color: #e2e8f0;
    color: #475569;
    cursor: not-allowed;
}

.ak-search-wrap {
    position: relative;
}

.ak-search-wrap i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    font-size: 1rem;
    pointer-events: none;
}

.ak-search-wrap .ak-input {
    padding-left: 36px;
}

.ak-modal-footer {
    margin-top: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.ak-btn-submit {
    min-width: 150px;
    height: 41px;
    padding: 0 24px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #ffffff;
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    transition: all 0.16s ease;
}

.ak-btn-submit:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.32);
}

.ak-btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.ak-btn-reset {
    height: 39px;
    padding: 0 18px;
    border-radius: 10px;
    border: 1.5px solid #dc2626;
    background: #ffffff;
    color: #dc2626;
    font-size: 0.87rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.ak-btn-reset:hover {
    background: #fef2f2;
}

.ak-btn-apply {
    height: 39px;
    padding: 0 22px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #1d6fb8 0%, #155a96 100%);
    color: #ffffff;
    font-size: 0.87rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 3px 10px rgba(29, 111, 184, 0.24);
    transition: all 0.15s ease;
}

.ak-btn-apply:hover {
    transform: translateY(-1px);
}

/* Detail List */
.ak-detail-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ak-detail-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 9px 12px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    font-size: 0.85rem;
}

.ak-detail-key {
    font-weight: 700;
    color: #475569;
}

.ak-detail-val {
    font-weight: 600;
    color: #0f172a;
    text-align: right;
    word-break: break-word;
}

.ak-stock-hint {
    font-size: 0.74rem;
    font-weight: 600;
    color: #64748b;
}

/* Searchable Combobox Nama Aset */
.ak-combobox {
    position: relative;
}

.ak-combobox-input-wrap {
    position: relative;
}

.ak-combobox-input-wrap i.ak-cb-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    font-size: 0.98rem;
    pointer-events: none;
}

.ak-combobox-input-wrap .ak-input {
    padding-left: 36px;
    padding-right: 34px;
}

.ak-combobox-clear {
    display: none;
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 24px;
    height: 24px;
    border: none;
    border-radius: 6px;
    background: #e2e8f0;
    color: #475569;
    font-size: 0.78rem;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.ak-combobox-clear.is-visible {
    display: inline-flex;
}

.ak-combobox-clear:hover {
    background: #cbd5e1;
    color: #0f172a;
}

.ak-combobox-menu {
    display: none;
    position: absolute;
    left: 0;
    right: 0;
    top: calc(100% + 4px);
    z-index: 40;
    max-height: 210px;
    overflow-y: auto;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14);
    padding: 4px;
}

.ak-combobox-menu.is-open {
    display: block;
}

.ak-combobox-option {
    width: 100%;
    text-align: left;
    padding: 8px 11px;
    border: none;
    border-radius: 7px;
    background: transparent;
    color: #0f172a;
    font-size: 0.85rem;
    font-family: inherit;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.ak-combobox-option:hover,
.ak-combobox-option.is-highlighted {
    background: #eff6ff;
    color: #1d4ed8;
}

.ak-combobox-option-meta {
    font-size: 0.74rem;
    font-weight: 600;
    color: #64748b;
    flex-shrink: 0;
}

.ak-combobox-empty {
    padding: 12px;
    text-align: center;
    font-size: 0.83rem;
    color: #64748b;
}

@media (max-width: 640px) {
    .ak-form-grid {
        grid-template-columns: 1fr;
    }
    .ak-table-card {
        padding: 14px;
    }
}
</style>

<div class="app-layout">
    <?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-container ak-page">

            <!-- Header Halaman -->
            <div class="ak-header">
                <div>
                    <h1 class="ak-title" id="akPageTitle">Data Aset Kelas <?php echo htmlspecialchars((string) $kelasId); ?></h1>
                    <p class="ak-subtitle" id="akPageSubtitle">Data aset yang dimiliki ruang kelas <?php echo htmlspecialchars((string) $kelasId); ?></p>
                </div>

                <div class="ak-header-actions">
                    <div class="ak-total-card" aria-live="polite">
                        <div class="ak-total-icon">
                            <i class="ph-bold ph-chart-bar"></i>
                        </div>
                        <div class="ak-total-meta">
                            <span class="ak-total-label">Total Aset</span>
                            <span class="ak-total-value" id="akTotalAset">0</span>
                        </div>
                    </div>

                    <button type="button" class="ak-btn-filter" id="btnOpenFilterModal" title="Filter Data" aria-label="Filter Data">
                        <i class="ph-bold ph-faders"></i>
                        <span class="ak-filter-badge" id="akFilterBadge"></span>
                    </button>

                    <button type="button" class="ak-btn-klaim" id="btnOpenKlaimModal">
                        <i class="ph-bold ph-plus"></i>
                        <span>Klaim Aset</span>
                    </button>
                </div>
            </div>

            <!-- Kartu Tabel Data Aset -->
            <section class="ak-table-card">
                <div class="ak-table-wrap">
                    <table class="ak-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">No</th>
                                <th>Kode Aset</th>
                                <th>Jenis Aset</th>
                                <th>Nama Aset</th>
                                <th>No Registrasi</th>
                                <th style="width: 90px;">Jumlah</th>
                                <th style="width: 185px;">Aktivitas</th>
                            </tr>
                        </thead>
                        <tbody id="akTableBody">
                            <tr>
                                <td colspan="7" class="ak-empty-cell">Memuat data aset kelas...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination & Range -->
                <div class="ak-pagination-bar">
                    <div class="ak-range-group" id="akRangeControls">
                        <span class="ak-range-label">Range</span>
                        <button type="button" class="ak-pg-btn" data-range="5">5</button>
                        <button type="button" class="ak-pg-btn is-active" data-range="10">10</button>
                        <button type="button" class="ak-pg-btn" data-range="25">25</button>
                        <button type="button" class="ak-pg-btn" data-range="50">50</button>
                        <button type="button" class="ak-pg-btn" data-range="all">all</button>
                    </div>

                    <div class="ak-page-group" id="akPageControls"></div>
                </div>
            </section>

        </div>
    </main>
</div>

<!-- Modal 1: Klaim Aset (dan Edit Jumlah Klaim) -->
<div class="ak-modal-overlay" id="modalKlaim" role="dialog" aria-modal="true" aria-labelledby="modalKlaimTitle">
    <div class="ak-modal">
        <div class="ak-modal-header">
            <h2 class="ak-modal-title" id="modalKlaimTitle">Klaim Aset</h2>
            <button type="button" class="ak-modal-close" data-close-modal="modalKlaim" aria-label="Tutup">
                <i class="ph-bold ph-x"></i>
            </button>
        </div>

        <form id="formKlaimAset" class="ak-modal-body" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="kelas_id" value="<?php echo htmlspecialchars((string) $kelasId); ?>">
            <input type="hidden" name="klaim_id" id="klaimIdInput" value="">
            <input type="hidden" name="kib" id="klaimKibInput" value="">
            <input type="hidden" name="aset_id" id="klaimAsetIdInput" value="">

            <div class="ak-form-grid">
                <div class="ak-field">
                    <label class="ak-label" for="klaimKodeAset">Kode Aset</label>
                    <input type="text" class="ak-input" id="klaimKodeAset" placeholder="KIB-.../20../0.." readonly>
                </div>

                <div class="ak-field">
                    <label class="ak-label" for="klaimJenisAset">Jenis Aset</label>
                    <input type="text" class="ak-input" id="klaimJenisAset" placeholder="Otomatis dari Nama Aset" readonly>
                </div>

                <div class="ak-field">
                    <label class="ak-label" for="klaimJenisKib">Jenis KIB</label>
                    <input type="text" class="ak-input" id="klaimJenisKib" placeholder="Contoh: B" readonly>
                </div>

                <div class="ak-field">
                    <label class="ak-label" for="klaimNoReg">No Registrasi</label>
                    <input type="text" class="ak-input" id="klaimNoReg" name="no_reg" placeholder="00001" readonly>
                </div>

                <div class="ak-field ak-field--full">
                    <label class="ak-label" for="klaimNamaSearch">Nama Aset</label>
                    <div class="ak-combobox" id="klaimNamaCombobox">
                        <input type="hidden" id="klaimNamaAset" value="">
                        <div class="ak-combobox-input-wrap">
                            <i class="ph-bold ph-magnifying-glass ak-cb-icon"></i>
                            <input
                                type="text"
                                class="ak-input"
                                id="klaimNamaSearch"
                                placeholder="Ketik untuk mencari nama aset..."
                                autocomplete="off"
                            >
                            <button type="button" class="ak-combobox-clear" id="btnClearNamaAset" title="Hapus pilihan" aria-label="Hapus pilihan">
                                <i class="ph-bold ph-x"></i>
                            </button>
                        </div>
                        <div class="ak-combobox-menu" id="klaimNamaMenu" role="listbox"></div>
                    </div>
                </div>

                <div class="ak-field ak-field--full" id="klaimSpesifikasiGroup" style="display:none;">
                    <label class="ak-label" for="klaimSpesifikasi">Spesifikasi Aset</label>
                    <select class="ak-select" id="klaimSpesifikasi" required>
                        <option value="">Pilih spesifikasi aset</option>
                    </select>
                </div>

                <div class="ak-field">
                    <label class="ak-label" for="klaimTahun">Tahun Perolehan</label>
                    <input type="text" class="ak-input" id="klaimTahun" placeholder="-" readonly>
                </div>

                <div class="ak-field">
                    <label class="ak-label" for="klaimSumber">Sumber</label>
                    <input type="text" class="ak-input" id="klaimSumber" placeholder="-" readonly>
                </div>

                <div class="ak-field">
                    <label class="ak-label" for="klaimHarga">Harga</label>
                    <input type="text" class="ak-input" id="klaimHarga" placeholder="Rp. 0" readonly>
                </div>

                <div class="ak-field">
                    <label class="ak-label" for="klaimJumlah">Jumlah</label>
                    <input type="number" class="ak-input" id="klaimJumlah" name="jumlah" value="1" min="1" step="1" required>
                    <span class="ak-stock-hint" id="klaimStockHint"></span>
                </div>
            </div>

            <div class="ak-modal-footer">
                <button type="submit" class="ak-btn-submit" id="btnSubmitKlaim">Submit</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Filter Data -->
<div class="ak-modal-overlay" id="modalFilter" role="dialog" aria-modal="true" aria-labelledby="modalFilterTitle">
    <div class="ak-modal">
        <div class="ak-modal-header">
            <h2 class="ak-modal-title" id="modalFilterTitle">
                <i class="ph-bold ph-faders"></i>
                <span>Filter Data</span>
            </h2>
            <button type="button" class="ak-modal-close" data-close-modal="modalFilter" aria-label="Tutup">
                <i class="ph-bold ph-x"></i>
            </button>
        </div>

        <form id="formFilterAset" class="ak-modal-body" novalidate>
            <div class="ak-form-grid">
                <div class="ak-field ak-field--full">
                    <label class="ak-label" for="filterKeyword">Kata Kunci</label>
                    <div class="ak-search-wrap">
                        <i class="ph-bold ph-magnifying-glass"></i>
                        <input type="text" class="ak-input" id="filterKeyword" placeholder="Masukkan kata kunci...">
                    </div>
                </div>

                <div class="ak-field">
                    <label class="ak-label" for="filterKib">KIB</label>
                    <select class="ak-select" id="filterKib">
                        <option value="all">Semua KIB</option>
                        <option value="kib-a">KIB A</option>
                        <option value="kib-b">KIB B</option>
                        <option value="kib-c">KIB C</option>
                        <option value="kib-d">KIB D</option>
                        <option value="kib-e">KIB E</option>
                        <option value="kib-f">KIB F</option>
                    </select>
                </div>

                <div class="ak-field">
                    <label class="ak-label" for="filterJenis">Jenis Aset</label>
                    <select class="ak-select" id="filterJenis">
                        <option value="">Semua Jenis Aset</option>
                    </select>
                </div>

                <div class="ak-field">
                    <label class="ak-label" for="filterTahun">Tahun Perolehan</label>
                    <select class="ak-select" id="filterTahun">
                        <option value="">Pilih Tahun</option>
                    </select>
                </div>

                <div class="ak-field">
                    <label class="ak-label" for="filterSumber">Sumber</label>
                    <select class="ak-select" id="filterSumber">
                        <option value="">Semua Sumber</option>
                    </select>
                </div>
            </div>

            <div class="ak-modal-footer">
                <button type="button" class="ak-btn-reset" id="btnResetFilter">
                    <i class="ph-bold ph-funnel-x"></i>
                    <span>Reset</span>
                </button>
                <button type="submit" class="ak-btn-apply">Terapkan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Detail Aset -->
<div class="ak-modal-overlay" id="modalDetail" role="dialog" aria-modal="true" aria-labelledby="modalDetailTitle">
    <div class="ak-modal">
        <div class="ak-modal-header">
            <h2 class="ak-modal-title" id="modalDetailTitle">
                <i class="ph-bold ph-book-open"></i>
                <span>Detail Aset</span>
            </h2>
            <button type="button" class="ak-modal-close" data-close-modal="modalDetail" aria-label="Tutup">
                <i class="ph-bold ph-x"></i>
            </button>
        </div>

        <div class="ak-modal-body">
            <div class="ak-detail-list" id="akDetailContent"></div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    // Konfigurasi awal halaman dari server
    const KELAS_ID   = <?php echo (int) $kelasId; ?>;
    const CSRF_TOKEN = <?php echo json_encode($csrfToken); ?>;
    const API_BASE   = '../actions/';

    const state = {
        page: 1,
        range: '10',
        q: '',
        kib: 'all',
        jenis: '',
        tahun: '',
        sumber: '',
        nextNoReg: '00001',
        masterAssets: [],
        uniqueAssetNames: [],
        highlightedIndex: -1,
        maxStokTersedia: null,
        rows: []
    };

    const el = {
        tableBody: document.getElementById('akTableBody'),
        totalAset: document.getElementById('akTotalAset'),
        rangeControls: document.getElementById('akRangeControls'),
        pageControls: document.getElementById('akPageControls'),
        filterBadge: document.getElementById('akFilterBadge'),

        modalKlaim: document.getElementById('modalKlaim'),
        modalKlaimTitle: document.getElementById('modalKlaimTitle'),
        formKlaim: document.getElementById('formKlaimAset'),
        klaimId: document.getElementById('klaimIdInput'),
        klaimKib: document.getElementById('klaimKibInput'),
        klaimAsetId: document.getElementById('klaimAsetIdInput'),
        klaimKode: document.getElementById('klaimKodeAset'),
        klaimJenis: document.getElementById('klaimJenisAset'),
        klaimJenisKib: document.getElementById('klaimJenisKib'),
        klaimNoReg: document.getElementById('klaimNoReg'),
        klaimNama: document.getElementById('klaimNamaAset'),
        klaimNamaCombobox: document.getElementById('klaimNamaCombobox'),
        klaimNamaSearch: document.getElementById('klaimNamaSearch'),
        klaimNamaMenu: document.getElementById('klaimNamaMenu'),
        btnClearNama: document.getElementById('btnClearNamaAset'),
        klaimSpecGroup: document.getElementById('klaimSpesifikasiGroup'),
        klaimSpec: document.getElementById('klaimSpesifikasi'),
        klaimTahun: document.getElementById('klaimTahun'),
        klaimSumber: document.getElementById('klaimSumber'),
        klaimHarga: document.getElementById('klaimHarga'),
        klaimJumlah: document.getElementById('klaimJumlah'),
        klaimStockHint: document.getElementById('klaimStockHint'),
        btnSubmitKlaim: document.getElementById('btnSubmitKlaim'),

        modalFilter: document.getElementById('modalFilter'),
        formFilter: document.getElementById('formFilterAset'),
        filterKeyword: document.getElementById('filterKeyword'),
        filterKib: document.getElementById('filterKib'),
        filterJenis: document.getElementById('filterJenis'),
        filterTahun: document.getElementById('filterTahun'),
        filterSumber: document.getElementById('filterSumber'),

        modalDetail: document.getElementById('modalDetail'),
        detailContent: document.getElementById('akDetailContent')
    };

    function esc(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function notify(type, message) {
        if (window.Swal) {
            Swal.fire({
                icon: type,
                title: type === 'success' ? 'Berhasil' : 'Perhatian',
                text: message,
                timer: type === 'success' ? 1800 : undefined,
                showConfirmButton: type !== 'success',
                didOpen: function (popup) {
                    const container = popup.closest('.swal2-container');
                    if (container) {
                        container.style.zIndex = '99999';
                    }
                }
            });
        } else {
            alert(message);
        }
    }

    function openModal(modalEl) {
        if (!modalEl) return;
        modalEl.classList.add('is-open');
    }

    function closeModal(modalEl) {
        if (!modalEl) return;
        modalEl.classList.remove('is-open');
    }

    function closeAllModals() {
        closeModal(el.modalKlaim);
        closeModal(el.modalFilter);
        closeModal(el.modalDetail);
    }

    function updateFilterBadge() {
        const hasFilter = Boolean(
            state.q.trim() ||
            (state.kib && state.kib !== 'all') ||
            state.jenis ||
            state.tahun ||
            state.sumber
        );
        el.filterBadge.classList.toggle('is-active', hasFilter);
    }

    // Muat data tabel dari server
    async function loadTable(includeMaster = false) {
        el.tableBody.innerHTML = '<tr><td colspan="7" class="ak-empty-cell">Memuat data aset kelas...</td></tr>';

        const params = new URLSearchParams({
            kelas_id: String(KELAS_ID),
            page: String(state.page),
            range: state.range,
            q: state.q,
            kib: state.kib,
            jenis: state.jenis,
            tahun: state.tahun,
            sumber: state.sumber
        });

        if (includeMaster) {
            params.set('include_master', '1');
        }

        try {
            const res = await fetch(API_BASE + 'aset-kelas-list.php?' + params.toString(), {
                headers: { 'Accept': 'application/json' }
            });
            const json = await res.json();

            if (!res.ok || !json.success) {
                throw new Error(json.message || 'Gagal memuat data.');
            }

            const data = json.data || {};
            state.rows = data.rows || [];
            state.page = data.page || 1;
            state.nextNoReg = data.next_no_reg || '00001';

            if (Array.isArray(data.master_assets)) {
                state.masterAssets = data.master_assets;
                populateNamaAsetDropdown();
            }

            if (data.filter_options) {
                populateFilterOptions(data.filter_options);
            }

            el.totalAset.textContent = String(data.total_aset ?? 0);
            renderTableRows(data.rows || [], data.offset || 0);
            renderPagination(data.page || 1, data.total_pages || 1);
            updateFilterBadge();
        } catch (err) {
            el.tableBody.innerHTML = '<tr><td colspan="7" class="ak-empty-cell">' + esc(err.message) + '</td></tr>';
        }
    }

    function renderTableRows(rows, offset) {
        if (!rows.length) {
            el.tableBody.innerHTML = '<tr><td colspan="7" class="ak-empty-cell">Tidak ada data</td></tr>';
            return;
        }

        el.tableBody.innerHTML = rows.map(function (row, idx) {
            const no = offset + idx + 1;
            return (
                '<tr>' +
                    '<td class="ak-col-center">' + no + '.</td>' +
                    '<td class="ak-col-center"><span class="ak-kode">' + esc(row.kode_aset) + '</span></td>' +
                    '<td class="ak-col-center"><span class="ak-jenis-badge">' + esc(row.jenis_aset) + '</span></td>' +
                    '<td class="ak-nama">' + esc(row.nama_aset) + '</td>' +
                    '<td class="ak-col-center ak-noreg">' + esc(row.no_reg) + '</td>' +
                    '<td class="ak-col-center"><span class="ak-qty-pill">' + esc(row.jumlah) + '</span></td>' +
                    '<td class="ak-col-center">' +
                        '<div class="ak-actions">' +
                            '<button type="button" class="ak-icon-btn ak-icon-btn--detail" data-action="detail" data-id="' + row.id + '" title="Lihat Detail Aset" aria-label="Detail Aset">' +
                                '<i class="ph-bold ph-book-open"></i>' +
                            '</button>' +
                            '<button type="button" class="ak-icon-btn ak-icon-btn--edit" data-action="edit" data-id="' + row.id + '" title="Ubah Data Klaim" aria-label="Edit Klaim">' +
                                '<i class="ph-bold ph-pencil-simple"></i>' +
                            '</button>' +
                            '<button type="button" class="ak-btn-cancel" data-action="batalkan" data-id="' + row.id + '" data-nama="' + esc(row.nama_aset) + '">Batalkan</button>' +
                        '</div>' +
                    '</td>' +
                '</tr>'
            );
        }).join('');
    }

    function renderPagination(currentPage, totalPages) {
        const parts = [];
        parts.push('<button type="button" class="ak-pg-btn" data-page="' + (currentPage - 1) + '" ' + (currentPage <= 1 ? 'disabled' : '') + '>Previous</button>');

        const pagesToShow = [];
        for (let p = 1; p <= totalPages; p++) {
            if (p === 1 || p === totalPages || (p >= currentPage - 1 && p <= currentPage + 1)) {
                pagesToShow.push(p);
            }
        }

        let lastP = 0;
        pagesToShow.forEach(function (p) {
            if (lastP && p - lastP > 1) {
                parts.push('<span class="ak-pg-ellipsis">...</span>');
            }
            parts.push('<button type="button" class="ak-pg-btn ' + (p === currentPage ? 'is-active' : '') + '" data-page="' + p + '">' + p + '</button>');
            lastP = p;
        });

        parts.push('<button type="button" class="ak-pg-btn" data-page="' + (currentPage + 1) + '" ' + (currentPage >= totalPages ? 'disabled' : '') + '>Next</button>');
        el.pageControls.innerHTML = parts.join('');
    }

    function populateFilterOptions(opts) {
        const curJenis = el.filterJenis.value || state.jenis;
        const curTahun = el.filterTahun.value || state.tahun;
        const curSumber = el.filterSumber.value || state.sumber;

        el.filterJenis.innerHTML = '<option value="">Semua Jenis Aset</option>' +
            (opts.jenis || []).map(v => '<option value="' + esc(v) + '">' + esc(v) + '</option>').join('');
        el.filterJenis.value = curJenis;

        el.filterTahun.innerHTML = '<option value="">Pilih Tahun</option>' +
            (opts.tahun || []).map(v => '<option value="' + esc(v) + '">' + esc(v) + '</option>').join('');
        el.filterTahun.value = curTahun;

        el.filterSumber.innerHTML = '<option value="">Semua Sumber</option>' +
            (opts.sumber || []).map(v => '<option value="' + esc(v) + '">' + esc(v) + '</option>').join('');
        el.filterSumber.value = curSumber;
    }

    function populateNamaAsetDropdown() {
        const grouped = new Map();

        state.masterAssets.forEach(function (item) {
            if (!item.nama_aset) return;
            if (!grouped.has(item.nama_aset)) {
                grouped.set(item.nama_aset, {
                    nama: item.nama_aset,
                    jenis: item.jenis_aset || '',
                    count: 1
                });
            } else {
                grouped.get(item.nama_aset).count += 1;
            }
        });

        state.uniqueAssetNames = Array.from(grouped.values());
        renderNamaAsetMenu('');
    }

    function renderNamaAsetMenu(keyword) {
        const q = (keyword || '').trim().toLowerCase();
        const filtered = state.uniqueAssetNames.filter(function (item) {
            return !q || item.nama.toLowerCase().includes(q) || item.jenis.toLowerCase().includes(q);
        });

        state.highlightedIndex = -1;

        if (!filtered.length) {
            el.klaimNamaMenu.innerHTML = '<div class="ak-combobox-empty">Nama aset tidak ditemukan</div>';
            return;
        }

        el.klaimNamaMenu.innerHTML = filtered.slice(0, 60).map(function (item, idx) {
            return (
                '<button type="button" class="ak-combobox-option" role="option" data-nama="' + esc(item.nama) + '" data-idx="' + idx + '">' +
                    '<span>' + esc(item.nama) + '</span>' +
                    '<span class="ak-combobox-option-meta">' + esc(item.jenis) + '</span>' +
                '</button>'
            );
        }).join('');
    }

    function openNamaMenu() {
        if (el.klaimNamaSearch.disabled) return;
        renderNamaAsetMenu(el.klaimNamaSearch.value);
        el.klaimNamaMenu.classList.add('is-open');
    }

    function closeNamaMenu() {
        el.klaimNamaMenu.classList.remove('is-open');
        state.highlightedIndex = -1;
    }

    function selectNamaAset(nama) {
        el.klaimNama.value = nama;
        el.klaimNamaSearch.value = nama;
        el.btnClearNama.classList.toggle('is-visible', Boolean(nama));
        closeNamaMenu();
        onNamaAsetChange();
    }

    function resetKlaimFields() {
        el.klaimId.value = '';
        el.klaimKib.value = '';
        el.klaimAsetId.value = '';
        el.klaimKode.value = '';
        el.klaimJenis.value = '';
        el.klaimJenisKib.value = '';
        el.klaimNoReg.value = state.nextNoReg;
        el.klaimNama.value = '';
        el.klaimNamaSearch.disabled = false;
        el.klaimNamaSearch.value = '';
        el.btnClearNama.classList.remove('is-visible');
        closeNamaMenu();
        el.klaimSpec.disabled = false;
        el.klaimSpec.innerHTML = '<option value="">Pilih spesifikasi aset</option>';
        el.klaimSpecGroup.style.display = 'none';
        el.klaimTahun.value = '';
        el.klaimSumber.value = '';
        el.klaimHarga.value = '';
        el.klaimJumlah.value = '1';
        el.klaimJumlah.removeAttribute('max');
        state.maxStokTersedia = null;
        el.klaimStockHint.style.color = '';
        el.klaimStockHint.textContent = '';
    }

    function onNamaAsetChange() {
        const selectedName = el.klaimNama.value;
        el.klaimKib.value = '';
        el.klaimAsetId.value = '';
        el.klaimKode.value = '';
        el.klaimJenis.value = '';
        el.klaimJenisKib.value = '';
        el.klaimTahun.value = '';
        el.klaimSumber.value = '';
        el.klaimHarga.value = '';
        state.maxStokTersedia = null;
        el.klaimStockHint.style.color = '';
        el.klaimStockHint.textContent = '';

        if (!selectedName) {
            el.klaimSpecGroup.style.display = 'none';
            el.klaimSpec.innerHTML = '<option value="">Pilih spesifikasi aset</option>';
            return;
        }

        const matches = state.masterAssets.filter(item => item.nama_aset === selectedName);
        if (matches.length > 0) {
            el.klaimJenis.value = matches[0].jenis_aset || '';
            el.klaimJenisKib.value = matches[0].jenis_kib || '';
        }

        el.klaimSpec.innerHTML = '<option value="">Pilih spesifikasi aset</option>' +
            matches.map(function (item) {
                const key = item.kib + ':' + item.aset_id;
                const stokInfo = ' (Tersedia: ' + item.stok_tersedia + ')';
                return '<option value="' + esc(key) + '">' + esc(item.spesifikasi + stokInfo) + '</option>';
            }).join('');

        el.klaimSpecGroup.style.display = 'flex';
    }

    function onSpesifikasiChange() {
        const val = el.klaimSpec.value;
        if (!val) {
            el.klaimKib.value = '';
            el.klaimAsetId.value = '';
            el.klaimKode.value = '';
            el.klaimTahun.value = '';
            el.klaimSumber.value = '';
            el.klaimHarga.value = '';
            el.klaimStockHint.textContent = '';
            return;
        }

        const parts = val.split(':');
        const item = state.masterAssets.find(m => m.kib === parts[0] && String(m.aset_id) === parts[1]);
        if (!item) return;

        el.klaimKib.value = item.kib;
        el.klaimAsetId.value = String(item.aset_id);
        el.klaimKode.value = item.kode_aset;
        el.klaimJenis.value = item.jenis_aset;
        el.klaimJenisKib.value = item.jenis_kib;
        el.klaimNoReg.value = state.nextNoReg;
        el.klaimTahun.value = item.tahun;
        el.klaimSumber.value = item.sumber;
        el.klaimHarga.value = item.harga;

        state.maxStokTersedia = Number(item.stok_tersedia ?? 0);
        if (state.maxStokTersedia > 0) {
            el.klaimJumlah.max = String(state.maxStokTersedia);
            el.klaimStockHint.style.color = '';
            el.klaimStockHint.textContent = 'Stok tersedia: ' + state.maxStokTersedia;
        } else {
            el.klaimJumlah.max = '0';
            el.klaimStockHint.style.color = '#dc2626';
            el.klaimStockHint.textContent = 'Stok sudah habis diklaim (0 tersedia)';
        }
    }

    function openEditModal(id) {
        const row = state.rows.find(r => r.id === id);
        if (!row) return;

        el.modalKlaimTitle.textContent = 'Ubah Data Klaim';
        el.klaimId.value = String(row.id);
        el.klaimKib.value = row.kib;
        el.klaimAsetId.value = String(row.aset_id);
        el.klaimKode.value = row.kode_aset;
        el.klaimJenis.value = row.jenis_aset;
        el.klaimJenisKib.value = row.jenis_kib;
        el.klaimNoReg.value = row.no_reg;

        el.klaimNama.value = row.nama_aset;
        el.klaimNamaSearch.value = row.nama_aset;
        el.klaimNamaSearch.disabled = true;
        el.btnClearNama.classList.remove('is-visible');
        closeNamaMenu();

        el.klaimSpecGroup.style.display = 'flex';
        el.klaimSpec.innerHTML = '<option value="edit">' + esc(row.spesifikasi || '-') + '</option>';
        el.klaimSpec.value = 'edit';
        el.klaimSpec.disabled = true;

        el.klaimTahun.value = row.tahun;
        el.klaimSumber.value = row.sumber;
        el.klaimHarga.value = row.harga;
        el.klaimJumlah.value = String(row.jumlah);

        const masterItem = state.masterAssets.find(m => m.kib === row.kib && Number(m.aset_id) === Number(row.aset_id));
        if (masterItem) {
            state.maxStokTersedia = Number(masterItem.stok_tersedia ?? 0) + Number(row.jumlah ?? 0);
            el.klaimJumlah.max = String(state.maxStokTersedia);
            el.klaimStockHint.style.color = '';
            el.klaimStockHint.textContent = 'Maksimal stok tersedia: ' + state.maxStokTersedia;
        } else {
            state.maxStokTersedia = null;
            el.klaimJumlah.removeAttribute('max');
            el.klaimStockHint.style.color = '';
            el.klaimStockHint.textContent = 'Ubah jumlah klaim aset';
        }

        openModal(el.modalKlaim);
    }

    async function submitKlaim(e) {
        e.preventDefault();

        const isEdit = Boolean(el.klaimId.value);
        const jumlahVal = parseInt(el.klaimJumlah.value, 10);

        if (!isEdit && (!el.klaimKib.value || !el.klaimAsetId.value)) {
            notify('warning', 'Silakan pilih Nama Aset dan Spesifikasi Aset terlebih dahulu.');
            return;
        }

        if (isNaN(jumlahVal) || jumlahVal < 1) {
            notify('warning', 'Jumlah aset minimal adalah 1.');
            return;
        }

        if (state.maxStokTersedia !== null) {
            if (state.maxStokTersedia < 1) {
                notify('warning', 'Stok aset ini sudah habis diklaim.');
                return;
            }
            if (jumlahVal > state.maxStokTersedia) {
                notify('warning', 'Jumlah yang diinput (' + jumlahVal + ') melebihi stok yang tersedia (' + state.maxStokTersedia + ').');
                return;
            }
        }

        el.btnSubmitKlaim.disabled = true;
        el.btnSubmitKlaim.textContent = 'Menyimpan...';

        try {
            const fd = new FormData(el.formKlaim);
            fd.set('csrf_token', CSRF_TOKEN);
            fd.set('kelas_id', String(KELAS_ID));

            const res = await fetch(API_BASE + 'aset-kelas-klaim.php', {
                method: 'POST',
                body: fd,
                headers: { 'Accept': 'application/json' }
            });
            const json = await res.json();

            if (!res.ok || !json.success) {
                throw new Error(json.message || 'Gagal menyimpan klaim aset.');
            }

            closeModal(el.modalKlaim);
            await loadTable(true);
            notify('success', json.message || 'Klaim aset berhasil disimpan.');
        } catch (err) {
            notify('error', err.message);
        } finally {
            el.btnSubmitKlaim.disabled = false;
            el.btnSubmitKlaim.textContent = 'Submit';
        }
    }

    async function handleBatalkan(id, nama) {
        let confirmed = false;
        if (window.Swal) {
            const result = await Swal.fire({
                icon: 'warning',
                title: 'Batalkan Klaim?',
                text: 'Klaim aset "' + nama + '" akan dibatalkan dari ruang kelas ini.',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Batalkan',
                cancelButtonText: 'Tidak'
            });
            confirmed = Boolean(result.isConfirmed);
        } else {
            confirmed = window.confirm('Batalkan klaim aset "' + nama + '"?');
        }

        if (!confirmed) return;

        try {
            const fd = new FormData();
            fd.append('csrf_token', CSRF_TOKEN);
            fd.append('kelas_id', String(KELAS_ID));
            fd.append('id', String(id));

            const res = await fetch(API_BASE + 'aset-kelas-batalkan.php', {
                method: 'POST',
                body: fd,
                headers: { 'Accept': 'application/json' }
            });
            const json = await res.json();

            if (!res.ok || !json.success) {
                throw new Error(json.message || 'Gagal membatalkan klaim.');
            }

            await loadTable(true);
            notify('success', json.message || 'Klaim aset berhasil dibatalkan.');
        } catch (err) {
            notify('error', err.message);
        }
    }

    async function handleDetail(id) {
        el.detailContent.innerHTML = '<p style="text-align:center;color:#6b7280;">Memuat detail aset...</p>';
        openModal(el.modalDetail);

        try {
            const params = new URLSearchParams({
                kelas_id: String(KELAS_ID),
                id: String(id)
            });
            const res = await fetch(API_BASE + 'aset-kelas-detail.php?' + params.toString(), {
                headers: { 'Accept': 'application/json' }
            });
            const json = await res.json();

            if (!res.ok || !json.success) {
                throw new Error(json.message || 'Gagal memuat detail aset.');
            }

            const item = json.data.item;
            const rows = [
                ['Kode Aset', item.kode_aset],
                ['Jenis KIB', 'KIB ' + item.jenis_kib],
                ['Jenis Aset', item.jenis_aset],
                ['Nama Aset', item.nama_aset],
                ['No Registrasi', item.no_reg],
                ['Spesifikasi', item.spesifikasi || '-'],
                ['Tahun Perolehan', item.tahun || '-'],
                ['Sumber', item.sumber || '-'],
                ['Harga', item.harga || '-'],
                ['Jumlah Diklaim', String(item.jumlah)]
            ];

            el.detailContent.innerHTML = rows.map(function (pair) {
                return '<div class="ak-detail-row"><span class="ak-detail-key">' + esc(pair[0]) + '</span><span class="ak-detail-val">' + esc(pair[1]) + '</span></div>';
            }).join('');
        } catch (err) {
            el.detailContent.innerHTML = '<p style="text-align:center;color:#dc2626;">' + esc(err.message) + '</p>';
        }
    }

    // Event Listeners
    document.getElementById('btnOpenKlaimModal').addEventListener('click', function () {
        el.modalKlaimTitle.textContent = 'Klaim Aset';
        populateNamaAsetDropdown();
        resetKlaimFields();
        openModal(el.modalKlaim);
    });

    document.getElementById('btnOpenFilterModal').addEventListener('click', function () {
        el.filterKeyword.value = state.q;
        el.filterKib.value = state.kib;
        el.filterJenis.value = state.jenis;
        el.filterTahun.value = state.tahun;
        el.filterSumber.value = state.sumber;
        openModal(el.modalFilter);
    });

    el.klaimNamaSearch.addEventListener('focus', openNamaMenu);
    el.klaimNamaSearch.addEventListener('input', function () {
        el.klaimNama.value = '';
        el.btnClearNama.classList.toggle('is-visible', Boolean(this.value));
        onNamaAsetChange();
        openNamaMenu();
    });

    el.klaimNamaSearch.addEventListener('keydown', function (e) {
        const options = Array.from(el.klaimNamaMenu.querySelectorAll('.ak-combobox-option'));
        if (!options.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (!el.klaimNamaMenu.classList.contains('is-open')) openNamaMenu();
            state.highlightedIndex = Math.min(options.length - 1, state.highlightedIndex + 1);
            options.forEach((opt, i) => opt.classList.toggle('is-highlighted', i === state.highlightedIndex));
            options[state.highlightedIndex]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            state.highlightedIndex = Math.max(0, state.highlightedIndex - 1);
            options.forEach((opt, i) => opt.classList.toggle('is-highlighted', i === state.highlightedIndex));
            options[state.highlightedIndex]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter' && el.klaimNamaMenu.classList.contains('is-open')) {
            e.preventDefault();
            const target = options[state.highlightedIndex] || options[0];
            if (target) {
                selectNamaAset(target.getAttribute('data-nama') || '');
            }
        }
    });

    el.klaimNamaMenu.addEventListener('click', function (e) {
        const opt = e.target.closest('.ak-combobox-option');
        if (!opt) return;
        selectNamaAset(opt.getAttribute('data-nama') || '');
    });

    el.btnClearNama.addEventListener('click', function () {
        el.klaimNama.value = '';
        el.klaimNamaSearch.value = '';
        el.btnClearNama.classList.remove('is-visible');
        onNamaAsetChange();
        openNamaMenu();
        el.klaimNamaSearch.focus();
    });

    document.addEventListener('click', function (e) {
        if (!el.klaimNamaCombobox.contains(e.target)) {
            closeNamaMenu();
        }
    });

    el.klaimSpec.addEventListener('change', onSpesifikasiChange);
    el.klaimJumlah.addEventListener('change', function () {
        const val = parseInt(this.value, 10);
        if (state.maxStokTersedia !== null && !isNaN(val) && val > state.maxStokTersedia) {
            notify('warning', 'Jumlah yang diinput (' + val + ') melebihi stok yang tersedia (' + state.maxStokTersedia + ').');
        }
    });
    el.formKlaim.addEventListener('submit', submitKlaim);

    el.formFilter.addEventListener('submit', function (e) {
        e.preventDefault();
        state.q = el.filterKeyword.value.trim();
        state.kib = el.filterKib.value || 'all';
        state.jenis = el.filterJenis.value || '';
        state.tahun = el.filterTahun.value || '';
        state.sumber = el.filterSumber.value || '';
        state.page = 1;
        closeModal(el.modalFilter);
        loadTable(false);
    });

    document.getElementById('btnResetFilter').addEventListener('click', function () {
        el.filterKeyword.value = '';
        el.filterKib.value = 'all';
        el.filterJenis.value = '';
        el.filterTahun.value = '';
        el.filterSumber.value = '';
        state.q = '';
        state.kib = 'all';
        state.jenis = '';
        state.tahun = '';
        state.sumber = '';
        state.page = 1;
        closeModal(el.modalFilter);
        loadTable(false);
    });

    el.rangeControls.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-range]');
        if (!btn) return;
        const newRange = btn.getAttribute('data-range');
        if (!newRange || newRange === state.range) return;
        state.range = newRange;
        state.page = 1;
        el.rangeControls.querySelectorAll('.ak-pg-btn').forEach(b => b.classList.toggle('is-active', b === btn));
        loadTable(false);
    });

    el.pageControls.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-page]');
        if (!btn || btn.disabled) return;
        const targetPage = parseInt(btn.getAttribute('data-page'), 10);
        if (!isNaN(targetPage) && targetPage !== state.page) {
            state.page = targetPage;
            loadTable(false);
        }
    });

    el.tableBody.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        const action = btn.getAttribute('data-action');
        const id = parseInt(btn.getAttribute('data-id'), 10);
        if (action === 'detail') {
            handleDetail(id);
        } else if (action === 'edit') {
            openEditModal(id);
        } else if (action === 'batalkan') {
            handleBatalkan(id, btn.getAttribute('data-nama') || '');
        }
    });

    // Tutup modal via tombol X atau klik overlay
    document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            closeModal(document.getElementById(btn.getAttribute('data-close-modal')));
        });
    });

    [el.modalKlaim, el.modalFilter, el.modalDetail].forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                closeModal(overlay);
            }
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });

    // Muat data awal + master dropdown
    loadTable(true);
})();
</script>
