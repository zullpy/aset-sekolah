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

require_auth();

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

function fetch_assets_by_penempatan(PDO $pdo, string $penempatan): array
{
    $options = [];
    foreach (MONITORING_KIB_TABLES as $kibId => $meta) {
        $table = $meta['table'];
        $jenisCol = $meta['has_jenis'] ? 'jenis' : 'NULL AS jenis';
        $jumlahCol = $meta['has_jumlah'] ? 'jumlah' : 'NULL AS jumlah';

        $stmt = $pdo->prepare("SELECT id, kode, nama, {$jenisCol}, {$jumlahCol}, penempatan FROM {$table} WHERE penempatan = ? ORDER BY nama ASC, id ASC");
        $stmt->execute([$penempatan]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $jenisVal = trim((string) ($row['jenis'] ?? ''));
            if ($jenisVal === '') {
                $jenisVal = $meta['default_jenis'];
            }
            $options[] = [
                'kib'         => $kibId,
                'kib_label'   => $meta['label'],
                'aset_id'     => (int) $row['id'],
                'kode'        => (string) $row['kode'],
                'nama'        => (string) $row['nama'],
                'jenis'       => $jenisVal,
                'jumlah'      => $meta['has_jumlah'] ? parse_aset_jumlah_int($row['jumlah'] ?? 1) : 1,
                'penempatan'  => (string) $row['penempatan'],
            ];
        }
    }
    return $options;
}

