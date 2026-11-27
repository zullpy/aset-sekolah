<?php
/**
 * API Endpoint - Riwayat Lengkap Tahapan Pemeliharaan Per Aset
 * Lokasi: api/pemeliharaan-riwayat.php
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/conn.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/ajax.php';
require_once dirname(__DIR__) . '/includes/pemeliharaan-helper.php';
require_once dirname(__DIR__) . '/includes/aset-kelas-helper.php';

require_auth(['administrator', 'operator']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Metode request tidak diizinkan.', [], 405);
}

release_session_lock();

try {
    $id     = (int) ($_GET['id'] ?? 0);
    $kibId  = strtolower(trim((string) ($_GET['kib'] ?? '')));
    $asetId = (int) ($_GET['aset_id'] ?? 0);

    $assetMeta = null;

    if ($id > 0) {
        $stmtRef = $pdo->prepare("SELECT kib, aset_id, kode_aset, nama_aset FROM pemeliharaan WHERE id = ? LIMIT 1");
        $stmtRef->execute([$id]);
        $ref = $stmtRef->fetch(PDO::FETCH_ASSOC);
        if (!$ref) {
            json_error('Data pemeliharaan tidak ditemukan.', [], 404);
        }
        $kibId  = (string) $ref['kib'];
        $asetId = (int) $ref['aset_id'];
        $assetMeta = [
            'kib'       => $kibId,
            'kib_label' => PEMELIHARAAN_KIB_TABLES[$kibId]['label'] ?? strtoupper($kibId),
            'aset_id'   => $asetId,
            'kode_aset' => (string) $ref['kode_aset'],
            'nama_aset' => (string) $ref['nama_aset'],
        ];
    }

    if (!isset(PEMELIHARAAN_KIB_TABLES[$kibId]) || $asetId <= 0) {
        json_error('Parameter aset tidak valid.', [], 422);
    }

    ensure_klaim_aset_table($pdo);
    $currentUser = current_user();
    $isOperatorRole = ($currentUser && ($currentUser['role'] ?? '') === 'operator');
    $operatorKelasId = $isOperatorRole ? detect_operator_kelas_id($currentUser) : null;
    $operatorLokasi = ($operatorKelasId !== null) ? pml_penempatan_to_lokasi('kelas-' . $operatorKelasId) : null;

    if ($operatorKelasId !== null) {
        $stmtCheck = $pdo->prepare("
            SELECT id FROM klaim_aset WHERE kelas_id = ? AND kib = ? AND aset_id = ?
            LIMIT 1
        ");
        $stmtCheck->execute([$operatorKelasId, $kibId, $asetId]);
        if (!$stmtCheck->fetch()) {
            json_error('Data pemeliharaan tidak ditemukan untuk kelas Anda.', [], 404);
        }

        $stmtHist = $pdo->prepare("
            SELECT *
            FROM pemeliharaan
            WHERE kib = ? AND aset_id = ?
              AND lokasi IN (?, ?)
            ORDER BY tanggal_update DESC, id DESC
        ");
        $stmtHist->execute([$kibId, $asetId, $operatorLokasi, 'Kelas ' . $operatorKelasId]);
    } else {
        $stmtHist = $pdo->prepare("
            SELECT *
            FROM pemeliharaan
            WHERE kib = ? AND aset_id = ?
            ORDER BY tanggal_update DESC, id DESC
        ");
        $stmtHist->execute([$kibId, $asetId]);
    }
    $rows = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

    if ($assetMeta === null) {
        $first = $rows[0] ?? null;
        $assetMeta = [
            'kib'       => $kibId,
            'kib_label' => PEMELIHARAAN_KIB_TABLES[$kibId]['label'] ?? strtoupper($kibId),
            'aset_id'   => $asetId,
            'kode_aset' => $first ? (string) $first['kode_aset'] : '',
            'nama_aset' => $first ? (string) $first['nama_aset'] : '',
        ];
    }

    $riwayat = array_map(static function (array $row): array {
        return [
            'id'                 => (int) $row['id'],
            'parent_id'          => $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
            'lokasi'             => (string) $row['lokasi'],
            'kondisi_awal'       => (string) $row['kondisi_awal'],
            'kondisi_akhir'      => $row['kondisi_akhir'] !== null ? (string) $row['kondisi_akhir'] : '',
            'jumlah'             => (int) $row['jumlah'],
            'jumlah_aktif'       => (int) $row['jumlah_aktif'],
            'status'             => (string) $row['status'],
            'tanggal_update'     => (string) $row['tanggal_update'],
            'tanggal_update_fmt' => pml_format_date_id((string) $row['tanggal_update']),
            'dokumentasi'        => pml_parse_dokumentasi($row['dokumentasi'] ?? ''),
            'created_by'         => (string) ($row['created_by'] ?? ''),
            'created_at'         => (string) ($row['created_at'] ?? ''),
        ];
    }, $rows);

    json_success('Riwayat pemeliharaan aset berhasil dimuat.', [
        'asset'   => $assetMeta,
        'riwayat' => $riwayat,
    ]);
} catch (Throwable $e) {
    error_log('API Pemeliharaan Riwayat Error: ' . $e->getMessage());
    json_error('Gagal memuat riwayat pemeliharaan aset.', [], 500);
}
