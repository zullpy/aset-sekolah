<?php
/**
 * Endpoint GET - Detail Aset Kelas
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 * Lokasi: actions/aset_kelas_detail.php
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/conn.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/ajax.php';
require_once dirname(__DIR__) . '/includes/aset-kelas-helper.php';

require_auth(['administrator', 'operator']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    json_error('Metode request tidak diizinkan.', [], 405);
}

$currentUser = current_user();
$kelasId = resolve_kelas_id($_GET['kelas_id'] ?? null, $currentUser);
if ($kelasId === null) {
    json_error('Parameter kelas tidak valid.', [], 400);
}

if (!user_can_access_kelas($currentUser, $kelasId)) {
    json_error('Anda tidak memiliki izin untuk melihat detail aset kelas ini.', [], 403);
}

$klaimId = (int) ($_GET['id'] ?? 0);
if ($klaimId <= 0) {
    json_error('ID klaim aset tidak valid.', [], 422);
}

release_session_lock();

try {
    ensure_klaim_aset_table($pdo);

    $stmt = $pdo->prepare("SELECT * FROM klaim_aset WHERE id = ? AND kelas_id = ? LIMIT 1");
    $stmt->execute([$klaimId, $kelasId]);
    $klaim = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$klaim) {
        json_error('Data klaim aset tidak ditemukan.', [], 404);
    }

    $master = fetch_master_aset_by_id($pdo, $klaim['kib'], (int) $klaim['aset_id']);
    $extra = [];
    if ($master && !empty($master['raw'])) {
        $raw = $master['raw'];
        foreach (['merk', 'bahan', 'no_pabrik', 'luas', 'lokasi', 'status_tanah', 'sertifikat', 'penggunaan', 'konstruksi', 'ukuran', 'dokumen', 'spesifikasi_judul', 'spesifikasi_pengarang', 'spesifikasi_penerbit', 'tingkat', 'beton', 'letak_bangunan', 'persen_fisik', 'status_pengerjaan', 'keterangan'] as $field) {
            if (isset($raw[$field]) && trim((string) $raw[$field]) !== '') {
                $extra[$field] = trim((string) $raw[$field]);
            }
        }
    }

    $klaim['id']       = (int) $klaim['id'];
    $klaim['kelas_id'] = (int) $klaim['kelas_id'];
    $klaim['aset_id']  = (int) $klaim['aset_id'];
    $klaim['jumlah']   = (int) $klaim['jumlah'];
    $klaim['no_reg']   = format_no_reg_5digit((string) $klaim['no_reg']);
    $klaim['extra']    = $extra;

    json_success('Detail aset berhasil dimuat.', ['item' => $klaim]);
} catch (Throwable $e) {
    error_log('aset_kelas_detail error: ' . $e->getMessage());
    json_error('Gagal memuat detail aset.', [], 500);
}
