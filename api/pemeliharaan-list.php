<?php
/**
 * API Endpoint - Daftar, Filter, Pagination & Statistik Timeline Pemeliharaan Aset
 * Lokasi: api/pemeliharaan-list.php
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/conn.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/ajax.php';
require_once dirname(__DIR__) . '/includes/pemeliharaan-helper.php';

require_auth(['administrator', 'operator']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Metode request tidak diizinkan.', [], 405);
}

try {
    $q            = trim((string) ($_GET['q'] ?? ''));
    $namaAset     = trim((string) ($_GET['nama_aset'] ?? ''));
    $lokasi       = trim((string) ($_GET['lokasi'] ?? ''));
    $kondisiAwal  = trim((string) ($_GET['kondisi_awal'] ?? ''));
    $kondisiAkhir = trim((string) ($_GET['kondisi_akhir'] ?? ''));
    $status       = trim((string) ($_GET['status'] ?? ''));
    $tglDariRaw   = trim((string) ($_GET['tgl_dari'] ?? ''));
    $tglSampaiRaw = trim((string) ($_GET['tgl_sampai'] ?? ''));
    $tglSingleRaw = trim((string) ($_GET['tgl_update'] ?? ''));

    if ($tglSingleRaw !== '' && $tglDariRaw === '' && $tglSampaiRaw === '') {
        $tglDariRaw = $tglSingleRaw;
        $tglSampaiRaw = $tglSingleRaw;
    }

    $tglDari = ($tglDariRaw !== '') ? pml_parse_date($tglDariRaw, false) : null;
    if ($tglDariRaw !== '' && $tglDari === null) {
        json_error('Format Tanggal Dari tidak valid.', ['field' => 'tgl_dari'], 422);
    }

    $tglSampai = ($tglSampaiRaw !== '') ? pml_parse_date($tglSampaiRaw, false) : null;
    if ($tglSampaiRaw !== '' && $tglSampai === null) {
        json_error('Format Tanggal Sampai tidak valid.', ['field' => 'tgl_sampai'], 422);
    }

    if ($kondisiAwal !== '' && !in_array($kondisiAwal, PEMELIHARAAN_CONDITIONS, true)) {
        json_error('Filter Kondisi Awal tidak valid.', ['field' => 'kondisi_awal'], 422);
    }
    if ($kondisiAkhir !== '' && !in_array($kondisiAkhir, PEMELIHARAAN_CONDITIONS, true)) {
        json_error('Filter Kondisi Akhir tidak valid.', ['field' => 'kondisi_akhir'], 422);
    }
    if ($status !== '' && !in_array($status, PEMELIHARAAN_STAGES, true)) {
        json_error('Filter Status Pemeliharaan tidak valid.', ['field' => 'status'], 422);
    }

    $rangeRaw = strtolower(trim((string) ($_GET['range'] ?? '10')));
    $allowedRanges = ['5', '10', '25', '50', 'all'];
    if (!in_array($rangeRaw, $allowedRanges, true)) {
        $rangeRaw = '10';
    }
    $page = max(1, (int) ($_GET['page'] ?? 1));

    $where = ['jumlah_aktif > 0'];
    $params = [];

    if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = '(kode_aset LIKE ? OR nama_aset LIKE ?)';
        $params[] = $like;
        $params[] = $like;
    }
    if ($namaAset !== '') {
        $where[] = 'nama_aset = ?';
        $params[] = $namaAset;
    }
    if ($lokasi !== '') {
        $where[] = 'lokasi = ?';
        $params[] = $lokasi;
    }
    if ($kondisiAwal !== '') {
        $where[] = 'kondisi_awal = ?';
        $params[] = $kondisiAwal;
    }
    if ($kondisiAkhir !== '') {
        $where[] = 'kondisi_akhir = ?';
        $params[] = $kondisiAkhir;
    }
    if ($status !== '') {
        $where[] = 'status = ?';
        $params[] = $status;
    }
    if ($tglDari !== null) {
        $where[] = 'tanggal_update >= ?';
        $params[] = $tglDari;
    }
    if ($tglSampai !== null) {
        $where[] = 'tanggal_update <= ?';
        $params[] = $tglSampai;
    }

    $whereSql = implode(' AND ', $where);

    // 1. Statistik timeline berdasarkan SUM jumlah_aktif per status terkini (mengikuti filter aktif)
    $timeline = [
        'perlu_pemeliharaan'      => 0,
        'sedang_ditindak_lanjut'  => 0,
        'selesai_ditindak_lanjut' => 0,
        'digunakan_kembali'       => 0,
        'disimpan'                => 0,
    ];

    $stmtTimeline = $pdo->prepare("
        SELECT status, COALESCE(SUM(jumlah_aktif), 0) AS total_unit
        FROM pemeliharaan
        WHERE {$whereSql}
        GROUP BY status
    ");
    $stmtTimeline->execute($params);
    while ($tRow = $stmtTimeline->fetch(PDO::FETCH_ASSOC)) {
        $qty = (int) $tRow['total_unit'];
        switch ($tRow['status']) {
            case 'Perlu pemeliharaan':
                $timeline['perlu_pemeliharaan'] = $qty;
                break;
            case 'Sedang ditindak lanjut':
                $timeline['sedang_ditindak_lanjut'] = $qty;
                break;
            case 'Selesai ditindak lanjut':
                $timeline['selesai_ditindak_lanjut'] = $qty;
                break;
            case 'Digunakan kembali':
                $timeline['digunakan_kembali'] = $qty;
                break;
            case 'Disimpan':
                $timeline['disimpan'] = $qty;
                break;
        }
    }

    // 2. Hitung total baris untuk pagination server-side
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM pemeliharaan WHERE {$whereSql}");
    $stmtCount->execute($params);
    $totalRows = (int) $stmtCount->fetchColumn();

    if ($rangeRaw === 'all') {
        $totalPages = 1;
        $page = 1;
        $stmtList = $pdo->prepare("
            SELECT *
            FROM pemeliharaan
            WHERE {$whereSql}
            ORDER BY tanggal_update DESC, id DESC
        ");
        $stmtList->execute($params);
    } else {
        $limit = (int) $rangeRaw;
        $totalPages = max(1, (int) ceil($totalRows / $limit));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $limit;

        $stmtList = $pdo->prepare("
            SELECT *
            FROM pemeliharaan
            WHERE {$whereSql}
            ORDER BY tanggal_update DESC, id DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmtList->execute($params);
    }

    $rows = $stmtList->fetchAll(PDO::FETCH_ASSOC);

    $items = array_map(static function (array $row): array {
        $kibId = (string) ($row['kib'] ?? 'kib-b');
        $statusVal = (string) $row['status'];
        $nextStatuses = PEMELIHARAAN_TRANSITIONS[$statusVal] ?? [];
        $kondisiAkhir = $row['kondisi_akhir'] !== null ? (string) $row['kondisi_akhir'] : '';
        $kondisiDisplay = ($kondisiAkhir !== '') ? $kondisiAkhir : (string) $row['kondisi_awal'];

        return [
            'id'                 => (int) $row['id'],
            'kib'                => $kibId,
            'kib_label'          => PEMELIHARAAN_KIB_TABLES[$kibId]['label'] ?? strtoupper($kibId),
            'aset_id'            => (int) $row['aset_id'],
            'monitoring_id'      => $row['monitoring_id'] !== null ? (int) $row['monitoring_id'] : null,
            'kode_aset'          => (string) $row['kode_aset'],
            'nama_aset'          => (string) $row['nama_aset'],
            'lokasi'             => (string) $row['lokasi'],
            'kondisi_awal'       => (string) $row['kondisi_awal'],
            'kondisi_akhir'      => $kondisiAkhir,
            'kondisi_display'    => $kondisiDisplay,
            'jumlah'             => (int) $row['jumlah_aktif'],
            'jumlah_riwayat'     => (int) $row['jumlah'],
            'status'             => $statusVal,
            'next_statuses'      => $nextStatuses,
            'can_process'        => !empty($nextStatuses) && ((int) $row['jumlah_aktif'] > 0),
            'tanggal_update'     => (string) $row['tanggal_update'],
            'tanggal_update_fmt' => pml_format_date_id((string) $row['tanggal_update']),
            'dokumentasi'        => pml_parse_dokumentasi($row['dokumentasi'] ?? ''),
            'created_by'         => (string) ($row['created_by'] ?? ''),
        ];
    }, $rows);

    json_success('Data pemeliharaan berhasil dimuat.', [
        'timeline'   => $timeline,
        'items'      => $items,
        'pagination' => [
            'page'          => $page,
            'range'         => $rangeRaw,
            'total_rows'    => $totalRows,
            'total_pages'   => $totalPages,
            'showing_count' => count($items),
        ],
    ]);
} catch (Throwable $e) {
    error_log('API Pemeliharaan List Error: ' . $e->getMessage());
    json_error('Gagal memuat data pemeliharaan.', [], 500);
}
