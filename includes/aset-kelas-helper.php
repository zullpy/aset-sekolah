<?php
/**
 * Helper Data Aset Kelas (Klaim Aset)
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

const ASET_KELAS_VALID_IDS = [1, 2, 3, 4, 5, 6];

const ASET_KELAS_KIB_META = [
    'kib-a' => ['table' => 'kib_a', 'kode_huruf' => 'A', 'label' => 'KIB A', 'default_jenis' => 'Tanah'],
    'kib-b' => ['table' => 'kib_b', 'kode_huruf' => 'B', 'label' => 'KIB B', 'default_jenis' => 'Peralatan & Mesin'],
    'kib-c' => ['table' => 'kib_c', 'kode_huruf' => 'C', 'label' => 'KIB C', 'default_jenis' => 'Gedung & Bangunan'],
    'kib-d' => ['table' => 'kib_d', 'kode_huruf' => 'D', 'label' => 'KIB D', 'default_jenis' => 'Jalan, Irigasi & Jaringan'],
    'kib-e' => ['table' => 'kib_e', 'kode_huruf' => 'E', 'label' => 'KIB E', 'default_jenis' => 'Buku & Aset Tetap Lainnya'],
    'kib-f' => ['table' => 'kib_f', 'kode_huruf' => 'F', 'label' => 'KIB F', 'default_jenis' => 'Konstruksi Dalam Pengerjaan'],
];

/**
 * Memastikan tabel klaim_aset tersedia di database
 */
function ensure_klaim_aset_table(PDO $pdo): void
{
    // Tabel klaim_aset telah dikelola melalui migrasi 012_create_klaim_aset.sql
}

/**
 * Mendeteksi kelas milik operator (1-6) berdasarkan username, nama lengkap, atau email
 * Mendukung format:
 * - "operator1", "operator 1", "operator-1", "operator_1", "operator.1", "operator kelas 1"
 * - "guru.kelas1", "guru kelas 1", "guru1", "wali kelas 1", "walikelas1"
 * - "kelas-1", "kelas 1", "kelas1", "op1", "op 1"
 */
function detect_operator_kelas_id(?array $user): ?int
{
    if (!$user) {
        return null;
    }

    $username = strtolower(trim((string) ($user['username'] ?? '')));
    if (preg_match('/(?:kelas|operator|guru|wali|op)[-_.\s]*(?:kelas[-_.\s]*)?([1-6])\b/', $username, $m)) {
        return (int) $m[1];
    }
    if (preg_match('/([1-6])$/', $username, $m)) {
        return (int) $m[1];
    }

    $nama = strtolower(trim((string) ($user['nama_lengkap'] ?? '')));
    if (preg_match('/(?:kelas|operator|guru|wali|op)[-_.\s]*(?:kelas[-_.\s]*)?([1-6])\b/', $nama, $m)) {
        return (int) $m[1];
    }
    if (preg_match('/\bkelas\s*([1-6])\b/', $nama, $m)) {
        return (int) $m[1];
    }

    $email = strtolower(trim((string) ($user['email'] ?? '')));
    if (preg_match('/(?:kelas|operator|guru|wali|op)[-_.\s]*(?:kelas[-_.\s]*)?([1-6])\b/', $email, $m)) {
        return (int) $m[1];
    }

    // Fallback untuk akun operator default/generic yang belum memiliki nomor kelas di username/nama
    // Default ke Kelas 1 agar konsisten dengan Data Aset Kelas (resolve_kelas_id)
    $role = strtolower(trim((string) ($user['role'] ?? '')));
    if ($role === 'operator') {
        if (!str_contains($username, 'penjaga') && !str_contains($nama, 'penjaga') && !str_contains($email, 'penjaga')) {
            return 1;
        }
    }

    return null;
}

/**
 * Validasi dan resolusi kelas_id dari parameter request
 */
function resolve_kelas_id($rawKelasId, ?array $user): ?int
{
    if ($rawKelasId === null || $rawKelasId === '') {
        $detected = detect_operator_kelas_id($user);
        return $detected ?? 1;
    }

    if (!is_numeric($rawKelasId)) {
        return null;
    }

    $id = (int) $rawKelasId;
    return in_array($id, ASET_KELAS_VALID_IDS, true) ? $id : null;
}

