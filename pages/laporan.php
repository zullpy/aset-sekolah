<?php
/**
 * Halaman Laporan Inventaris Aset Sekolah
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/conn.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/laporan-helper.php';

require_auth(['administrator', 'operator']);

$pageTitle     = 'Laporan - Sistem Informasi Inventaris Aset SDN Budi Karya';
$activeMenu    = 'laporan';
$activeSubmenu = '';

// Ambil opsi filter dari helper & database
$jenisLaporanOptions = laporan_jenis_options();
$kibOptions          = laporan_kib_options();
$lokasiOptions       = laporan_get_lokasi_options($pdo);
$kondisiOptions      = laporan_kondisi_options();
$statusOptions       = laporan_get_status_options($pdo);

// Nilai default Titimangsa
$namaInstansiDefault = !empty($_SESSION['nama_instansi'])
    ? (string) $_SESSION['nama_instansi']
    : 'SDN BUDIKARYA';

$tanggalIsoDefault        = date('Y-m-d');
$tanggalPembuatanDefault  = laporan_format_titimangsa_tanggal($tanggalIsoDefault);

require_once dirname(__DIR__) . '/includes/header.php';
?>

<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/dashboard.css', ENT_QUOTES, 'UTF-8'); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/kib.css', ENT_QUOTES, 'UTF-8'); ?>">

<style>
/* Gaya khusus halaman Laporan */
.lap-container {
    font-family: 'Inter', 'Plus Jakarta Sans', sans-serif;
}

.lap-page-header {
    margin-bottom: 24px;
}

.lap-page-title {
    font-family: 'Inter', 'Plus Jakarta Sans', sans-serif;
    font-size: 22px;
    font-weight: 800;
    letter-spacing: 0.4px;
    color: #0f172a;
    text-decoration: underline;
    text-decoration-thickness: 3px;
    text-underline-offset: 7px;
    margin: 0 0 8px;
}

.lap-page-subtitle {
    font-size: 13.5px;
    font-weight: 600;
    color: #6b7280;
    margin: 0;
}

.lap-card {
    background-color: #f5f5f5;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 22px 24px;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
    margin-bottom: 20px;
}

.lap-grid-2col {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.25fr);
    gap: 28px;
    align-items: start;
}

