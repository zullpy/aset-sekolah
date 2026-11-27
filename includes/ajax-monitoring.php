<?php
/**
 * Backend Endpoint AJAX - Monitoring Aset Sekolah (Kelas 1-6 & Aset Umum)
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 * Lokasi: includes/ajax-monitoring.php
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/conn.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ajax.php';
require_once __DIR__ . '/pemeliharaan-helper.php';
require_once __DIR__ . '/aset-kelas-helper.php';

require_auth(['administrator', 'operator']);

function assert_monitoring_penempatan_access(?array $user, string $penempatan): void
{
    if (!$user) {
        json_error('Sesi tidak valid.', [], 401);
    }
    $role = strtolower(trim((string) ($user['role'] ?? '')));
    if ($role === 'administrator') {
        return;
    }
    $assignedKelas = detect_operator_kelas_id($user);
    if ($assignedKelas !== null) {
        $expected = 'kelas-' . $assignedKelas;
        if ($penempatan !== $expected) {
            json_error('Anda hanya berwenang mengakses monitoring ' . MONITORING_LOCATIONS[$expected] . '.', [], 403);
        }
    }
}

header('Content-Type: application/json; charset=utf-8');

const MONITORING_LOCATIONS = [
    'kelas-1' => 'Kelas 1',
    'kelas-2' => 'Kelas 2',
    'kelas-3' => 'Kelas 3',
    'kelas-4' => 'Kelas 4',
    'kelas-5' => 'Kelas 5',
    'kelas-6' => 'Kelas 6',
    'umum'    => 'Aset Umum',
];

const MONITORING_CONDITIONS = ['Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang'];

const MONITORING_KIB_TABLES = [
    'kib-a' => ['table' => 'kib_a', 'label' => 'KIB A', 'default_jenis' => 'Tanah', 'has_jenis' => false, 'has_jumlah' => false],
    'kib-b' => ['table' => 'kib_b', 'label' => 'KIB B', 'default_jenis' => 'Peralatan & Mesin', 'has_jenis' => true, 'has_jumlah' => true],
    'kib-c' => ['table' => 'kib_c', 'label' => 'KIB C', 'default_jenis' => 'Gedung & Bangunan', 'has_jenis' => false, 'has_jumlah' => false],
    'kib-d' => ['table' => 'kib_d', 'label' => 'KIB D', 'default_jenis' => 'Jalan, Irigasi & Jaringan', 'has_jenis' => false, 'has_jumlah' => false],
    'kib-e' => ['table' => 'kib_e', 'label' => 'KIB E', 'default_jenis' => 'Buku & Aset Tetap Lainnya', 'has_jenis' => true, 'has_jumlah' => true],
    'kib-f' => ['table' => 'kib_f', 'label' => 'KIB F', 'default_jenis' => 'Konstruksi Dalam Pengerjaan', 'has_jenis' => true, 'has_jumlah' => false],
];

function normalize_penempatan(string $raw, bool $allowSemua = false): ?string
{
    $val = strtolower(trim($raw));
    if ($val === 'aset-umum') {
        $val = 'umum';
    }
    if ($allowSemua && ($val === 'semua' || $val === 'all')) {
        return 'semua';
    }
    return isset(MONITORING_LOCATIONS[$val]) ? $val : null;
}

function allowed_kibs_for_penempatan(string $penempatan): array
{
    if (str_starts_with($penempatan, 'kelas-')) {
        return ['kib-b', 'kib-e'];
    }
    return array_keys(MONITORING_KIB_TABLES);
}

function parse_monitoring_date(string $raw): ?string
{
    $val = trim($raw);
    if ($val === '') {
        return date('Y-m-d');
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $val, $m)) {
        if (checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return $val;
        }
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2})$/', $val, $m)) {
        $d = (int) $m[1];
        $mo = (int) $m[2];
        $yy = (int) $m[3];
        $currYY = (int) date('y');
        $yyyy = ($yy <= $currYY + 10) ? (2000 + $yy) : (1900 + $yy);
        if (checkdate($mo, $d, $yyyy)) {
            return sprintf('%04d-%02d-%02d', $yyyy, $mo, $d);
        }
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $val, $m)) {
        $d = (int) $m[1];
        $mo = (int) $m[2];
        $yyyy = (int) $m[3];
        if (checkdate($mo, $d, $yyyy)) {
            return sprintf('%04d-%02d-%02d', $yyyy, $mo, $d);
        }
    }
    return null;
}

function parse_aset_jumlah_int($raw): int
{
    $digits = preg_replace('/[^\d]/', '', (string) $raw);
    $val = ($digits === '') ? 1 : (int) $digits;
    return max(1, $val);
}

function fetch_all_monitoring_assets(PDO $pdo, string $penempatan = 'umum'): array
{
    // Untuk lokasi per kelas (kelas-1 s/d kelas-6), sumber aset berasal dari klaim_aset kelas tersebut
    if (preg_match('/^kelas-([1-6])$/', $penempatan, $m)) {
        ensure_klaim_aset_table($pdo);
        $kelasId = (int) $m[1];

        $usedMap = [];
        $stmtUsed = $pdo->prepare("SELECT kib, aset_id, COALESCE(SUM(jumlah), 0) AS used_qty FROM monitoring_aset WHERE penempatan = ? GROUP BY kib, aset_id");
        $stmtUsed->execute([$penempatan]);
        while ($u = $stmtUsed->fetch(PDO::FETCH_ASSOC)) {
            $usedMap[$u['kib'] . ':' . (int) $u['aset_id']] = (int) $u['used_qty'];
        }

        $stmt = $pdo->prepare("
            SELECT kib, aset_id, MAX(kode_aset) AS kode, MAX(nama_aset) AS nama, MAX(jenis_aset) AS jenis, COALESCE(SUM(jumlah), 0) AS total_klaim
            FROM klaim_aset
            WHERE kelas_id = ?
            GROUP BY kib, aset_id
            ORDER BY nama ASC, aset_id ASC
        ");
        $stmt->execute([$kelasId]);
        $options = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $kibId = (string) $row['kib'];
            $asetId = (int) $row['aset_id'];
            $totalQty = max(1, (int) $row['total_klaim']);
            $usedQty = $usedMap[$kibId . ':' . $asetId] ?? 0;
            $sisaKuota = max(0, $totalQty - $usedQty);

            $options[] = [
                'kib'               => $kibId,
                'kib_label'         => MONITORING_KIB_TABLES[$kibId]['label'] ?? strtoupper($kibId),
                'aset_id'           => $asetId,
                'kode'              => (string) $row['kode'],
                'nama'              => (string) $row['nama'],
                'jenis'             => (string) $row['jenis'],
                'jumlah'            => $totalQty,
                'jumlah_total'      => $totalQty,
                'jumlah_termonitor' => $usedQty,
                'sisa_kuota'        => $sisaKuota,
            ];
        }
        return $options;
    }

    $allowedKibs = allowed_kibs_for_penempatan($penempatan);

    $usedMap = [];
    $stmtUsed = $pdo->query("SELECT kib, aset_id, COALESCE(SUM(jumlah), 0) AS used_qty FROM monitoring_aset GROUP BY kib, aset_id");
    while ($u = $stmtUsed->fetch(PDO::FETCH_ASSOC)) {
        $usedMap[$u['kib'] . ':' . (int) $u['aset_id']] = (int) $u['used_qty'];
    }

    $options = [];
    foreach ($allowedKibs as $kibId) {
        if (!isset(MONITORING_KIB_TABLES[$kibId])) {
            continue;
        }
        $meta = MONITORING_KIB_TABLES[$kibId];
        $table = $meta['table'];
        $jenisCol = $meta['has_jenis'] ? 'jenis' : 'NULL AS jenis';
        $jumlahCol = $meta['has_jumlah'] ? 'jumlah' : 'NULL AS jumlah';

        $stmt = $pdo->query("
            SELECT id, kode, nama, {$jenisCol}, {$jumlahCol}
            FROM {$table}
            ORDER BY nama ASC, id ASC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $jenisVal = trim((string) ($row['jenis'] ?? ''));
            if ($jenisVal === '') {
                $jenisVal = $meta['default_jenis'];
            }
            $asetId = (int) $row['id'];
            $totalQty = $meta['has_jumlah'] ? parse_aset_jumlah_int($row['jumlah'] ?? 1) : 1;
            $usedQty = $usedMap[$kibId . ':' . $asetId] ?? 0;
            $sisaKuota = max(0, $totalQty - $usedQty);

            $options[] = [
                'kib'               => $kibId,
                'kib_label'         => $meta['label'],
                'aset_id'           => $asetId,
                'kode'              => (string) $row['kode'],
                'nama'              => (string) $row['nama'],
                'jenis'             => $jenisVal,
                'jumlah'            => $totalQty,
                'jumlah_total'      => $totalQty,
                'jumlah_termonitor' => $usedQty,
                'sisa_kuota'        => $sisaKuota,
            ];
        }
    }
    return $options;
}

function get_registered_asset_stats(PDO $pdo, string $penempatan = 'semua'): array
{
    if (preg_match('/^kelas-([1-6])$/', $penempatan, $m)) {
        ensure_klaim_aset_table($pdo);
        $kelasId = (int) $m[1];
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT CONCAT(kib, ':', aset_id)) AS aset_count, COALESCE(SUM(jumlah), 0) AS unit_total FROM klaim_aset WHERE kelas_id = ?");
        $stmt->execute([$kelasId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'aset_count' => (int) ($row['aset_count'] ?? 0),
            'unit_total' => (int) ($row['unit_total'] ?? 0),
        ];
    }

    $allowedKibs = allowed_kibs_for_penempatan($penempatan);
    $asetCount = 0;
    $unitTotal = 0;

    foreach ($allowedKibs as $kibId) {
        if (!isset(MONITORING_KIB_TABLES[$kibId])) {
            continue;
        }
        $meta = MONITORING_KIB_TABLES[$kibId];
        $table = $meta['table'];
        if ($meta['has_jumlah']) {
            $rows = $pdo->query("SELECT jumlah FROM {$table}")->fetchAll(PDO::FETCH_COLUMN);
            $asetCount += count($rows);
            foreach ($rows as $j) {
                $unitTotal += parse_aset_jumlah_int($j);
            }
        } else {
            $cnt = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
            $asetCount += $cnt;
            $unitTotal += $cnt;
        }
    }

    return [
        'aset_count' => $asetCount,
        'unit_total' => $unitTotal,
    ];
}

function find_monitoring_asset(PDO $pdo, string $kibId, int $asetId): ?array
{
    if (!isset(MONITORING_KIB_TABLES[$kibId]) || $asetId <= 0) {
        return null;
    }
    $meta = MONITORING_KIB_TABLES[$kibId];
    $table = $meta['table'];
    $jenisCol = $meta['has_jenis'] ? 'jenis' : 'NULL AS jenis';
    $jumlahCol = $meta['has_jumlah'] ? 'jumlah' : 'NULL AS jumlah';

    $stmt = $pdo->prepare("
        SELECT id, kode, nama, {$jenisCol}, {$jumlahCol}
        FROM {$table}
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$asetId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    $jenisVal = trim((string) ($row['jenis'] ?? ''));
    if ($jenisVal === '') {
        $jenisVal = $meta['default_jenis'];
    }
    $totalQty = $meta['has_jumlah'] ? parse_aset_jumlah_int($row['jumlah'] ?? 1) : 1;

    return [
        'kib'          => $kibId,
        'kib_label'    => $meta['label'],
        'aset_id'      => (int) $row['id'],
        'kode'         => (string) $row['kode'],
        'nama'         => (string) $row['nama'],
        'jenis'        => $jenisVal,
        'jumlah'       => $totalQty,
        'jumlah_total' => $totalQty,
    ];
}

function build_monitoring_summary(array $items, array $kibStats): array
{
    $summary = [
        'total_aset_terdaftar' => (int) ($kibStats['aset_count'] ?? 0),
        'total_unit_sekolah'   => (int) ($kibStats['unit_total'] ?? 0),
        'total_entri'          => count($items),
        'total_unit'           => 0,
        'baik'                 => 0,
        'rusak_ringan'         => 0,
        'rusak_berat'          => 0,
        'hilang'               => 0,
    ];

    foreach ($items as $row) {
        $qty = max(1, (int) ($row['jumlah'] ?? 1));
        $summary['total_unit'] += $qty;
        switch ($row['kondisi'] ?? '') {
            case 'Baik':
                $summary['baik'] += $qty;
                break;
            case 'Rusak Ringan':
                $summary['rusak_ringan'] += $qty;
                break;
            case 'Rusak Berat':
                $summary['rusak_berat'] += $qty;
                break;
            case 'Hilang':
                $summary['hilang'] += $qty;
                break;
        }
    }

    return $summary;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            $penempatan = normalize_penempatan((string) ($_GET['penempatan'] ?? $_POST['penempatan'] ?? ''), true);
            if ($penempatan === null) {
                json_error('Parameter penempatan tidak valid.', [], 422);
            }
            assert_monitoring_penempatan_access(current_user(), $penempatan);
            session_write_close();

            if ($penempatan === 'semua') {
                $stmt = $pdo->query("SELECT * FROM monitoring_aset ORDER BY tgl_update DESC, id DESC");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt = $pdo->prepare("SELECT * FROM monitoring_aset WHERE penempatan = ? ORDER BY tgl_update DESC, id DESC");
                $stmt->execute([$penempatan]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $items = array_map(static function (array $row): array {
                $kibId = (string) $row['kib'];
                $pen = (string) $row['penempatan'];
                return [
                    'id'               => (int) $row['id'],
                    'penempatan'       => $pen,
                    'penempatan_label' => MONITORING_LOCATIONS[$pen] ?? $pen,
                    'kib'              => $kibId,
                    'kib_label'        => MONITORING_KIB_TABLES[$kibId]['label'] ?? strtoupper($kibId),
                    'aset_id'          => (int) $row['aset_id'],
                    'kode_aset'        => (string) $row['kode_aset'],
                    'nama_aset'        => (string) $row['nama_aset'],
                    'jenis_aset'       => (string) $row['jenis_aset'],
                    'jumlah'             => (int) $row['jumlah'],
                    'kondisi'            => (string) $row['kondisi'],
                    'perlu_pemeliharaan' => (string) ($row['perlu_pemeliharaan'] ?? 'Tidak'),
                    'catatan'            => (string) ($row['catatan'] ?? ''),
                    'petugas'          => (string) ($row['petugas'] ?? ''),
                    'tgl_update'       => (string) $row['tgl_update'],
                ];
            }, $rows);

            $kibStats = get_registered_asset_stats($pdo, $penempatan);
            $summary = build_monitoring_summary($items, $kibStats);
            $assetOptions = ($penempatan === 'semua') ? [] : fetch_all_monitoring_assets($pdo, $penempatan);

            json_success('Data monitoring berhasil dimuat.', [
                'penempatan'       => $penempatan,
                'penempatan_label' => ($penempatan === 'semua') ? 'Semua Lokasi' : (MONITORING_LOCATIONS[$penempatan] ?? $penempatan),
                'readonly'         => ($penempatan === 'semua'),
                'items'            => $items,
                'summary'          => $summary,
                'asset_options'    => $assetOptions,
            ]);
            break;

        case 'asset_options':
            $penempatan = normalize_penempatan((string) ($_GET['penempatan'] ?? $_POST['penempatan'] ?? ''), false);
            if ($penempatan === null) {
                json_error('Parameter penempatan harus salah satu dari Kelas 1-6 atau Aset Umum.', [], 422);
            }
            assert_monitoring_penempatan_access(current_user(), $penempatan);
            session_write_close();

            $assetOptions = fetch_all_monitoring_assets($pdo, $penempatan);
            json_success('Daftar aset KIB berhasil dimuat.', [
                'penempatan'       => $penempatan,
                'penempatan_label' => MONITORING_LOCATIONS[$penempatan],
                'asset_options'    => $assetOptions,
            ]);
            break;

        case 'save':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_error('Metode request tidak diizinkan.', [], 405);
            }
            if (!verify_csrf_token()) {
                json_error('Token CSRF tidak valid. Silakan muat ulang halaman.', [], 403);
            }

            $penempatan = normalize_penempatan((string) ($_POST['penempatan'] ?? ''), false);
            if ($penempatan === null) {
                json_error('Parameter penempatan tidak valid.', ['field' => 'penempatan'], 422);
            }
            assert_monitoring_penempatan_access(current_user(), $penempatan);

            $id      = (int) ($_POST['id'] ?? 0);
            $kibId   = strtolower(trim((string) ($_POST['kib'] ?? '')));
            $asetId  = (int) ($_POST['aset_id'] ?? 0);
            $kondisi = trim((string) ($_POST['kondisi'] ?? ''));
            $perluRaw = trim((string) ($_POST['perlu_pemeliharaan'] ?? 'Tidak'));
            $perluPemeliharaan = (in_array($kondisi, ['Rusak Ringan', 'Rusak Berat'], true) && $perluRaw === 'Ya') ? 'Ya' : 'Tidak';
            $jumlah  = (int) ($_POST['jumlah'] ?? 0);
            $catatan = trim((string) ($_POST['catatan'] ?? ''));
            $petugas = trim((string) ($_POST['petugas'] ?? ''));
            $tglRaw  = (string) ($_POST['tgl_update'] ?? '');

            if (!isset(MONITORING_KIB_TABLES[$kibId]) || $asetId <= 0) {
                json_error('Silakan pilih Nama Aset yang tersedia pada Data Aset.', ['field' => 'aset_key'], 422);
            }

            $isKelasPenempatan = (bool) preg_match('/^kelas-([1-6])$/', $penempatan, $kelasMatch);
            if ($isKelasPenempatan) {
                ensure_klaim_aset_table($pdo);
                $kelasId = (int) $kelasMatch[1];
                $stmtClaimed = $pdo->prepare("
                    SELECT kib, aset_id, MAX(kode_aset) AS kode, MAX(nama_aset) AS nama, MAX(jenis_aset) AS jenis, COALESCE(SUM(jumlah), 0) AS total_klaim
                    FROM klaim_aset
                    WHERE kelas_id = ? AND kib = ? AND aset_id = ?
                    GROUP BY kib, aset_id
                    LIMIT 1
                ");
                $stmtClaimed->execute([$kelasId, $kibId, $asetId]);
                $claimedRow = $stmtClaimed->fetch(PDO::FETCH_ASSOC);
                if (!$claimedRow) {
                    $master = fetch_master_aset_by_id($pdo, $kibId, $asetId);
                    if ($master) {
                        $cUser = current_user();
                        $insKlaim = $pdo->prepare("
                            INSERT INTO klaim_aset (
                                kelas_id, kib, aset_id, kode_aset, jenis_aset, jenis_kib,
                                nama_aset, no_reg, spesifikasi, tahun, sumber, harga, jumlah, user_id
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $insKlaim->execute([
                            $kelasId,
                            $kibId,
                            $asetId,
                            $master['kode_aset'],
                            $master['jenis_aset'],
                            $master['jenis_kib'],
                            $master['nama_aset'],
                            $master['no_reg'] ?? '00001',
                            $master['spesifikasi'] ?? '',
                            $master['tahun'] ?? '',
                            $master['sumber'] ?? '',
                            $master['harga'] ?? '',
                            $jumlah,
                            $cUser['id'] ?? null
                        ]);
                        $claimedRow = [
                            'kode'        => $master['kode_aset'],
                            'nama'        => $master['nama_aset'],
                            'jenis'       => $master['jenis_aset'],
                            'total_klaim' => $jumlah
                        ];
                    } else {
                        json_error('Aset ini belum diklaim pada Data Aset Kelas ' . $kelasId . '.', ['field' => 'aset_key'], 422);
                    }
                }
                $asset = [
                    'kib'          => $kibId,
                    'kib_label'    => MONITORING_KIB_TABLES[$kibId]['label'] ?? strtoupper($kibId),
                    'aset_id'      => $asetId,
                    'kode'         => (string) $claimedRow['kode'],
                    'nama'         => (string) $claimedRow['nama'],
                    'jenis'        => (string) $claimedRow['jenis'],
                    'jumlah'       => (int) $claimedRow['total_klaim'],
                    'jumlah_total' => (int) $claimedRow['total_klaim'],
                ];
            } else {
                $allowedKibs = allowed_kibs_for_penempatan($penempatan);
                if (!in_array($kibId, $allowedKibs, true)) {
                    json_error('Jenis KIB aset tidak diizinkan untuk lokasi ini.', ['field' => 'aset_key'], 422);
                }
                $asset = find_monitoring_asset($pdo, $kibId, $asetId);
                if ($asset === null) {
                    json_error('Aset tidak ditemukan pada Data Aset.', ['field' => 'aset_key'], 422);
                }
            }

            if (!in_array($kondisi, MONITORING_CONDITIONS, true)) {
                json_error('Kondisi aset tidak valid.', ['field' => 'kondisi'], 422);
            }

            if ($jumlah < 1) {
                json_error('Jumlah minimal 1.', ['field' => 'jumlah'], 422);
            }

            if ($isKelasPenempatan) {
                $stmtUsed = $pdo->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM monitoring_aset WHERE penempatan = ? AND kib = ? AND aset_id = ? AND id <> ?");
                $stmtUsed->execute([$penempatan, $asset['kib'], $asset['aset_id'], $id]);
            } else {
                $stmtUsed = $pdo->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM monitoring_aset WHERE kib = ? AND aset_id = ? AND id <> ?");
                $stmtUsed->execute([$asset['kib'], $asset['aset_id'], $id]);
            }
            $usedOther = (int) $stmtUsed->fetchColumn();
            $maxAllowed = max(0, (int) $asset['jumlah_total'] - $usedOther);
            if ($jumlah > $maxAllowed) {
                if ($usedOther > 0) {
                    $quotaErr = "Jumlah melebihi sisa kuota aset (total diklaim/terdaftar {$asset['jumlah_total']} unit, sudah termonitor {$usedOther} unit, sisa {$maxAllowed} unit).";
                } else {
                    $quotaErr = "Jumlah melebihi total aset yang tersedia (maksimal {$maxAllowed} unit).";
                }
                json_error($quotaErr, ['field' => 'jumlah'], 422);
            }

            $tglUpdate = parse_monitoring_date($tglRaw);
            if ($tglUpdate === null) {
                json_error('Format Tanggal Update tidak valid.', ['field' => 'tgl_update'], 422);
            }

            $currentUser = current_user();
            $defaultActor = trim((string) ($currentUser['nama_lengkap'] ?? ''));
            if (!empty($currentUser['id'])) {
                $stmtActor = $pdo->prepare("SELECT nama_lengkap, username FROM users WHERE id = ? LIMIT 1");
                $stmtActor->execute([(int) $currentUser['id']]);
                if ($actorRow = $stmtActor->fetch(PDO::FETCH_ASSOC)) {
                    $dbNama = trim((string) ($actorRow['nama_lengkap'] ?? ''));
                    $dbUser = trim((string) ($actorRow['username'] ?? ''));
                    if ($dbNama !== '') {
                        $defaultActor = $dbNama;
                    } elseif ($dbUser !== '' && $defaultActor === '') {
                        $defaultActor = $dbUser;
                    }
                }
            }
            if ($defaultActor === '') {
                $defaultActor = trim((string) ($currentUser['username'] ?? '')) ?: 'Administrator';
            }
            if ($id === 0 || $petugas === '') {
                $petugas = $defaultActor;
            }

            $pdo->beginTransaction();

            if ($id > 0) {
                $stmtCheck = $pdo->prepare("SELECT id FROM monitoring_aset WHERE id = ? AND penempatan = ? LIMIT 1");
                $stmtCheck->execute([$id, $penempatan]);
                if (!$stmtCheck->fetch()) {
                    $pdo->rollBack();
                    json_error('Data monitoring tidak ditemukan pada lokasi ini.', [], 404);
                }

                $stmtUpd = $pdo->prepare("
                    UPDATE monitoring_aset
                    SET kib = ?, aset_id = ?, kode_aset = ?, nama_aset = ?, jenis_aset = ?,
                        jumlah = ?, kondisi = ?, perlu_pemeliharaan = ?, catatan = ?, petugas = ?, tgl_update = ?
                    WHERE id = ? AND penempatan = ?
                ");
                $stmtUpd->execute([
                    $asset['kib'],
                    $asset['aset_id'],
                    $asset['kode'],
                    $asset['nama'],
                    $asset['jenis'],
                    $jumlah,
                    $kondisi,
                    $perluPemeliharaan,
                    $catatan,
                    $petugas,
                    $tglUpdate,
                    $id,
                    $penempatan,
                ]);

                $locLabel = MONITORING_LOCATIONS[$penempatan];
                $actTeks = "Monitoring {$asset['nama']} ({$asset['kode']}) di {$locLabel} diperbarui: {$kondisi}";
                $stmtLog = $pdo->prepare("INSERT INTO kib_activities (teks, pelaku, waktu, warna) VALUES (?, ?, 'Baru saja', '#f97316')");
                $stmtLog->execute([$actTeks, $defaultActor]);

                $savedId = $id;
                $msg = "Data monitoring aset di {$locLabel} berhasil diperbarui.";
            } else {
                $stmtIns = $pdo->prepare("
                    INSERT INTO monitoring_aset
                        (penempatan, kib, aset_id, kode_aset, nama_aset, jenis_aset, jumlah, kondisi, perlu_pemeliharaan, catatan, petugas, tgl_update)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtIns->execute([
                    $penempatan,
                    $asset['kib'],
                    $asset['aset_id'],
                    $asset['kode'],
                    $asset['nama'],
                    $asset['jenis'],
                    $jumlah,
                    $kondisi,
                    $perluPemeliharaan,
                    $catatan,
                    $petugas,
                    $tglUpdate,
                ]);
                $savedId = (int) $pdo->lastInsertId();

                $locLabel = MONITORING_LOCATIONS[$penempatan];
                $actTeks = "Monitoring baru {$asset['nama']} ({$asset['kode']}) di {$locLabel}: {$kondisi}";
                $stmtLog = $pdo->prepare("INSERT INTO kib_activities (teks, pelaku, waktu, warna) VALUES (?, ?, 'Baru saja', '#22c55e')");
                $stmtLog->execute([$actTeks, $defaultActor]);

                $msg = "Data monitoring aset di {$locLabel} berhasil ditambahkan.";
            }

            pml_sync_from_monitoring($pdo, [
                'id'                 => $savedId,
                'penempatan'         => $penempatan,
                'kib'                => $asset['kib'],
                'aset_id'            => $asset['aset_id'],
                'kode_aset'          => $asset['kode'],
                'nama_aset'          => $asset['nama'],
                'jumlah'             => $jumlah,
                'kondisi'            => $kondisi,
                'perlu_pemeliharaan' => $perluPemeliharaan,
                'tgl_update'         => $tglUpdate,
            ], $defaultActor);

            $pdo->commit();

            json_success($msg, [
                'id'   => $savedId,
                'item' => [
                    'id'                 => $savedId,
                    'penempatan'         => $penempatan,
                    'penempatan_label'   => MONITORING_LOCATIONS[$penempatan],
                    'kib'                => $asset['kib'],
                    'kib_label'          => $asset['kib_label'],
                    'aset_id'            => $asset['aset_id'],
                    'kode_aset'          => $asset['kode'],
                    'nama_aset'          => $asset['nama'],
                    'jenis_aset'         => $asset['jenis'],
                    'jumlah'             => $jumlah,
                    'kondisi'            => $kondisi,
                    'perlu_pemeliharaan' => $perluPemeliharaan,
                    'catatan'            => $catatan,
                    'petugas'            => $petugas,
                    'tgl_update'         => $tglUpdate,
                ],
            ]);
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_error('Metode request tidak diizinkan.', [], 405);
            }
            if (!verify_csrf_token()) {
                json_error('Token CSRF tidak valid. Silakan muat ulang halaman.', [], 403);
            }

            $penempatan = normalize_penempatan((string) ($_POST['penempatan'] ?? ''), false);
            $id = (int) ($_POST['id'] ?? 0);

            if ($penempatan === null || $id <= 0) {
                json_error('Parameter penempatan atau ID tidak valid.', [], 422);
            }
            assert_monitoring_penempatan_access(current_user(), $penempatan);

            $stmtFind = $pdo->prepare("SELECT nama_aset, kode_aset FROM monitoring_aset WHERE id = ? AND penempatan = ? LIMIT 1");
            $stmtFind->execute([$id, $penempatan]);
            $row = $stmtFind->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                json_error('Data monitoring tidak ditemukan pada lokasi ini.', [], 404);
            }

            $pdo->beginTransaction();
            pml_unlink_monitoring($pdo, $id);
            $stmtDel = $pdo->prepare("DELETE FROM monitoring_aset WHERE id = ? AND penempatan = ?");
            $stmtDel->execute([$id, $penempatan]);

            $actor = pml_resolve_actor_name($pdo);
            $locLabel = MONITORING_LOCATIONS[$penempatan];
            $actTeks = "Data monitoring {$row['nama_aset']} ({$row['kode_aset']}) di {$locLabel} dihapus";
            $stmtLog = $pdo->prepare("INSERT INTO kib_activities (teks, pelaku, waktu, warna) VALUES (?, ?, 'Baru saja', '#ef4444')");
            $stmtLog->execute([$actTeks, $actor]);
            $pdo->commit();

            json_success("Data monitoring \"{$row['nama_aset']}\" berhasil dihapus.", [
                'id'         => $id,
                'penempatan' => $penempatan,
            ]);
            break;

        default:
            json_error('Aksi monitoring tidak dikenali.', [], 400);
            break;
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('AJAX Monitoring Error: ' . $e->getMessage());
    json_error('Terjadi kesalahan pada database monitoring: ' . $e->getMessage(), [], 500);
}