/**
 * Memeriksa apakah pengguna berwenang mengakses/mengubah data kelas tertentu
 */
function user_can_access_kelas(?array $user, int $kelasId): bool
{
    if (!$user || !in_array($kelasId, ASET_KELAS_VALID_IDS, true)) {
        return false;
    }

    $role = strtolower(trim((string) ($user['role'] ?? '')));
    if ($role === 'administrator') {
        return true;
    }

    if ($role !== 'operator') {
        return false;
    }

    $assignedKelas = detect_operator_kelas_id($user);
    if ($assignedKelas !== null) {
        return $assignedKelas === $kelasId;
    }

    // Operator umum tanpa kelas spesifik tetap diizinkan
    return true;
}

/**
 * Memformat angka/string harga ke format Rupiah ("Rp. 300.000")
 */
function format_rupiah_display(string $raw): string
{
    $clean = trim($raw);
    if ($clean === '') {
        return 'Rp. 0';
    }

    $digits = preg_replace('/[^\d]/', '', $clean);
    if ($digits === '') {
        return $clean;
    }

    return 'Rp. ' . number_format((float) $digits, 0, ',', '.');
}

/**
 * Memformat nomor registrasi menjadi 5 digit zero-padded
 */
function format_no_reg_5digit(string $raw): string
{
    $digits = preg_replace('/[^\d]/', '', trim($raw));
    if ($digits === '') {
        return '00001';
    }
    return str_pad((string) ((int) $digits), 5, '0', STR_PAD_LEFT);
}

/**
 * Mengambil nomor registrasi berikutnya untuk tabel klaim_aset
 */
function get_next_klaim_no_reg(PDO $pdo, int $kelasId): string
{
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(CAST(no_reg AS UNSIGNED)), 0) FROM klaim_aset WHERE kelas_id = ? AND no_reg REGEXP '^[0-9]+$'");
    $stmt->execute([$kelasId]);
    $lastReg = (int) $stmt->fetchColumn();
    return str_pad((string) ($lastReg + 1), 5, '0', STR_PAD_LEFT);
}

/**
 * Mengambil jumlah stok numerik dari kolom jumlah string (misal "10 Unit" -> 10)
 */
function parse_jumlah_aset($raw): int
{
    $digits = preg_replace('/[^\d]/', '', (string) $raw);
    if ($digits === '') {
        return 1;
    }
    return max(1, (int) $digits);
}

/**
 * Membentuk label spesifikasi yang informatif per jenis KIB
 */
function build_spesifikasi_label(string $kib, array $row): string
{
    $parts = [];

    switch ($kib) {
        case 'kib-b':
            if (!empty($row['spesifikasi'])) $parts[] = trim((string) $row['spesifikasi']);
            if (!empty($row['merk'])) $parts[] = 'Merk: ' . trim((string) $row['merk']);
            if (!empty($row['bahan'])) $parts[] = 'Bahan: ' . trim((string) $row['bahan']);
            break;
        case 'kib-e':
            if (!empty($row['spesifikasi_judul'])) $parts[] = trim((string) $row['spesifikasi_judul']);
            if (!empty($row['spesifikasi_pengarang'])) $parts[] = trim((string) $row['spesifikasi_pengarang']);
            if (!empty($row['spesifikasi_penerbit'])) $parts[] = trim((string) $row['spesifikasi_penerbit']);
            break;
        case 'kib-a':
            if (!empty($row['luas'])) $parts[] = 'Luas: ' . trim((string) $row['luas']);
            if (!empty($row['penggunaan'])) $parts[] = trim((string) $row['penggunaan']);
            break;
        case 'kib-c':
            if (!empty($row['konstruksi'])) $parts[] = trim((string) $row['konstruksi']);
            if (!empty($row['luas'])) $parts[] = 'Luas: ' . trim((string) $row['luas']);
            break;
        case 'kib-d':
            if (!empty($row['konstruksi'])) $parts[] = trim((string) $row['konstruksi']);
            if (!empty($row['ukuran'])) $parts[] = trim((string) $row['ukuran']);
            break;
        case 'kib-f':
            if (!empty($row['tingkat'])) $parts[] = trim((string) $row['tingkat']);
            if (!empty($row['beton'])) $parts[] = trim((string) $row['beton']);
            if (!empty($row['luas'])) $parts[] = 'Luas: ' . trim((string) $row['luas']);
            break;
    }

    $label = implode(' | ', array_filter($parts));
    if ($label === '') {
        $kode = trim((string) ($row['kode'] ?? ''));
        $tahun = trim((string) ($row['tahun'] ?? ''));
        $label = trim($kode . ($tahun !== '' ? " ({$tahun})" : ''));
    }

    return $label !== '' ? $label : 'Standar';
}

