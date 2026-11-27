<?php
/**
 * Endpoint GET - Daftar Data Aset Kelas (Search, Filter, Pagination & Opsi Dropdown)
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 * Lokasi: actions/aset_kelas_list.php
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
    json_error('Anda tidak memiliki izin untuk mengakses data kelas ini.', [], 403);
}

release_session_lock();

try {
    ensure_klaim_aset_table($pdo);

    // Whitelist filter KIB
    $rawKib = strtolower(trim((string) ($_GET['kib'] ?? 'all')));
    $allowedKibFilters = ['all', 'kib-a', 'kib-b', 'kib-c', 'kib-d', 'kib-e', 'kib-f'];
    $kibFilter = in_array($rawKib, $allowedKibFilters, true) ? $rawKib : 'all';

    // Filter teks & dropdown
    $q      = trim((string) ($_GET['q'] ?? ''));
    $jenis  = trim((string) ($_GET['jenis'] ?? ''));
    $tahun  = trim((string) ($_GET['tahun'] ?? ''));
    $sumber = trim((string) ($_GET['sumber'] ?? ''));

    // Whitelist range pagination
    $rawRange = strtolower(trim((string) ($_GET['range'] ?? '10')));
    $allowedRanges = ['5', '10', '25', '50', 'all'];
    $range = in_array($rawRange, $allowedRanges, true) ? $rawRange : '10';

    $page = max(1, (int) ($_GET['page'] ?? 1));

    // Whitelist sorting
    $allowedSortCols = [
        'id'         => 'id',
        'kode_aset'  => 'kode_aset',
        'jenis_aset' => 'jenis_aset',
        'nama_aset'  => 'nama_aset',
        'no_reg'     => 'no_reg',
        'jumlah'     => 'jumlah',
    ];
    $sortKey = trim((string) ($_GET['sort'] ?? 'id'));
    $sortCol = $allowedSortCols[$sortKey] ?? 'id';
    $sortDir = strtoupper(trim((string) ($_GET['dir'] ?? 'DESC'))) === 'ASC' ? 'ASC' : 'DESC';

    // Bangun klausa WHERE secara dinamis dengan prepared statement
    $where  = ['kelas_id = :kelas_id'];
    $params = [':kelas_id' => $kelasId];

    if ($q !== '') {
        $where[] = '(kode_aset LIKE :q1 OR nama_aset LIKE :q2 OR no_reg LIKE :q3)';
        $likeQ = '%' . $q . '%';
        $params[':q1'] = $likeQ;
        $params[':q2'] = $likeQ;
        $params[':q3'] = $likeQ;
    }

    if ($kibFilter !== 'all') {
        $where[] = 'kib = :kib';
        $params[':kib'] = $kibFilter;
    }

    if ($jenis !== '' && strtolower($jenis) !== 'all') {
        $where[] = 'jenis_aset = :jenis';
        $params[':jenis'] = $jenis;
    }

    if ($tahun !== '' && strtolower($tahun) !== 'all') {
        $where[] = 'tahun = :tahun';
        $params[':tahun'] = $tahun;
    }

    if ($sumber !== '' && strtolower($sumber) !== 'all') {
        $where[] = 'sumber = :sumber';
        $params[':sumber'] = $sumber;
    }

    $whereSql = implode(' AND ', $where);

    // Hitung total baris & total jumlah aset (SUM kolom jumlah sesuai filter aktif)
    $stmtSummary = $pdo->prepare("
        SELECT COUNT(*) AS total_rows, COALESCE(SUM(jumlah), 0) AS total_aset
        FROM klaim_aset
        WHERE {$whereSql}
    ");
    $stmtSummary->execute($params);
    $summary = $stmtSummary->fetch(PDO::FETCH_ASSOC);

    $totalRows = (int) ($summary['total_rows'] ?? 0);
    $totalAset = (int) ($summary['total_aset'] ?? 0);

    if ($range === 'all') {
        $perPage = max(1, $totalRows);
        $totalPages = 1;
        $page = 1;
        $offset = 0;
    } else {
        $perPage = (int) $range;
        $totalPages = max(1, (int) ceil($totalRows / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;
    }

    // Ambil data tabel sesuai halaman
    $sqlData = "
        SELECT id, kelas_id, kib, aset_id, kode_aset, jenis_aset, jenis_kib, nama_aset, no_reg, spesifikasi, tahun, sumber, harga, jumlah, created_at
        FROM klaim_aset
        WHERE {$whereSql}
        ORDER BY {$sortCol} {$sortDir}, id DESC
    ";
    if ($range !== 'all') {
        $sqlData .= " LIMIT :limit OFFSET :offset";
    }

    $stmtData = $pdo->prepare($sqlData);
    foreach ($params as $key => $val) {
        $stmtData->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    if ($range !== 'all') {
        $stmtData->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmtData->bindValue(':offset', $offset, PDO::PARAM_INT);
    }
    $stmtData->execute();
    $rows = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
        $r['id']       = (int) $r['id'];
        $r['kelas_id'] = (int) $r['kelas_id'];
        $r['aset_id']  = (int) $r['aset_id'];
        $r['jumlah']   = (int) $r['jumlah'];
        $r['no_reg']   = format_no_reg_5digit((string) $r['no_reg']);
    }
    unset($r);

    // Kumpulkan opsi filter dari database (tabel klaim_aset + master KIB)
    $jenisSet  = [];
    $tahunSet  = [];
    $sumberSet = [];

    foreach (ASET_KELAS_KIB_META as $kibKey => $meta) {
        $table = $meta['table'];
        $cols = $pdo->query("SHOW COLUMNS FROM {$table}")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('jenis', $cols, true)) {
            $qJenis = $pdo->query("SELECT DISTINCT TRIM(jenis) FROM {$table} WHERE jenis IS NOT NULL AND TRIM(jenis) <> ''");
            foreach ($qJenis->fetchAll(PDO::FETCH_COLUMN) as $v) {
                $jenisSet[$v] = true;
            }
        } else {
            $jenisSet[$meta['default_jenis']] = true;
        }
        if (in_array('tahun', $cols, true)) {
            $qTahun = $pdo->query("SELECT DISTINCT TRIM(tahun) FROM {$table} WHERE tahun IS NOT NULL AND TRIM(tahun) <> ''");
            foreach ($qTahun->fetchAll(PDO::FETCH_COLUMN) as $v) {
                if (preg_match('/(\d{4})/', $v, $m)) {
                    $tahunSet[$m[1]] = true;
                }
            }
        }
        if (in_array('sumber', $cols, true)) {
            $qSumber = $pdo->query("SELECT DISTINCT TRIM(sumber) FROM {$table} WHERE sumber IS NOT NULL AND TRIM(sumber) <> ''");
            foreach ($qSumber->fetchAll(PDO::FETCH_COLUMN) as $v) {
                $sumberSet[$v] = true;
            }
        }
    }

    $jenisList  = array_keys($jenisSet);
    $tahunList  = array_keys($tahunSet);
    $sumberList = array_keys($sumberSet);
    sort($jenisList, SORT_NATURAL | SORT_FLAG_CASE);
    rsort($tahunList, SORT_NUMERIC);
    sort($sumberList, SORT_NATURAL | SORT_FLAG_CASE);

    $payload = [
        'kelas_id'       => $kelasId,
        'rows'           => $rows,
        'total_rows'     => $totalRows,
        'total_aset'     => $totalAset,
        'page'           => $page,
        'range'          => $range,
        'per_page'       => $range === 'all' ? $totalRows : $perPage,
        'total_pages'    => $totalPages,
        'offset'         => $offset,
        'next_no_reg'    => get_next_klaim_no_reg($pdo, $kelasId),
        'filter_options' => [
            'jenis'  => $jenisList,
            'tahun'  => $tahunList,
            'sumber' => $sumberList,
        ],
    ];

    if (!empty($_GET['include_master'])) {
        $payload['master_assets'] = fetch_all_master_aset_for_klaim($pdo, $kelasId);
    }

    json_success('Data aset kelas berhasil dimuat.', $payload);
} catch (Throwable $e) {
    error_log('aset_kelas_list error: ' . $e->getMessage());
    json_error('Gagal memuat data aset kelas.', [], 500);
}
