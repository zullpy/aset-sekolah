<?php
/**
 * Endpoint Ekspor / Download PDF Laporan Inventaris Aset
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 * Lokasi: api/laporan-export.php
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/conn.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/laporan-helper.php';

require_auth('administrator');

$input = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : ($_SESSION['laporan_filters'] ?? $_GET);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf_token()) {
    http_response_code(403);
    exit('Token keamanan tidak valid.');
}

$parsed = laporan_parse_filters($input, $pdo);
if (!$parsed['ok']) {
    http_response_code(422);
    exit(htmlspecialchars($parsed['message'], ENT_QUOTES, 'UTF-8'));
}

$filters = $parsed['data'];
$_SESSION['laporan_filters'] = $filters;

try {
    $rows     = laporan_fetch_rows($pdo, $filters);
    $pdfBytes = laporan_render_pdf_bytes($filters, $rows);
    $filename = 'laporan-inventaris-aset-' . date('Ymd-His') . '.pdf';

    if (ob_get_length()) {
        ob_clean();
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfBytes));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo $pdfBytes;
    exit;
} catch (Throwable $e) {
    error_log('Gagal mengekspor laporan PDF: ' . $e->getMessage());
    http_response_code(500);
    exit('Terjadi kesalahan saat mengekspor laporan.');
}
