<?php
/**
 * Endpoint Pratinjau Laporan PDF (POST)
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 * Lokasi: actions/laporan_preview.php
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/conn.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/ajax.php';
require_once dirname(__DIR__) . '/includes/laporan-helper.php';

require_auth('administrator');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Metode request tidak diizinkan.', [], 405);
}

if (!verify_csrf_token()) {
    json_error('Token keamanan tidak valid. Muat ulang halaman dan coba lagi.', [], 403);
}

// Validasi input dan whitelist nilai filter
$parsed = laporan_parse_filters($_POST, $pdo);
if (!$parsed['ok']) {
    json_error($parsed['message'], [], 422);
}

$filters = $parsed['data'];
$_SESSION['laporan_filters'] = $filters;

// Jika mode=save (tombol Submit), simpan filter di sesi dan kembalikan JSON sukses
$mode = trim((string) ($_POST['mode'] ?? 'preview'));
if ($mode === 'save') {
    json_success('Pengaturan laporan berhasil disimpan.', ['filters' => $filters]);
}

try {
    $rows     = laporan_fetch_rows($pdo, $filters);
    $pdfBytes = laporan_render_pdf_bytes($filters, $rows);

    $jenisMap = laporan_jenis_options();
    $namaFile = ($filters['jenis_laporan'] !== '' && isset($jenisMap[$filters['jenis_laporan']]))
        ? $jenisMap[$filters['jenis_laporan']]
        : 'Laporan Data Aset';

    if (ob_get_length()) {
        ob_clean();
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $namaFile . '.pdf"');
    header('X-Laporan-Title: ' . $namaFile);
    header('Content-Length: ' . strlen($pdfBytes));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo $pdfBytes;
    exit;
} catch (Throwable $e) {
    error_log('Gagal membuat pratinjau PDF laporan: ' . $e->getMessage());
    json_error('Gagal membuat pratinjau PDF laporan.', [], 500);
}
