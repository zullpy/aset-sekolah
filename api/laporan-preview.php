<?php
/**
 * Endpoint Pratinjau & Simpan Pengaturan Laporan (AJAX)
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 * Lokasi: api/laporan-preview.php
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/conn.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/ajax.php';
require_once dirname(__DIR__) . '/includes/laporan-helper.php';

require_auth(['administrator', 'operator']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Metode request tidak diizinkan.', [], 405);
}

if (!verify_csrf_token()) {
    json_error('Token keamanan tidak valid. Muat ulang halaman dan coba lagi.', [], 403);
}

$parsed = laporan_parse_filters($_POST);
if (!$parsed['ok']) {
    json_error($parsed['message'], [], 422);
}

$filters = $parsed['data'];

// Simpan pengaturan laporan aktif di sesi pengguna
$_SESSION['laporan_filters'] = $filters;

// Jika mode=save (tombol Submit), kembalikan respon JSON sukses
$mode = trim((string) ($_POST['mode'] ?? 'preview'));
if ($mode === 'save') {
    json_success('Pengaturan laporan berhasil disimpan.', ['filters' => $filters]);
}

try {
    $rows     = laporan_fetch_rows($pdo, $filters);
    $pdfBytes = laporan_render_pdf_bytes($filters, $rows);
    $filename = 'laporan-inventaris-aset-' . date('Ymd-His') . '.pdf';

    if (ob_get_length()) {
        ob_clean();
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfBytes));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo $pdfBytes;
    exit;
} catch (Throwable $e) {
    error_log('Gagal membuat pratinjau laporan: ' . $e->getMessage());
    json_error('Gagal membuat pratinjau PDF laporan.', [], 500);
}
