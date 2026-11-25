<?php
/**
 * Helper Bersama Fitur Pemeliharaan Aset
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

const PEMELIHARAAN_STAGES = [
    'Perlu pemeliharaan',
    'Sedang ditindak lanjut',
    'Selesai ditindak lanjut',
    'Digunakan kembali',
    'Disimpan',
];

const PEMELIHARAAN_TRANSITIONS = [
    'Perlu pemeliharaan'      => ['Sedang ditindak lanjut', 'Selesai ditindak lanjut', 'Digunakan kembali', 'Disimpan'],
    'Sedang ditindak lanjut'  => ['Selesai ditindak lanjut', 'Digunakan kembali', 'Disimpan'],
    'Selesai ditindak lanjut' => ['Digunakan kembali', 'Disimpan'],
    'Digunakan kembali'       => [],
    'Disimpan'                => [],
];

const PEMELIHARAAN_NEW_ENTRY_STATUSES = [
    'Perlu pemeliharaan',
    'Sedang ditindak lanjut',
];

const PEMELIHARAAN_CONDITIONS = [
    'Baik',
    'Rusak Ringan',
    'Rusak Berat',
    'Hilang',
];

const PEMELIHARAAN_FINAL_CONDITION_REQUIRED = [
    'Selesai ditindak lanjut',
    'Digunakan kembali',
    'Disimpan',
];

const PEMELIHARAAN_KIB_TABLES = [
    'kib-a' => ['table' => 'kib_a', 'label' => 'KIB A', 'has_jumlah' => false],
    'kib-b' => ['table' => 'kib_b', 'label' => 'KIB B', 'has_jumlah' => true],
    'kib-c' => ['table' => 'kib_c', 'label' => 'KIB C', 'has_jumlah' => false],
    'kib-d' => ['table' => 'kib_d', 'label' => 'KIB D', 'has_jumlah' => false],
    'kib-e' => ['table' => 'kib_e', 'label' => 'KIB E', 'has_jumlah' => true],
    'kib-f' => ['table' => 'kib_f', 'label' => 'KIB F', 'has_jumlah' => false],
];

function pml_penempatan_to_lokasi(string $raw): string
{
    $val = trim($raw);
    if ($val === '') {
        return 'Aset Umum';
    }
    $lower = strtolower($val);
    if (preg_match('/^kelas-(\d+)$/', $lower, $m)) {
        return 'R. Kelas ' . $m[1];
    }
    if ($lower === 'umum' || $lower === 'aset-umum' || $lower === 'aset umum') {
        return 'Aset Umum';
    }
    return $val;
}

function pml_parse_jumlah_int($raw): int
{
    $digits = preg_replace('/[^\d]/', '', (string) $raw);
    $val = ($digits === '') ? 1 : (int) $digits;
    return max(1, $val);
}

function pml_parse_date(string $raw, bool $allowEmptyToday = true): ?string
{
    $val = trim($raw);
    if ($val === '') {
        return $allowEmptyToday ? date('Y-m-d') : null;
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

function pml_format_date_id(?string $isoDate): string
{
    $raw = trim((string) $isoDate);
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
        return $m[3] . '/' . $m[2] . '/' . $m[1];
    }
    return $raw !== '' ? $raw : '-';
}

function pml_resolve_actor_name(PDO $pdo): string
{
    $currentUser = function_exists('current_user') ? current_user() : null;
    $actor = trim((string) ($currentUser['nama_lengkap'] ?? ''));

    if (!empty($currentUser['id'])) {
        $stmt = $pdo->prepare("SELECT nama_lengkap, username FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([(int) $currentUser['id']]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dbNama = trim((string) ($row['nama_lengkap'] ?? ''));
            $dbUser = trim((string) ($row['username'] ?? ''));
            if ($dbNama !== '') {
                $actor = $dbNama;
            } elseif ($dbUser !== '' && $actor === '') {
                $actor = $dbUser;
            }
        }
    }

    if ($actor === '') {
        $actor = trim((string) ($currentUser['username'] ?? '')) ?: 'Administrator';
    }

    return $actor;
}

function pml_parse_dokumentasi($raw): array
{
    if (is_array($raw)) {
        $list = $raw;
    } else {
        $str = trim((string) $raw);
        if ($str === '') {
            return [];
        }
        $decoded = json_decode($str, true);
        if (is_array($decoded)) {
            $list = $decoded;
        } else {
            $list = [$str];
        }
    }

    $clean = [];
    foreach ($list as $item) {
        $path = str_replace('\\', '/', trim((string) $item));
        if ($path !== '' && !str_contains($path, '..')) {
            $clean[] = ltrim($path, '/');
        }
    }
    return array_values(array_unique($clean));
}

function pml_find_kib_asset(PDO $pdo, string $kibId, int $asetId): ?array
{
    if (!isset(PEMELIHARAAN_KIB_TABLES[$kibId]) || $asetId <= 0) {
        return null;
    }
    $meta = PEMELIHARAAN_KIB_TABLES[$kibId];
    $table = $meta['table'];
    $jumlahCol = $meta['has_jumlah'] ? 'jumlah' : 'NULL AS jumlah';

    $stmt = $pdo->prepare("
        SELECT id, kode, nama, penempatan, {$jumlahCol}
        FROM {$table}
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$asetId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    return [
        'kib'          => $kibId,
        'kib_label'    => $meta['label'],
        'aset_id'      => (int) $row['id'],
        'kode'         => (string) $row['kode'],
        'nama'         => (string) $row['nama'],
        'penempatan'   => (string) ($row['penempatan'] ?? 'umum'),
        'lokasi'       => pml_penempatan_to_lokasi((string) ($row['penempatan'] ?? 'umum')),
        'jumlah_total' => $meta['has_jumlah'] ? pml_parse_jumlah_int($row['jumlah'] ?? 1) : 1,
    ];
}

function pml_get_lokasi_options(PDO $pdo): array
{
    $ordered = [];
    $seen = [];

    $addLoc = static function (string $loc) use (&$ordered, &$seen): void {
        $clean = trim($loc);
        if ($clean === '') {
            return;
        }
        $key = mb_strtolower($clean);
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $ordered[] = $clean;
        }
    };

    // 1. Ambil daftar penempatan dari definisi kolom database (kelas-1..kelas-6, umum)
    $colStmt = $pdo->query("SHOW COLUMNS FROM monitoring_aset LIKE 'penempatan'");
    $colInfo = $colStmt->fetch(PDO::FETCH_ASSOC);
    if ($colInfo && !empty($colInfo['Type']) && preg_match_all("/'([^']+)'/", (string) $colInfo['Type'], $matches)) {
        foreach ($matches[1] as $enumVal) {
            if ($enumVal === 'umum') {
                continue; // Sisipkan setelah ruang khusus agar urutan rapi
            }
            $addLoc(pml_penempatan_to_lokasi($enumVal));
        }
    }

    // 2. Tambahkan ruang penempatan aktual dari tabel aset & pemeliharaan (termasuk R. Guru / ruang lain)
    $penStmt = $pdo->query("SELECT DISTINCT penempatan FROM monitoring_aset WHERE penempatan IS NOT NULL AND penempatan <> ''");
    foreach ($penStmt->fetchAll(PDO::FETCH_COLUMN) as $penVal) {
        $mapped = pml_penempatan_to_lokasi((string) $penVal);
        if ($mapped !== 'Aset Umum') {
            $addLoc($mapped);
        }
    }

    foreach (['kib_a', 'kib_c'] as $tbl) {
        $locStmt = $pdo->query("SELECT DISTINCT lokasi FROM {$tbl} WHERE lokasi IS NOT NULL AND TRIM(lokasi) <> ''");
        foreach ($locStmt->fetchAll(PDO::FETCH_COLUMN) as $rawLoc) {
            $addLoc(pml_penempatan_to_lokasi((string) $rawLoc));
        }
    }

    $addLoc('R. Guru');
    $addLoc('Aset Umum');

    $pmlLocStmt = $pdo->query("SELECT DISTINCT lokasi FROM pemeliharaan WHERE lokasi IS NOT NULL AND TRIM(lokasi) <> '' ORDER BY lokasi ASC");
    foreach ($pmlLocStmt->fetchAll(PDO::FETCH_COLUMN) as $rawLoc) {
        $addLoc(pml_penempatan_to_lokasi((string) $rawLoc));
    }

    return $ordered;
}

function pml_sync_from_monitoring(PDO $pdo, array $mon, string $actorName): void
{
    $monitoringId = (int) ($mon['id'] ?? 0);
    if ($monitoringId <= 0) {
        return;
    }

    $kondisi = trim((string) ($mon['kondisi'] ?? ''));
    $perlu   = trim((string) ($mon['perlu_pemeliharaan'] ?? 'Tidak'));
    $needsQueue = in_array($kondisi, ['Rusak Ringan', 'Rusak Berat'], true) && $perlu === 'Ya';

    $stmtExist = $pdo->prepare("
        SELECT id, status, jumlah, jumlah_aktif
        FROM pemeliharaan
        WHERE monitoring_id = ?
        ORDER BY id ASC
        LIMIT 1
    ");
    $stmtExist->execute([$monitoringId]);
    $existing = $stmtExist->fetch(PDO::FETCH_ASSOC);

    if ($needsQueue) {
        $lokasi = pml_penempatan_to_lokasi((string) ($mon['penempatan'] ?? 'umum'));
        $jumlah = max(1, (int) ($mon['jumlah'] ?? 1));
        $tglUpdate = trim((string) ($mon['tgl_update'] ?? '')) ?: date('Y-m-d');

        if (!$existing) {
            $stmtIns = $pdo->prepare("
                INSERT INTO pemeliharaan
                    (kib, aset_id, monitoring_id, parent_id, kode_aset, nama_aset, lokasi,
                     kondisi_awal, kondisi_akhir, jumlah, jumlah_aktif, status, tanggal_update, dokumentasi, created_by)
                VALUES (?, ?, ?, NULL, ?, ?, ?, ?, NULL, ?, ?, 'Perlu pemeliharaan', ?, NULL, ?)
            ");
            $stmtIns->execute([
                (string) $mon['kib'],
                (int) $mon['aset_id'],
                $monitoringId,
                (string) $mon['kode_aset'],
                (string) $mon['nama_aset'],
                $lokasi,
                $kondisi,
                $jumlah,
                $jumlah,
                $tglUpdate,
                $actorName,
            ]);
            return;
        }

        // Hindari duplikasi dan hanya perbarui jika antrean awal belum dipecah / diproses
        $stmtChildren = $pdo->prepare("SELECT COUNT(*) FROM pemeliharaan WHERE parent_id = ?");
        $stmtChildren->execute([(int) $existing['id']]);
        $hasChildren = ((int) $stmtChildren->fetchColumn()) > 0;

        if (
            !$hasChildren
            && $existing['status'] === 'Perlu pemeliharaan'
            && (int) $existing['jumlah_aktif'] === (int) $existing['jumlah']
        ) {
            $stmtUpd = $pdo->prepare("
                UPDATE pemeliharaan
                SET kib = ?, aset_id = ?, kode_aset = ?, nama_aset = ?, lokasi = ?,
                    kondisi_awal = ?, jumlah = ?, jumlah_aktif = ?, tanggal_update = ?
                WHERE id = ?
            ");
            $stmtUpd->execute([
                (string) $mon['kib'],
                (int) $mon['aset_id'],
                (string) $mon['kode_aset'],
                (string) $mon['nama_aset'],
                $lokasi,
                $kondisi,
                $jumlah,
                $jumlah,
                $tglUpdate,
                (int) $existing['id'],
            ]);
        }
        return;
    }

    if ($existing) {
        $stmtChildren = $pdo->prepare("SELECT COUNT(*) FROM pemeliharaan WHERE parent_id = ?");
        $stmtChildren->execute([(int) $existing['id']]);
        $hasChildren = ((int) $stmtChildren->fetchColumn()) > 0;

        if (
            !$hasChildren
            && $existing['status'] === 'Perlu pemeliharaan'
            && (int) $existing['jumlah_aktif'] === (int) $existing['jumlah']
        ) {
            $stmtDel = $pdo->prepare("DELETE FROM pemeliharaan WHERE id = ?");
            $stmtDel->execute([(int) $existing['id']]);
        }
    }
}

function pml_unlink_monitoring(PDO $pdo, int $monitoringId): void
{
    if ($monitoringId <= 0) {
        return;
    }

    $stmtExist = $pdo->prepare("
        SELECT id, status, jumlah, jumlah_aktif
        FROM pemeliharaan
        WHERE monitoring_id = ?
        ORDER BY id ASC
        LIMIT 1
    ");
    $stmtExist->execute([$monitoringId]);
    $existing = $stmtExist->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        return;
    }

    $stmtChildren = $pdo->prepare("SELECT COUNT(*) FROM pemeliharaan WHERE parent_id = ?");
    $stmtChildren->execute([(int) $existing['id']]);
    $hasChildren = ((int) $stmtChildren->fetchColumn()) > 0;

    if (
        !$hasChildren
        && $existing['status'] === 'Perlu pemeliharaan'
        && (int) $existing['jumlah_aktif'] === (int) $existing['jumlah']
    ) {
        $stmtDel = $pdo->prepare("DELETE FROM pemeliharaan WHERE id = ?");
        $stmtDel->execute([(int) $existing['id']]);
    } else {
        $stmtNull = $pdo->prepare("UPDATE pemeliharaan SET monitoring_id = NULL WHERE monitoring_id = ?");
        $stmtNull->execute([$monitoringId]);
    }
}