/**
 * Mengambil satu record master aset berdasarkan kib & aset_id
 */
function fetch_master_aset_by_id(PDO $pdo, string $kib, int $asetId): ?array
{
    if (!isset(ASET_KELAS_KIB_META[$kib]) || $asetId <= 0) {
        return null;
    }

    $meta = ASET_KELAS_KIB_META[$kib];
    $table = $meta['table'];
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = ? LIMIT 1");
    $stmt->execute([$asetId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return null;
    }

    $tahun = trim((string) ($row['tahun'] ?? ($row['tgl_mulai'] ?? '')));
    if (preg_match('/(\d{4})/', $tahun, $m)) {
        $tahun = $m[1];
    }

    $rawHarga = $row['harga'] ?? ($row['harga_total'] ?? ($row['harga_satuan'] ?? ($row['biaya'] ?? '0')));
    $rawJenis = trim((string) ($row['jenis'] ?? ''));
    $jenisAset = $rawJenis !== '' ? $rawJenis : $meta['default_jenis'];

    $kodeAset = trim((string) ($row['kode'] ?? ''));
    if ($kodeAset === '') {
        $urut = str_pad((string) $asetId, 3, '0', STR_PAD_LEFT);
        $kodeAset = 'KIB-' . $meta['kode_huruf'] . '/' . ($tahun !== '' ? $tahun : date('Y')) . '/' . $urut;
    }

    $noReg = format_no_reg_5digit((string) ($row['no_reg'] ?? $asetId));
    $jumlahTotal = isset($row['jumlah']) ? parse_jumlah_aset($row['jumlah']) : 1;

    return [
        'kib'          => $kib,
        'aset_id'      => (int) $row['id'],
        'kode_aset'    => $kodeAset,
        'jenis_aset'   => $jenisAset,
        'jenis_kib'    => $meta['kode_huruf'],
        'nama_aset'    => trim((string) ($row['nama'] ?? '')),
        'no_reg'       => $noReg,
        'spesifikasi'  => build_spesifikasi_label($kib, $row),
        'tahun'        => $tahun !== '' ? $tahun : '-',
        'sumber'       => trim((string) ($row['sumber'] ?? '')) ?: '-',
        'harga'        => format_rupiah_display((string) $rawHarga),
        'jumlah_total' => $jumlahTotal,
        'raw'          => $row,
    ];
}

/**
 * Mengambil seluruh daftar master aset (KIB A–F) untuk dropdown Klaim Aset
 */
function fetch_all_master_aset_for_klaim(PDO $pdo, int $kelasId): array
{
    ensure_klaim_aset_table($pdo);

    // Hitung total jumlah yang sudah diklaim per (kib, aset_id)
    $claimedMap = [];
    $stmtClaimed = $pdo->query("SELECT kib, aset_id, COALESCE(SUM(jumlah), 0) AS total_klaim FROM klaim_aset GROUP BY kib, aset_id");
    while ($c = $stmtClaimed->fetch(PDO::FETCH_ASSOC)) {
        $claimedMap[$c['kib'] . ':' . (int) $c['aset_id']] = (int) $c['total_klaim'];
    }

    $items = [];
    foreach (ASET_KELAS_KIB_META as $kibKey => $meta) {
        $table = $meta['table'];
        $stmt = $pdo->query("SELECT * FROM {$table} ORDER BY nama ASC, id ASC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $asetId = (int) $row['id'];
            $master = fetch_master_aset_by_id($pdo, $kibKey, $asetId);
            if (!$master) {
                continue;
            }
            $used = $claimedMap[$kibKey . ':' . $asetId] ?? 0;
            $sisa = max(0, $master['jumlah_total'] - $used);
            unset($master['raw']);
            $master['stok_terklaim'] = $used;
            $master['stok_tersedia'] = $sisa;
            $items[] = $master;
        }
    }

    return $items;
}