function count_registered_assets(PDO $pdo, string $penempatan): int
{
    $total = 0;
    foreach (MONITORING_KIB_TABLES as $meta) {
        $table = $meta['table'];
        if ($penempatan === 'semua') {
            $total += (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE penempatan = ?");
            $stmt->execute([$penempatan]);
            $total += (int) $stmt->fetchColumn();
        }
    }
    return $total;
}

function find_asset_in_penempatan(PDO $pdo, string $kibId, int $asetId, string $penempatan): ?array
{
    if (!isset(MONITORING_KIB_TABLES[$kibId]) || $asetId <= 0) {
        return null;
    }
    $meta = MONITORING_KIB_TABLES[$kibId];
    $table = $meta['table'];
    $jenisCol = $meta['has_jenis'] ? 'jenis' : 'NULL AS jenis';
    $jumlahCol = $meta['has_jumlah'] ? 'jumlah' : 'NULL AS jumlah';

    $stmt = $pdo->prepare("SELECT id, kode, nama, {$jenisCol}, {$jumlahCol}, penempatan FROM {$table} WHERE id = ? AND penempatan = ? LIMIT 1");
    $stmt->execute([$asetId, $penempatan]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    $jenisVal = trim((string) ($row['jenis'] ?? ''));
    if ($jenisVal === '') {
        $jenisVal = $meta['default_jenis'];
    }

    return [
        'kib'        => $kibId,
        'kib_label'  => $meta['label'],
        'aset_id'    => (int) $row['id'],
        'kode'       => (string) $row['kode'],
        'nama'       => (string) $row['nama'],
        'jenis'      => $jenisVal,
        'jumlah'     => $meta['has_jumlah'] ? parse_aset_jumlah_int($row['jumlah'] ?? 1) : 1,
        'penempatan' => (string) $row['penempatan'],
    ];
}

function build_monitoring_summary(array $items, int $registeredCount): array
{
    $summary = [
        'total_aset_terdaftar' => $registeredCount,
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
                    'jumlah'           => (int) $row['jumlah'],
                    'kondisi'          => (string) $row['kondisi'],
                    'catatan'          => (string) ($row['catatan'] ?? ''),
                    'petugas'          => (string) ($row['petugas'] ?? ''),
                    'tgl_update'       => (string) $row['tgl_update'],
                ];
            }, $rows);

            $registeredCount = count_registered_assets($pdo, $penempatan);
            $summary = build_monitoring_summary($items, $registeredCount);
            $assetOptions = ($penempatan === 'semua') ? [] : fetch_assets_by_penempatan($pdo, $penempatan);

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

            $assetOptions = fetch_assets_by_penempatan($pdo, $penempatan);
            json_success('Daftar aset lokasi berhasil dimuat.', [
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

            $id      = (int) ($_POST['id'] ?? 0);
            $kibId   = strtolower(trim((string) ($_POST['kib'] ?? '')));
            $asetId  = (int) ($_POST['aset_id'] ?? 0);
            $kondisi = trim((string) ($_POST['kondisi'] ?? ''));
            $jumlah  = (int) ($_POST['jumlah'] ?? 0);
            $catatan = trim((string) ($_POST['catatan'] ?? ''));
            $petugas = trim((string) ($_POST['petugas'] ?? ''));
            $tglRaw  = (string) ($_POST['tgl_update'] ?? '');

            if (!isset(MONITORING_KIB_TABLES[$kibId]) || $asetId <= 0) {
                json_error('Silakan pilih Nama Aset yang tersedia pada lokasi ini.', ['field' => 'aset_key'], 422);
            }

            $asset = find_asset_in_penempatan($pdo, $kibId, $asetId, $penempatan);
            if ($asset === null) {
                json_error('Aset tidak ditemukan pada penempatan ' . MONITORING_LOCATIONS[$penempatan] . '.', ['field' => 'aset_key'], 422);
            }

            if (!in_array($kondisi, MONITORING_CONDITIONS, true)) {
                json_error('Kondisi aset tidak valid.', ['field' => 'kondisi'], 422);
            }

            if ($jumlah < 1) {
                json_error('Jumlah minimal 1.', ['field' => 'jumlah'], 422);
            }

            $tglUpdate = parse_monitoring_date($tglRaw);
            if ($tglUpdate === null) {
                json_error('Format Tanggal Update tidak valid.', ['field' => 'tgl_update'], 422);
            }

            $currentUser = current_user();
            $defaultActor = $currentUser['nama_lengkap'] ?? $currentUser['username'] ?? 'Administrator';
            if ($petugas === '') {
                $petugas = $defaultActor;
            }

            if ($id > 0) {
                $stmtCheck = $pdo->prepare("SELECT id FROM monitoring_aset WHERE id = ? AND penempatan = ? LIMIT 1");
                $stmtCheck->execute([$id, $penempatan]);
                if (!$stmtCheck->fetch()) {
                    json_error('Data monitoring tidak ditemukan pada lokasi ini.', [], 404);
                }

                $stmtUpd = $pdo->prepare("
                    UPDATE monitoring_aset
                    SET kib = ?, aset_id = ?, kode_aset = ?, nama_aset = ?, jenis_aset = ?,
                        jumlah = ?, kondisi = ?, catatan = ?, petugas = ?, tgl_update = ?
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
                        (penempatan, kib, aset_id, kode_aset, nama_aset, jenis_aset, jumlah, kondisi, catatan, petugas, tgl_update)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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

            json_success($msg, [
                'id'   => $savedId,
                'item' => [
                    'id'               => $savedId,
                    'penempatan'       => $penempatan,
                    'penempatan_label' => MONITORING_LOCATIONS[$penempatan],
                    'kib'              => $asset['kib'],
                    'kib_label'        => $asset['kib_label'],
                    'aset_id'          => $asset['aset_id'],
                    'kode_aset'        => $asset['kode'],
                    'nama_aset'        => $asset['nama'],
                    'jenis_aset'       => $asset['jenis'],
                    'jumlah'           => $jumlah,
                    'kondisi'          => $kondisi,
                    'catatan'          => $catatan,
                    'petugas'          => $petugas,
                    'tgl_update'       => $tglUpdate,
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

            $stmtFind = $pdo->prepare("SELECT nama_aset, kode_aset FROM monitoring_aset WHERE id = ? AND penempatan = ? LIMIT 1");
            $stmtFind->execute([$id, $penempatan]);
            $row = $stmtFind->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                json_error('Data monitoring tidak ditemukan pada lokasi ini.', [], 404);
            }

            $stmtDel = $pdo->prepare("DELETE FROM monitoring_aset WHERE id = ? AND penempatan = ?");
            $stmtDel->execute([$id, $penempatan]);

            $currentUser = current_user();
            $actor = $currentUser['nama_lengkap'] ?? $currentUser['username'] ?? 'Administrator';
            $locLabel = MONITORING_LOCATIONS[$penempatan];
            $actTeks = "Data monitoring {$row['nama_aset']} ({$row['kode_aset']}) di {$locLabel} dihapus";
            $stmtLog = $pdo->prepare("INSERT INTO kib_activities (teks, pelaku, waktu, warna) VALUES (?, ?, 'Baru saja', '#ef4444')");
            $stmtLog->execute([$actTeks, $actor]);

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
    error_log('AJAX Monitoring Error: ' . $e->getMessage());
    json_error('Terjadi kesalahan pada database monitoring: ' . $e->getMessage(), [], 500);
}