@media (max-width: 900px) {
    .lap-grid-2col {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

.lap-section-heading {
    font-size: 14.5px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 12px;
}

.lap-section-heading--spaced {
    margin-top: 18px;
}

.lap-field {
    margin-bottom: 14px;
}

.lap-field:last-child {
    margin-bottom: 0;
}

.lap-label {
    display: block;
    font-size: 12.5px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

.lap-input,
.lap-select {
    width: 100%;
    height: 40px;
    padding: 0 12px;
    font-family: 'Inter', sans-serif;
    font-size: 13.5px;
    color: #111827;
    background-color: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.lap-input:focus,
.lap-select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.lap-date-display-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.lap-date-display-wrap .lap-input {
    padding-right: 40px;
    background-color: #ffffff;
    cursor: pointer;
}

.lap-date-picker-trigger {
    position: absolute;
    right: 6px;
    width: 30px;
    height: 30px;
    border: none;
    background: transparent;
    color: #4b5563;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.lap-date-picker-trigger:hover {
    background-color: #f3f4f6;
    color: #111827;
}

.lap-date-native-hidden {
    position: absolute;
    opacity: 0;
    pointer-events: none;
    width: 1px;
    height: 1px;
    right: 8px;
    bottom: 0;
}

/* Grid 2 kolom x 3 baris untuk Filter Data */
.lap-filter-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

@media (max-width: 640px) {
    .lap-filter-grid {
        grid-template-columns: 1fr;
    }
}

/* Tombol aksi form */
.lap-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid #e5e7eb;
}

.lap-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    height: 40px;
    padding: 0 18px;
    font-family: 'Inter', sans-serif;
    font-size: 13.5px;
    font-weight: 600;
    border-radius: 8px;
    border: 1.5px solid transparent;
    cursor: pointer;
    transition: all 0.16s ease;
}

.lap-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.lap-btn-submit {
    background-color: #2563eb;
    color: #ffffff;
}

.lap-btn-submit:hover:not(:disabled) {
    background-color: #1d4ed8;
}

.lap-btn-reset {
    background-color: #ffffff;
    color: #dc2626;
    border-color: #dc2626;
}

.lap-btn-reset:hover:not(:disabled) {
    background-color: #fef2f2;
}

.lap-btn-preview {
    background-color: #16a34a;
    color: #ffffff;
}

.lap-btn-preview:hover:not(:disabled) {
    background-color: #15803d;
}

.lap-btn-export {
    background-color: #16a34a;
    color: #ffffff;
}

.lap-btn-export:hover:not(:disabled) {
    background-color: #15803d;
}

/* PDF Viewer dengan toolbar gelap */
.lap-pdf-viewer {
    margin-top: 16px;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #374151;
    background-color: #374151;
}

.lap-pdf-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 10px 16px;
    background-color: #1f2937;
    color: #f9fafb;
    font-size: 13px;
}

.lap-pdf-filename {
    font-weight: 600;
    color: #f3f4f6;
    word-break: break-all;
}

.lap-pdf-controls {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}

.lap-pdf-zoom-group {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background-color: #111827;
    padding: 3px 8px;
    border-radius: 6px;
}

.lap-pdf-tool-btn {
    background-color: #374151;
    color: #f9fafb;
    border: none;
    border-radius: 5px;
    min-width: 28px;
    height: 26px;
    padding: 0 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.lap-pdf-tool-btn:hover {
    background-color: #4b5563;
}

.lap-pdf-stage {
    padding: 18px;
    background-color: #4b5563;
    overflow: auto;
    max-height: 700px;
    display: flex;
    justify-content: center;
}

.lap-pdf-frame-wrap {
    width: 100%;
    max-width: 880px;
    transform-origin: top center;
    transition: transform 0.15s ease;
}

.lap-pdf-iframe {
    width: 100%;
    height: 620px;
    border: none;
    border-radius: 4px;
    background-color: #ffffff;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
}
</style>

<div class="app-layout">
    <?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-container lap-container">

            <!-- Header Halaman -->
            <div class="lap-page-header">
                <h1 class="lap-page-title">LAPORAN</h1>
                <p class="lap-page-subtitle">Buat dan ekspor laporan data inventaris aset sekolah</p>
            </div>

            <!-- Kartu 1: Form Pengaturan Laporan -->
            <section class="lap-card" aria-label="Form Pengaturan Laporan">
                <form id="formLaporan" method="POST" action="../api/laporan-export.php" novalidate>
                    <?php echo csrf_input(); ?>

                    <div class="lap-grid-2col">
                        <!-- Kolom Kiri: Jenis Laporan & Titimangsa -->
                        <div>
                            <h2 class="lap-section-heading">1. Jenis Laporan</h2>
                            <div class="lap-field">
                                <label class="lap-label" for="jenisLaporan">Pilih Jenis Laporan</label>
                                <select id="jenisLaporan" name="jenis_laporan" class="lap-select" data-default="">
                                    <?php foreach ($jenisLaporanOptions as $val => $label): ?>
                                        <option value="<?php echo htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <h2 class="lap-section-heading lap-section-heading--spaced">2. Titimangsa</h2>
                            <div class="lap-field">
                                <label class="lap-label" for="namaInstansi">Nama Instansi</label>
                                <input
                                    type="text"
                                    id="namaInstansi"
                                    name="nama_instansi"
                                    class="lap-input"
                                    value="<?php echo htmlspecialchars($namaInstansiDefault, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-default="<?php echo htmlspecialchars($namaInstansiDefault, ENT_QUOTES, 'UTF-8'); ?>"
                                    placeholder="SDN BUDIKARYA"
                                >
                            </div>

                            <div class="lap-field">
                                <label class="lap-label" for="tanggalPembuatan">Tanggal Pembuatan Laporan</label>
                                <div class="lap-date-display-wrap">
                                    <input
                                        type="text"
                                        id="tanggalPembuatan"
                                        name="tanggal_pembuatan"
                                        class="lap-input"
                                        value="<?php echo htmlspecialchars($tanggalPembuatanDefault, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-default="<?php echo htmlspecialchars($tanggalPembuatanDefault, ENT_QUOTES, 'UTF-8'); ?>"
                                        readonly
                                    >
                                    <button type="button" id="btnPickTanggalPembuatan" class="lap-date-picker-trigger" title="Pilih Tanggal Pembuatan Laporan" aria-label="Pilih Tanggal Pembuatan Laporan">
                                        <i class="ph-bold ph-calendar-blank"></i>
                                    </button>
                                    <input
                                        type="date"
                                        id="tanggalPembuatanPicker"
                                        class="lap-date-native-hidden"
                                        value="<?php echo htmlspecialchars($tanggalIsoDefault, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-default="<?php echo htmlspecialchars($tanggalIsoDefault, ENT_QUOTES, 'UTF-8'); ?>"
                                        tabindex="-1"
                                        aria-hidden="true"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Kanan: 3. Filter Data (Grid 2 kolom x 3 baris) -->
                        <div>
                            <h2 class="lap-section-heading">3. Filter Data</h2>
                            <div class="lap-filter-grid">
                                <div class="lap-field">
                                    <label class="lap-label" for="periodeAwal">Periode Awal (dd/mm/yy)</label>
                                    <input
                                        type="date"
                                        id="periodeAwal"
                                        name="periode_awal"
                                        class="lap-input"
                                        value=""
                                        data-default=""
                                    >
                                </div>

                                <div class="lap-field">
                                    <label class="lap-label" for="periodeAkhir">Periode Akhir (dd/mm/yy)</label>
                                    <input
                                        type="date"
                                        id="periodeAkhir"
                                        name="periode_akhir"
                                        class="lap-input"
                                        value=""
                                        data-default=""
                                    >
                                </div>

                                <div class="lap-field">
                                    <label class="lap-label" for="filterKib">KIB</label>
                                    <select id="filterKib" name="kib" class="lap-select" data-default="">
                                        <?php foreach ($kibOptions as $val => $label): ?>
                                            <option value="<?php echo htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="lap-field">
                                    <label class="lap-label" for="filterLokasi">Lokasi</label>
                                    <select id="filterLokasi" name="lokasi" class="lap-select" data-default="">
                                        <?php foreach ($lokasiOptions as $val => $label): ?>
                                            <option value="<?php echo htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="lap-field">
                                    <label class="lap-label" for="filterKondisi">Kondisi</label>
                                    <select id="filterKondisi" name="kondisi" class="lap-select" data-default="">
                                        <?php foreach ($kondisiOptions as $val => $label): ?>
                                            <option value="<?php echo htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="lap-field">
                                    <label class="lap-label" for="filterStatus">Status</label>
                                    <select id="filterStatus" name="status" class="lap-select" data-default="">
                                        <?php foreach ($statusOptions as $val => $label): ?>
                                            <option value="<?php echo htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi (Rata Kanan) -->
                    <div class="lap-actions">
                        <button type="button" id="btnSubmitLaporan" class="lap-btn lap-btn-submit">
                            <span>Submit</span>
                        </button>
                        <button type="button" id="btnResetLaporan" class="lap-btn lap-btn-reset">
                            <i class="ph-bold ph-funnel-x" aria-hidden="true"></i>
                            <span>Reset</span>
                        </button>
                        <button type="button" id="btnPreviewLaporan" class="lap-btn lap-btn-preview">
                            <i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i>
                            <span>Pratinjau</span>
                        </button>
                    </div>
                </form>
            </section>

            <!-- Kartu 2: 4. Ekspor Laporan -->
            <section class="lap-card" aria-label="4. Ekspor Laporan">
                <h2 class="lap-section-heading">4. Ekspor Laporan</h2>
                <button type="button" id="btnExportLaporan" class="lap-btn lap-btn-export">
                    <i class="ph-bold ph-download-simple" aria-hidden="true"></i>
                    <span>Ekspor Laporan</span>
                </button>

                <!-- Area PDF Viewer: disembunyikan sebelum tombol Pratinjau diklik -->
                <div id="pdfViewerContainer" class="lap-pdf-viewer" style="display: none;">
                    <div class="lap-pdf-toolbar">
                        <span id="pdfFileName" class="lap-pdf-filename">Laporan Data Aset</span>

                        <div class="lap-pdf-controls">
                            <span id="pdfPageIndicator">1 / 1</span>

                            <div class="lap-pdf-zoom-group">
                                <button type="button" id="btnZoomOut" class="lap-pdf-tool-btn" title="Perkecil" aria-label="Perkecil">-</button>
                                <span id="pdfZoomLabel">100%</span>
                                <button type="button" id="btnZoomIn" class="lap-pdf-tool-btn" title="Perbesar" aria-label="Perbesar">+</button>
                            </div>

                            <button type="button" id="btnRotatePdf" class="lap-pdf-tool-btn" title="Putar 90 derajat" aria-label="Putar 90 derajat">
                                <i class="ph-bold ph-arrow-clockwise" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="lap-pdf-stage">
                        <div id="pdfFrameWrapper" class="lap-pdf-frame-wrap">
                            <iframe id="pdfPreviewFrame" class="lap-pdf-iframe" title="Pratinjau PDF Laporan"></iframe>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </main>
</div>

<script>
(() => {
    const form              = document.getElementById('formLaporan');
    const periodeAwal       = document.getElementById('periodeAwal');
    const periodeAkhir      = document.getElementById('periodeAkhir');
    const tanggalDisplay    = document.getElementById('tanggalPembuatan');
    const tanggalPicker     = document.getElementById('tanggalPembuatanPicker');
    const btnPickTanggal    = document.getElementById('btnPickTanggalPembuatan');

    const btnSubmit         = document.getElementById('btnSubmitLaporan');
    const btnReset          = document.getElementById('btnResetLaporan');
    const btnPreview        = document.getElementById('btnPreviewLaporan');
    const btnExport         = document.getElementById('btnExportLaporan');

    const viewerContainer   = document.getElementById('pdfViewerContainer');
    const pdfFrame          = document.getElementById('pdfPreviewFrame');
    const pdfWrapper        = document.getElementById('pdfFrameWrapper');
    const pdfFileName       = document.getElementById('pdfFileName');
    const pdfZoomLabel      = document.getElementById('pdfZoomLabel');
    const btnZoomIn         = document.getElementById('btnZoomIn');
    const btnZoomOut        = document.getElementById('btnZoomOut');
    const btnRotate         = document.getElementById('btnRotatePdf');

    const namaHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    let zoomLevel = 100;
    let rotationDeg = 0;
    let currentBlobUrl = null;

    // Format tanggal ISO (YYYY-MM-DD) menjadi "Hari, dd/mm/yy"
    function formatTanggalIndonesia(isoStr) {
        if (!isoStr || !/^\d{4}-\d{2}-\d{2}$/.test(isoStr)) return '';
        const [y, m, d] = isoStr.split('-').map(Number);
        const dt = new Date(y, m - 1, d);
        const hari = namaHari[dt.getDay()] || 'Senin';
        const dd = String(d).padStart(2, '0');
        const mm = String(m).padStart(2, '0');
        const yy = String(y).slice(-2);
        return `${hari}, ${dd}/${mm}/${yy}`;
    }

    function openTanggalPicker() {
        if (typeof tanggalPicker.showPicker === 'function') {
            tanggalPicker.showPicker();
        } else {
            tanggalPicker.focus();
            tanggalPicker.click();
        }
    }

    btnPickTanggal.addEventListener('click', openTanggalPicker);
    tanggalDisplay.addEventListener('click', openTanggalPicker);
    tanggalPicker.addEventListener('change', () => {
        const formatted = formatTanggalIndonesia(tanggalPicker.value);
        if (formatted) {
            tanggalDisplay.value = formatted;
        }
    });

    function showNotice(icon, title, text) {
        if (window.Swal) {
            Swal.fire({ icon, title, text });
        } else {
            alert(`${title}: ${text}`);
        }
    }

    // Validasi: Periode Akhir tidak boleh lebih awal dari Periode Awal
    function validatePeriod() {
        if (periodeAwal.value && periodeAkhir.value && periodeAkhir.value < periodeAwal.value) {
            showNotice('warning', 'Periode Tidak Valid', 'Periode Akhir tidak boleh lebih awal dari Periode Awal.');
            return false;
        }
        return true;
    }

    function getCsrfHeader() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? { 'X-CSRF-Token': meta.getAttribute('content') } : {};
    }

    function applyPdfTransform() {
        pdfWrapper.style.transform = `scale(${zoomLevel / 100}) rotate(${rotationDeg}deg)`;
        pdfZoomLabel.textContent = `${zoomLevel}%`;
    }

    // Pratinjau PDF via AJAX (fetch) tanpa reload halaman
    btnPreview.addEventListener('click', async () => {
        if (!validatePeriod()) return;

        const originalPreviewHtml = btnPreview.innerHTML;
        btnPreview.disabled = true;
        btnPreview.innerHTML = '<i class="ph-bold ph-spinner" aria-hidden="true"></i><span>Memuat...</span>';

        try {
            const formData = new FormData(form);
            formData.append('mode', 'preview');

            const response = await fetch('../actions/laporan_preview.php', {
                method: 'POST',
                headers: getCsrfHeader(),
                body: formData,
            });

            if (!response.ok) {
                let errMsg = `Gagal memuat pratinjau laporan (HTTP ${response.status}).`;
                const ct = response.headers.get('Content-Type') || '';
                if (ct.includes('application/json')) {
                    const errJson = await response.json();
                    errMsg = errJson.message || errMsg;
                }
                throw new Error(errMsg);
            }

            const titleHeader = response.headers.get('X-Laporan-Title');
            pdfFileName.textContent = titleHeader ? titleHeader : 'Laporan Data Aset';

            const blob = await response.blob();
            if (currentBlobUrl) {
                URL.revokeObjectURL(currentBlobUrl);
            }
            currentBlobUrl = URL.createObjectURL(blob);

            zoomLevel = 100;
            rotationDeg = 0;
            applyPdfTransform();

            pdfFrame.src = `${currentBlobUrl}#toolbar=0&navpanes=0`;
            viewerContainer.style.display = 'block';
        } catch (err) {
            showNotice('error', 'Pratinjau Gagal', err.message || 'Terjadi kesalahan saat memuat pratinjau.');
        } finally {
            btnPreview.disabled = false;
            btnPreview.innerHTML = originalPreviewHtml;
        }
    });

    // Submit: menyimpan pengaturan laporan aktif
    btnSubmit.addEventListener('click', async () => {
        if (!validatePeriod()) return;

        btnSubmit.disabled = true;
        try {
            const formData = new FormData(form);
            formData.append('mode', 'save');

            const response = await fetch('../actions/laporan_preview.php', {
                method: 'POST',
                headers: getCsrfHeader(),
                body: formData,
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Gagal menyimpan pengaturan laporan.');
            }
            showNotice('success', 'Tersimpan', result.message || 'Pengaturan laporan berhasil disimpan.');
        } catch (err) {
            showNotice('error', 'Gagal Menyimpan', err.message || 'Terjadi kesalahan saat menyimpan pengaturan.');
        } finally {
            btnSubmit.disabled = false;
        }
    });

    // Reset: kembalikan semua field ke default dan sembunyikan area pratinjau
    btnReset.addEventListener('click', () => {
        form.querySelectorAll('[data-default]').forEach((el) => {
            el.value = el.getAttribute('data-default') ?? '';
        });

        viewerContainer.style.display = 'none';
        pdfFrame.removeAttribute('src');
        if (currentBlobUrl) {
            URL.revokeObjectURL(currentBlobUrl);
            currentBlobUrl = null;
        }
        zoomLevel = 100;
        rotationDeg = 0;
        applyPdfTransform();
    });

    // Ekspor Laporan: download PDF sesuai filter aktif
    btnExport.addEventListener('click', () => {
        if (!validatePeriod()) return;
        form.action = '../api/laporan-export.php';
        form.submit();
    });

    // Kontrol Zoom & Rotate toolbar PDF viewer
    btnZoomIn.addEventListener('click', () => {
        zoomLevel = Math.min(200, zoomLevel + 10);
        applyPdfTransform();
    });

    btnZoomOut.addEventListener('click', () => {
        zoomLevel = Math.max(50, zoomLevel - 10);
        applyPdfTransform();
    });

    btnRotate.addEventListener('click', () => {
        rotationDeg = (rotationDeg + 90) % 360;
        applyPdfTransform();
    });
})();
</script>

</body>
</html>
