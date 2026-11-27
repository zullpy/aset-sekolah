<?php
/**
 * Endpoint POST - Batalkan Klaim Aset Kelas
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 * Lokasi: actions/aset_kelas_batalkan.php
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/conn.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/ajax.php';
require_once dirname(__DIR__) . '/includes/aset-kelas-helper.php';

require_auth(['administrator', 'operator']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_error('Metode request tidak diizinkan.', [], 405);
}

if (!verify_csrf_token()) {
    json_error('Token keamanan (CSRF) tidak valid. Silakan muat ulang halaman.', [], 403);
}

$currentUser = current_user();
$kelasId = resolve_kelas_id($_POST['kelas_id'] ?? null, $currentUser);
if ($kelasId === null) {
    json_error('Parameter kelas tidak valid.', [], 400);
}

if (!user_can_access_kelas($currentUser, $kelasId)) {
    json_error('Anda tidak memiliki izin untuk membatalkan klaim aset di kelas ini.', [], 403);
}

$klaimId = (int) ($_POST['id'] ?? 0);
if ($klaimId <= 0) {
    json_error('ID klaim aset tidak valid.', [], 422);
}

try {
    ensure_klaim_aset_table($pdo);

    $stmtCheck = $pdo->prepare("SELECT id, nama_aset FROM klaim_aset WHERE id = ? AND kelas_id = ? LIMIT 1");
    $stmtCheck->execute([$klaimId, $kelasId]);
    $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        json_error('Data klaim aset tidak ditemukan atau bukan milik kelas ini.', [], 404);
    }

    $stmtDelete = $pdo->prepare("DELETE FROM klaim_aset WHERE id = ? AND kelas_id = ?");
    $stmtDelete->execute([$klaimId, $kelasId]);

    json_success('Klaim aset berhasil dibatalkan.', ['id' => $klaimId]);
} catch (Throwable $e) {
    error_log('aset_kelas_batalkan error: ' . $e->getMessage());
    json_error('Gagal membatalkan klaim aset.', [], 500);
}
