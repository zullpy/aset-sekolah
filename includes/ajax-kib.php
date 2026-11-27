<?php
/**
 * Backend Endpoint AJAX - Dataset KIB (A s/d F) & Aktivitas Terintegrasi
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 * Lokasi: includes/ajax-kib.php
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/conn.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ajax.php';

// Pastikan pengguna telah login
require_auth();

$action = $_GET['action'] ?? $_POST['action'] ?? 'all';

if ($action !== 'download') {
    header('Content-Type: application/json; charset=utf-8');
}

const KIB_MAX_FILE_BYTES = 5242880; // 5 MB

// Pemetaan ID KIB dengan nama tabel dan konfigurasi kolom
$kibConfigs = [
    'kib-a' => [
        'table'    => 'kib_a',
        'letter'   => 'A',
        'nama'     => 'KIB A (Tanah)',
        'kode'     => 'KIB A',
        'warna'    => '#852019',
        'file'     => 'kib-a.php',
        'fields'   => ['kode', 'nama', 'no_reg', 'luas', 'tahun', 'lokasi', 'status_tanah', 'sertifikat', 'penggunaan', 'sumber', 'harga', 'lampiran'],
        'required' => [
            'nama'         => 'Nama Aset',
            'luas'         => 'Luas (m²)',
            'tahun'        => 'Tahun Perolehan',
            'lokasi'       => 'Lokasi Aset',
            'status_tanah' => 'Status Tanah',
            'sertifikat'   => 'No Sertifikat',
            'penggunaan'   => 'Penggunaan',
            'sumber'       => 'Sumber',
            'harga'        => 'Harga (Rp.)',
        ],
    ],
    'kib-b' => [
        'table'    => 'kib_b',
        'letter'   => 'B',
        'nama'     => 'KIB B (Peralatan & Mesin)',
        'kode'     => 'KIB B',
        'warna'    => '#f97316',
        'file'     => 'kib-b.php',
        'fields'   => ['kode', 'jenis', 'nama', 'no_reg', 'merk', 'spesifikasi', 'bahan', 'no_pabrik', 'tahun', 'jumlah', 'harga', 'sumber', 'lampiran'],
        'required' => [
            'jenis'       => 'Jenis Aset',
            'nama'        => 'Nama Aset',
            'merk'        => 'Merk',
            'spesifikasi' => 'Spesifikasi',
            'bahan'       => 'Bahan',
            'tahun'       => 'Tahun Perolehan',
            'sumber'      => 'Sumber',
            'harga'       => 'Harga (Rp.)',
            'jumlah'      => 'Jumlah',
        ],
    ],
    'kib-c' => [
        'table'    => 'kib_c',
        'letter'   => 'C',
        'nama'     => 'KIB C (Gedung & Bangunan)',
        'kode'     => 'KIB C',
        'warna'    => '#eab308',
        'file'     => 'kib-c.php',
        'fields'   => ['kode', 'nama', 'no_reg', 'konstruksi', 'luas', 'lokasi', 'tgl_izin', 'status_tanah', 'tahun', 'harga', 'sumber', 'lampiran'],
        'required' => [
            'nama'         => 'Nama Aset',
            'konstruksi'   => 'Konstruksi',
            'luas'         => 'Luas (m²)',
            'lokasi'       => 'Lokasi Aset',
            'tgl_izin'     => 'No & Tanggal Perizinan',
            'status_tanah' => 'Status Tanah',
            'tahun'        => 'Tahun Perolehan',
            'sumber'       => 'Sumber',
            'harga'        => 'Harga (Rp.)',
        ],
    ],
    'kib-d' => [
        'table'    => 'kib_d',
        'letter'   => 'D',
        'nama'     => 'KIB D (Jalan, Irigasi & Jaringan)',
        'kode'     => 'KIB D',
        'warna'    => '#22c55e',
        'file'     => 'kib-d.php',
        'fields'   => ['kode', 'nama', 'no_reg', 'konstruksi', 'ukuran', 'luas', 'tahun', 'dokumen', 'harga', 'sumber', 'lampiran'],
        'required' => [
            'nama'       => 'Nama Aset',
            'konstruksi' => 'Konstruksi',
            'ukuran'     => 'Ukuran (m)',
            'dokumen'    => 'No & Tanggal Dokumen',
            'tahun'      => 'Tahun Perolehan',
            'sumber'     => 'Sumber',
            'harga'      => 'Harga (Rp.)',
        ],
    ],
    'kib-e' => [
        'table'    => 'kib_e',
        'letter'   => 'E',
        'nama'     => 'KIB E (Aset Tetap Lainnya)',
        'kode'     => 'KIB E',
        'warna'    => '#06b6d4',
        'file'     => 'kib-e.php',
        'fields'   => ['kode', 'jenis', 'nama', 'no_reg', 'spesifikasi_judul', 'spesifikasi_pengarang', 'spesifikasi_penerbit', 'spesifikasi_tahun', 'tahun', 'jumlah', 'harga_satuan', 'harga_total', 'sumber', 'lampiran'],
        'required' => [
            'jenis'                 => 'Jenis Aset',
            'nama'                  => 'Nama Buku',
            'spesifikasi_judul'     => 'Judul Buku',
            'spesifikasi_pengarang' => 'Penulis',
            'spesifikasi_penerbit'  => 'Penerbit',
            'spesifikasi_tahun'     => 'Tahun Terbit',
            'sumber'                => 'Sumber',
            'tahun'                 => 'Tahun Perolehan',
            'jumlah'                => 'Jumlah',
            'harga_satuan'          => 'Harga Satuan (Rp.)',
        ],
    ],
    'kib-f' => [
        'table'    => 'kib_f',
        'letter'   => 'F',
        'nama'     => 'KIB F (Konstruksi Dalam Pengerjaan)',
        'kode'     => 'KIB F',
        'warna'    => '#8b5cf6',
        'file'     => 'kib-f.php',
        'fields'   => ['kode', 'jenis', 'nama', 'no_reg', 'tingkat', 'beton', 'luas', 'letak_bangunan', 'dokumen', 'tgl_mulai', 'status_tanah', 'biaya', 'persen_fisik', 'status_pengerjaan', 'sumber', 'keterangan', 'lampiran'],
        'required' => [
            'jenis'          => 'Jenis Aset',
            'nama'           => 'Nama Aset',
            'tingkat'        => 'Tingkat / Tidak',
            'beton'          => 'Beton / Tidak',
            'letak_bangunan' => 'Letak Bangunan',
            'dokumen'        => 'Tanggal & No Dokumen',
            'tgl_mulai'      => 'Tanggal Mulai',
            'status_tanah'   => 'Status Tanah',
            'sumber'         => 'Sumber',
            'biaya'          => 'Biaya (Rp.)',
            'persen_fisik'   => 'Persentase Fisik',
        ],
    ],
];

function resolve_kib_entry(string $rawKib, array $kibConfigs): ?array
{
    $normalized = strtolower(trim($rawKib));
    if (preg_match('/^[a-f]$/', $normalized)) {
        $normalized = 'kib-' . $normalized;
    }
    if (!isset($kibConfigs[$normalized])) {
        return null;
    }
    return ['id' => $normalized, 'cfg' => $kibConfigs[$normalized]];
}

function format_kode_aset(string $kibLetter, int $tahun, int $seq): string
{
    return 'KIB-' . strtoupper($kibLetter) . '/' . $tahun . '/' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
}

function format_no_registrasi(int $seq): string
{
    return str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
}

function validate_tahun_perolehan(string $yearStr, string $fieldLabel = 'Tahun perolehan'): array
{
    $maxYear = (int) date('Y');
    if (!preg_match('/^\d{4}$/', $yearStr)) {
        return [false, null, "{$fieldLabel} harus berupa 4 digit angka (1900–{$maxYear})."];
    }
    $yearInt = (int) $yearStr;
    if ($yearInt < 1900 || $yearInt > $maxYear) {
        return [false, null, "{$fieldLabel} harus antara 1900 sampai {$maxYear}."];
    }
    return [true, $yearInt, ''];
}

function parse_pure_integer_amount(string $raw): int
{
    $digits = preg_replace('/[^\d]/', '', $raw);
    return $digits === '' ? 0 : (int) $digits;
}

function format_rupiah_amount(int $amount): string
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function extract_asset_year(string $kibId, array $data): array
{
    $maxYear = (int) date('Y');
    if ($kibId === 'kib-f') {
        $tglMulai = trim((string) ($data['tgl_mulai'] ?? ''));
        $yearStr = '';

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2})$/', $tglMulai, $m)) {
            $d = (int) $m[1];
            $mo = (int) $m[2];
            $yy = (int) $m[3];
            $currYY = (int) date('y');
            $yyyy = ($yy <= $currYY + 10) ? (2000 + $yy) : (1900 + $yy);
            if (checkdate($mo, $d, $yyyy)) {
                $yearStr = (string) $yyyy;
            }
        } elseif (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $tglMulai, $m)) {
            if (checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
                $yearStr = $m[3];
            }
        } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $tglMulai, $m)) {
            if (checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                $yearStr = $m[1];
            }
        } elseif (preg_match('/\b(\d{4})\b/', $tglMulai, $m)) {
            $yearStr = $m[1];
        }

        if ($yearStr === '') {
            return [false, null, "Tanggal Mulai harus berformat dd/mm/yy yang valid (tahun 1900–{$maxYear})."];
        }
        return validate_tahun_perolehan($yearStr, 'Tahun pada Tanggal Mulai');
    }

    $tahun = trim((string) ($data['tahun'] ?? ''));
    return validate_tahun_perolehan($tahun, 'Tahun Perolehan');
}

function get_kib_upload_dir(string $kibId): string
{
    $baseDir = dirname(__DIR__) . '/uploads';
    if (!is_dir($baseDir)) {
        @mkdir($baseDir, 0755, true);
    }
    $htaccessPath = $baseDir . '/.htaccess';
    if (!file_exists($htaccessPath)) {
        @file_put_contents($htaccessPath, "Options -Indexes\n<FilesMatch \"\\.php$\">\n    Require all denied\n</FilesMatch>\n");
    }

    $kibDir = $baseDir . '/kib/' . $kibId;
    if (!is_dir($kibDir)) {
        @mkdir($kibDir, 0755, true);
    }
    return $kibDir;
}

function compress_image_to_limit(string $srcPath, string $mime, string $destPath, int $maxBytes): bool
{
    if (!extension_loaded('gd')) {
        return false;
    }

    $img = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($srcPath),
        'image/png'  => @imagecreatefrompng($srcPath),
        default      => false,
    };

    if (!$img) {
        return false;
    }

    $origW = imagesx($img);
    $origH = imagesy($img);
    if ($origW <= 0 || $origH <= 0) {
        imagedestroy($img);
        return false;
    }

    $scale = 1.0;
    $maxDim = 2400;
    if ($origW > $maxDim || $origH > $maxDim) {
        $scale = min($maxDim / $origW, $maxDim / $origH);
    }

    $qualities = [85, 72, 58, 45, 32];
    for ($step = 0; $step < 4; $step++) {
        $w = max(320, (int) round($origW * $scale));
        $h = max(320, (int) round($origH * $scale));

        $canvas = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $img, 0, 0, 0, 0, $w, $h, $origW, $origH);

        foreach ($qualities as $q) {
            if (@imagejpeg($canvas, $destPath, $q) && file_exists($destPath) && filesize($destPath) <= $maxBytes) {
                imagedestroy($canvas);
                imagedestroy($img);
                return true;
            }
        }
        imagedestroy($canvas);
        $scale *= 0.75;
    }

    imagedestroy($img);
    @unlink($destPath);
    return false;
}

function compress_pdf_to_limit(string $srcPath, string $destPath, int $maxBytes): bool
{
    $gsBin = '/usr/bin/gs';
    if (!is_executable($gsBin) || !function_exists('exec')) {
        return false;
    }

    foreach (['/ebook', '/screen'] as $preset) {
        $cmd = sprintf(
            '%s -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=%s -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s 2>/dev/null',
            escapeshellarg($gsBin),
            escapeshellarg($preset),
            escapeshellarg($destPath),
            escapeshellarg($srcPath)
        );
        $out = [];
        $ret = 1;
        @exec($cmd, $out, $ret);
        if ($ret === 0 && file_exists($destPath) && filesize($destPath) > 0 && filesize($destPath) <= $maxBytes) {
            return true;
        }
    }

    @unlink($destPath);
    return false;
}

function handle_lampiran_upload(string $kibId, array $file): array
{
    $errCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errCode === UPLOAD_ERR_NO_FILE) {
        return [true, null, ''];
    }
    if ($errCode !== UPLOAD_ERR_OK) {
        return [false, null, 'Gagal mengunggah file lampiran (kode error: ' . $errCode . ').'];
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return [false, null, 'File upload tidak valid.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmpPath);
    $allowedMimes = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
    ];

    if (!isset($allowedMimes[$mime])) {
        return [false, null, 'Format lampiran harus berupa PDF, JPG, atau PNG.'];
    }

    $origBase = pathinfo((string) ($file['name'] ?? 'lampiran'), PATHINFO_FILENAME);
    $safeBase = preg_replace('/[^A-Za-z0-9_-]+/', '_', $origBase);
    $safeBase = trim((string) $safeBase, '_');
    if ($safeBase === '') {
        $safeBase = 'lampiran';
    }
    $safeBase = substr($safeBase, 0, 50);

    $uploadDir = get_kib_upload_dir($kibId);
    $ext = $allowedMimes[$mime];
    $uniquePrefix = date('YmdHis') . '_' . bin2hex(random_bytes(3));
    $size = (int) filesize($tmpPath);

    if ($size <= KIB_MAX_FILE_BYTES) {
        $finalName = "{$safeBase}_{$uniquePrefix}.{$ext}";
        $destPath = $uploadDir . '/' . $finalName;
        if (!@move_uploaded_file($tmpPath, $destPath)) {
            return [false, null, 'Gagal menyimpan file lampiran ke server.'];
        }
        return [true, $finalName, ''];
    }

    // Kompresi otomatis bila ukuran > 5MB
    if ($mime === 'application/pdf') {
        $finalName = "{$safeBase}_{$uniquePrefix}.pdf";
        $destPath = $uploadDir . '/' . $finalName;
        if (!compress_pdf_to_limit($tmpPath, $destPath, KIB_MAX_FILE_BYTES)) {
            return [false, null, 'Ukuran PDF melebihi 5MB dan tidak dapat dikompres di bawah batas maksimal.'];
        }
        return [true, $finalName, ''];
    }

    $finalName = "{$safeBase}_{$uniquePrefix}.jpg";
    $destPath = $uploadDir . '/' . $finalName;
    if (!compress_image_to_limit($tmpPath, $mime, $destPath, KIB_MAX_FILE_BYTES)) {
        return [false, null, 'Ukuran gambar melebihi 5MB dan gagal dikompres di bawah batas maksimal.'];
    }
    return [true, $finalName, ''];
}

try {
    if (in_array($action, ['list', 'all', 'summary', 'options'], true)) {
        session_write_close();
    }

    switch ($action) {
        // Ambil data satu KIB tertentu
        case 'list':
            $kibId = $_GET['kib'] ?? $_POST['kib'] ?? '';
            if (!isset($kibConfigs[$kibId])) {
                json_error('Kategori KIB tidak valid.');
            }

            $table = $kibConfigs[$kibId]['table'];
            $stmt = $pdo->query("SELECT * FROM {$table} ORDER BY id ASC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            json_success("Data {$kibConfigs[$kibId]['nama']} berhasil diambil.", [
                'kib'  => $kibId,
                'data' => $rows,
            ]);
            break;

        // Ambil data seluruh KIB sekaligus (A s/d F)
        case 'all':
            $allData = [];
            foreach ($kibConfigs as $kibId => $cfg) {
                $table = $cfg['table'];
                $stmt = $pdo->query("SELECT * FROM {$table} ORDER BY id ASC");
                $allData[$kibId] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Ambil juga ringkasan kategori untuk Dashboard
            $summary = [];
            $totalUnitAll = 0;
            foreach ($kibConfigs as $kibId => $cfg) {
                $items = $allData[$kibId];
                $rowCount = count($items);
                $unitCount = array_reduce($items, function ($sum, $it) {
                    $q = isset($it['jumlah']) ? (int) $it['jumlah'] : 1;
                    return $sum + ($q > 0 ? $q : 1);
                }, 0);

                $totalUnitAll += $unitCount;

                $summary[] = [
                    'id'        => $kibId,
                    'kode'      => $cfg['kode'],
                    'nama'      => $cfg['nama'],
                    'jumlah'    => $unitCount,
                    'rowCount'  => $rowCount,
                    'warna'     => $cfg['warna'],
                    'url'       => 'data-aset/' . $cfg['file'],
                ];
            }

            // Ambil ringkasan kondisi & sebaran lokasi dari monitoring_aset untuk Dashboard
            $monitoringSummary = [
                'total_unit'   => 0,
                'baik'         => 0,
                'rusak_ringan' => 0,
                'rusak_berat'  => 0,
                'hilang'       => 0,
            ];
            $stmtMonKondisi = $pdo->query("SELECT kondisi, COALESCE(SUM(jumlah), 0) AS total_unit FROM monitoring_aset GROUP BY kondisi");
            while ($mk = $stmtMonKondisi->fetch(PDO::FETCH_ASSOC)) {
                $qty = (int) $mk['total_unit'];
                $monitoringSummary['total_unit'] += $qty;
                switch ($mk['kondisi']) {
                    case 'Baik':
                        $monitoringSummary['baik'] += $qty;
                        break;
                    case 'Rusak Ringan':
                        $monitoringSummary['rusak_ringan'] += $qty;
                        break;
                    case 'Rusak Berat':
                        $monitoringSummary['rusak_berat'] += $qty;
                        break;
                    case 'Hilang':
                        $monitoringSummary['hilang'] += $qty;
                        break;
                }
            }

            $locDefs = [
                'kelas-1' => ['label' => 'Kelas 1',   'warna' => '#ef4444', 'url' => 'monitoring/kelas-1.php'],
                'kelas-2' => ['label' => 'Kelas 2',   'warna' => '#f97316', 'url' => 'monitoring/kelas-2.php'],
                'kelas-3' => ['label' => 'Kelas 3',   'warna' => '#22c55e', 'url' => 'monitoring/kelas-3.php'],
                'kelas-4' => ['label' => 'Kelas 4',   'warna' => '#3b82f6', 'url' => 'monitoring/kelas-4.php'],
                'kelas-5' => ['label' => 'Kelas 5',   'warna' => '#8b5cf6', 'url' => 'monitoring/kelas-5.php'],
                'kelas-6' => ['label' => 'Kelas 6',   'warna' => '#475569', 'url' => 'monitoring/kelas-6.php'],
                'umum'    => ['label' => 'Aset Umum', 'warna' => '#14b8a6', 'url' => 'monitoring/aset-umum.php'],
            ];
            $locCounts = [];
            $stmtMonLoc = $pdo->query("SELECT penempatan, COALESCE(SUM(jumlah), 0) AS total_unit, COUNT(*) AS total_entri FROM monitoring_aset GROUP BY penempatan");
            while ($ml = $stmtMonLoc->fetch(PDO::FETCH_ASSOC)) {
                $locCounts[$ml['penempatan']] = [
                    'jumlah' => (int) $ml['total_unit'],
                    'entri'  => (int) $ml['total_entri'],
                ];
            }
            $monitoringLocations = [];
            foreach ($locDefs as $locKey => $locMeta) {
                $monitoringLocations[] = [
                    'id'     => $locKey,
                    'label'  => $locMeta['label'],
                    'jumlah' => $locCounts[$locKey]['jumlah'] ?? 0,
                    'entri'  => $locCounts[$locKey]['entri'] ?? 0,
                    'warna'  => $locMeta['warna'],
                    'url'    => $locMeta['url'],
                ];
            }

            // Ambil ringkasan pemeliharaan dari tabel pemeliharaan (berdasarkan SUM jumlah_aktif per status)
            $pemeliharaanSummary = [
                'perlu_pemeliharaan'      => 0,
                'sedang_ditindak_lanjut'  => 0,
                'selesai_ditindak_lanjut' => 0,
                'digunakan_kembali'       => 0,
                'disimpan'                => 0,
            ];
            $stmtPml = $pdo->query("SELECT status, COALESCE(SUM(jumlah_aktif), 0) AS total_unit FROM pemeliharaan GROUP BY status");
            while ($pm = $stmtPml->fetch(PDO::FETCH_ASSOC)) {
                $qty = (int) $pm['total_unit'];
                switch ($pm['status']) {
                    case 'Perlu pemeliharaan':
                        $pemeliharaanSummary['perlu_pemeliharaan'] = $qty;
                        break;
                    case 'Sedang ditindak lanjut':
                        $pemeliharaanSummary['sedang_ditindak_lanjut'] = $qty;
                        break;
                    case 'Selesai ditindak lanjut':
                        $pemeliharaanSummary['selesai_ditindak_lanjut'] = $qty;
                        break;
                    case 'Digunakan kembali':
                        $pemeliharaanSummary['digunakan_kembali'] = $qty;
                        break;
                    case 'Disimpan':
                        $pemeliharaanSummary['disimpan'] = $qty;
                        break;
                }
            }

            // Ambil 5 aktivitas terbaru untuk ringkasan Dashboard
            $stmtAct = $pdo->query("SELECT id, teks, pelaku, waktu, warna, created_at FROM kib_activities ORDER BY id DESC LIMIT 5");
            $activities = $stmtAct->fetchAll(PDO::FETCH_ASSOC);
            $totalActivities = (int) $pdo->query("SELECT COUNT(*) FROM kib_activities")->fetchColumn();

            json_success('Seluruh dataset KIB berhasil dimuat dari database.', [
                'datasets'            => $allData,
                'summary'             => $summary,
                'totalAset'           => $totalUnitAll,
                'monitoringSummary'   => $monitoringSummary,
                'monitoringLocations' => $monitoringLocations,
                'pemeliharaanSummary' => $pemeliharaanSummary,
                'activities'          => $activities,
                'totalActivities'     => $totalActivities,
            ]);
            break;

        // Ambil daftar aktivitas (mendukung limit, offset, dan pencarian untuk modal Lihat Semua)
        case 'activities':
            $limit  = (int) ($_GET['limit'] ?? 30);
            $offset = (int) ($_GET['offset'] ?? 0);
            $q      = trim((string) ($_GET['q'] ?? ''));

            $limit  = max(1, min(100, $limit));
            $offset = max(0, $offset);

            if ($q !== '') {
                $like = '%' . $q . '%';
                $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM kib_activities WHERE teks LIKE ? OR pelaku LIKE ?");
                $stmtTotal->execute([$like, $like]);
                $total = (int) $stmtTotal->fetchColumn();

                $stmtAct = $pdo->prepare("SELECT id, teks, pelaku, waktu, warna, created_at FROM kib_activities WHERE teks LIKE ? OR pelaku LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?");
                $stmtAct->bindValue(1, $like, PDO::PARAM_STR);
                $stmtAct->bindValue(2, $like, PDO::PARAM_STR);
                $stmtAct->bindValue(3, $limit, PDO::PARAM_INT);
                $stmtAct->bindValue(4, $offset, PDO::PARAM_INT);
                $stmtAct->execute();
                $activities = $stmtAct->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $total = (int) $pdo->query("SELECT COUNT(*) FROM kib_activities")->fetchColumn();
                $stmtAct = $pdo->prepare("SELECT id, teks, pelaku, waktu, warna, created_at FROM kib_activities ORDER BY id DESC LIMIT ? OFFSET ?");
                $stmtAct->bindValue(1, $limit, PDO::PARAM_INT);
                $stmtAct->bindValue(2, $offset, PDO::PARAM_INT);
                $stmtAct->execute();
                $activities = $stmtAct->fetchAll(PDO::FETCH_ASSOC);
            }

            json_success('Data aktivitas berhasil dimuat.', [
                'activities' => $activities,
                'total'      => $total,
                'limit'      => $limit,
                'offset'     => $offset,
                'hasMore'    => ($offset + count($activities)) < $total,
            ]);
            break;

        // Pratinjau Kode Aset & No Registrasi berikutnya (tanpa mengubah counter)
        case 'next_kode':
            $resolved = resolve_kib_entry($_GET['kib'] ?? $_POST['kib'] ?? '', $kibConfigs);
            if ($resolved === null) {
                json_error('Kategori KIB tidak valid.');
            }

            $cfg       = $resolved['cfg'];
            $kibLetter = $cfg['letter'];
            $table     = $cfg['table'];

            $stmtReg = $pdo->prepare("SELECT last_no FROM registrasi_counter WHERE kib = ? LIMIT 1");
            $stmtReg->execute([$kibLetter]);
            $lastReg = $stmtReg->fetchColumn();
            if ($lastReg === false) {
                $lastReg = (int) $pdo->query("SELECT COALESCE(MAX(CAST(no_reg AS UNSIGNED)), 0) FROM {$table} WHERE no_reg REGEXP '^[0-9]+$'")->fetchColumn();
            }
            $noRegistrasi = format_no_registrasi(((int) $lastReg) + 1);

            $rawTahun = trim((string) ($_GET['tahun'] ?? $_POST['tahun'] ?? ''));
            if ($rawTahun === '') {
                json_success('Pratinjau no registrasi berhasil diambil.', [
                    'kode_aset'     => '',
                    'no_registrasi' => $noRegistrasi,
                ]);
            }

            [$validYear, $tahunInt, $yearErr] = validate_tahun_perolehan($rawTahun);
            if (!$validYear) {
                json_error($yearErr, [
                    'kode_aset'     => '',
                    'no_registrasi' => $noRegistrasi,
                ], 422);
            }

            $stmtKode = $pdo->prepare("SELECT COALESCE(MAX(last_no), 0) FROM kode_counter WHERE kib = ?");
            $stmtKode->execute([$kibLetter]);
            $lastKodeCounter = (int) $stmtKode->fetchColumn();

            $maxKodeTable = (int) $pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(kode, '/', -1) AS UNSIGNED)), 0) FROM {$table} WHERE kode REGEXP '^KIB-[A-F]/[0-9]{4}/[0-9]+$'")->fetchColumn();
            $nextKodeNo = max($lastKodeCounter, $maxKodeTable) + 1;
            $kodeAset = format_kode_aset($kibLetter, $tahunInt, $nextKodeNo);

            json_success('Pratinjau kode aset berhasil diambil.', [
                'kode_aset'     => $kodeAset,
                'no_registrasi' => $noRegistrasi,
            ]);
            break;

        // Unduh file lampiran yang tersimpan di server
        case 'download':
            $resolved = resolve_kib_entry($_GET['kib'] ?? '', $kibConfigs);
            $rawFile  = basename((string) ($_GET['file'] ?? ''));

            if ($resolved === null || $rawFile === '' || $rawFile === '.' || $rawFile === '..') {
                header('Content-Type: application/json; charset=utf-8');
                json_error('Permintaan file lampiran tidak valid.', [], 400);
            }

            $filePath = get_kib_upload_dir($resolved['id']) . '/' . $rawFile;
            if (!is_file($filePath) || !is_readable($filePath)) {
                header('Content-Type: application/json; charset=utf-8');
                json_error("File fisik \"{$rawFile}\" belum tersedia di server (data sampel atau belum diunggah).", [], 404);
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string) $finfo->file($filePath);
            $safeMime = in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true) ? $mime : 'application/octet-stream';
            $isInline = !empty($_GET['inline']) && $_GET['inline'] !== '0';
            $disposition = $isInline ? 'inline' : 'attachment';

            header('Content-Type: ' . $safeMime);
            header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', $rawFile) . '"');
            header('Content-Length: ' . filesize($filePath));
            header('X-Content-Type-Options: nosniff');
            if ($isInline) {
                header('X-Frame-Options: SAMEORIGIN');
                header('Cache-Control: private, max-age=300');
            }
            readfile($filePath);
            exit;

        // Simpan / Tambah / Perbarui satu aset
        case 'save':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_error('Metode request tidak diizinkan.', [], 405);
            }

            if (!verify_csrf_token()) {
                json_error('Token CSRF tidak valid. Silakan muat ulang halaman.', [], 403);
            }

            $resolved = resolve_kib_entry($_POST['kib'] ?? '', $kibConfigs);
            if ($resolved === null) {
                json_error('Kategori KIB tidak valid.');
            }

            $kibId     = $resolved['id'];
            $cfg       = $resolved['cfg'];
            $kibLetter = $cfg['letter'];
            $table     = $cfg['table'];
            $id        = (int) ($_POST['id'] ?? 0);

            $existingRow = null;
            if ($id > 0) {
                $stmtExisting = $pdo->prepare("SELECT * FROM {$table} WHERE id = ? LIMIT 1");
                $stmtExisting->execute([$id]);
                $existingRow = $stmtExisting->fetch(PDO::FETCH_ASSOC);
                if (!$existingRow) {
                    json_error('Data aset tidak ditemukan.', [], 404);
                }
            }

            // Kumpulkan kolom editable saja (abaikan kode dan no_reg dari client)
            $dataToSave = [];
            foreach ($cfg['fields'] as $f) {
                if ($f === 'kode' || $f === 'no_reg') {
                    continue;
                }
                if (isset($_POST[$f])) {
                    $dataToSave[$f] = trim((string) $_POST[$f]);
                }
            }

            // Validasi field wajib per KIB
            foreach ($cfg['required'] as $reqField => $reqLabel) {
                if (!isset($dataToSave[$reqField]) || $dataToSave[$reqField] === '') {
                    json_error("{$reqLabel} wajib diisi.", ['field' => $reqField], 422);
                }
            }

            // Validasi tahun perolehan / tanggal mulai
            [$validYear, $tahunInt, $yearErr] = extract_asset_year($kibId, $dataToSave);
            if (!$validYear) {
                $yearField = ($kibId === 'kib-f') ? 'tgl_mulai' : 'tahun';
                json_error($yearErr, ['field' => $yearField], 422);
            }

            // Normalisasi format nilai rupiah, jumlah, dan field turunan per KIB
            foreach (['harga', 'biaya', 'harga_satuan'] as $moneyField) {
                if (in_array($moneyField, $cfg['fields'], true) && isset($dataToSave[$moneyField])) {
                    $amount = parse_pure_integer_amount($dataToSave[$moneyField]);
                    if ($amount <= 0 && isset($cfg['required'][$moneyField])) {
                        json_error("{$cfg['required'][$moneyField]} harus berupa angka lebih dari 0.", ['field' => $moneyField], 422);
                    }
                    $dataToSave[$moneyField] = format_rupiah_amount($amount);
                }
            }

            if (in_array('jumlah', $cfg['fields'], true)) {
                $qty = parse_pure_integer_amount((string) ($dataToSave['jumlah'] ?? ''));
                if ($qty < 1) {
                    json_error('Jumlah minimal 1.', ['field' => 'jumlah'], 422);
                }
                $dataToSave['jumlah'] = (string) $qty;
            }

            if ($kibId === 'kib-e') {
                [$validPubYear, $pubYearInt, $pubYearErr] = validate_tahun_perolehan((string) ($dataToSave['spesifikasi_tahun'] ?? ''), 'Tahun Terbit');
                if (!$validPubYear) {
                    json_error($pubYearErr, ['field' => 'spesifikasi_tahun'], 422);
                }
                $qty = (int) $dataToSave['jumlah'];
                $unitPrice = parse_pure_integer_amount((string) ($_POST['harga_satuan'] ?? $dataToSave['harga_satuan']));
                $dataToSave['harga_total'] = format_rupiah_amount($qty * $unitPrice);
            }

            if ($kibId === 'kib-f') {
                $pctRaw = trim((string) ($dataToSave['persen_fisik'] ?? ''));
                $pctDigits = parse_pure_integer_amount($pctRaw);
                if ($pctRaw !== '' && !str_ends_with($pctRaw, '%')) {
                    $dataToSave['persen_fisik'] = $pctDigits . '%';
                }
                if (empty($dataToSave['status_pengerjaan'])) {
                    if ($pctDigits >= 100) {
                        $dataToSave['status_pengerjaan'] = 'Selesai';
                    } elseif ($pctDigits > 0) {
                        $dataToSave['status_pengerjaan'] = 'Dalam Pengerjaan';
                    } else {
                        $dataToSave['status_pengerjaan'] = 'Perencanaan';
                    }
                }
                if (!isset($dataToSave['luas']) || $dataToSave['luas'] === '') {
                    $dataToSave['luas'] = '-';
                }
            }

            // Tangani upload lampiran (PDF / JPG / PNG, otomatis kompres jika > 5MB)
            $uploadedNewFile = null;
            if (isset($_FILES['lampiran']) && (int) ($_FILES['lampiran']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                [$uploadOk, $savedFileName, $uploadErr] = handle_lampiran_upload($kibId, $_FILES['lampiran']);
                if (!$uploadOk) {
                    json_error($uploadErr, ['field' => 'lampiran'], 422);
                }
                $uploadedNewFile = $savedFileName;
                $dataToSave['lampiran'] = $savedFileName;
            } elseif ($existingRow !== null && (!isset($dataToSave['lampiran']) || $dataToSave['lampiran'] === '')) {
                $dataToSave['lampiran'] = (string) ($existingRow['lampiran'] ?? '');
            }

            $currentUser = current_user();
            $actor = $currentUser['nama_lengkap'] ?? $currentUser['username'] ?? 'Administrator';

            if ($id > 0) {
                if (in_array('jumlah', $cfg['fields'], true)) {
                    $newTotalQty = (int) ($dataToSave['jumlah'] ?? 1);
                    try {
                        $stmtMonTotal = $pdo->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM monitoring_aset WHERE kib = ? AND aset_id = ?");
                        $stmtMonTotal->execute([$kibId, $id]);
                        $usedInMonitoring = (int) $stmtMonTotal->fetchColumn();
                        if ($usedInMonitoring > $newTotalQty) {
                            json_error(
                                "Jumlah tidak boleh kurang dari {$usedInMonitoring} karena sudah tercatat pada Monitoring (total {$usedInMonitoring} unit).",
                                ['field' => 'jumlah'],
                                422
                            );
                        }
                    } catch (Throwable $monChkErr) {
                        // Abaikan jika tabel monitoring_aset belum tersedia
                    }
                }

                $pdo->beginTransaction();
                try {
                    $setParts = [];
                    $params   = [];
                    foreach ($dataToSave as $col => $val) {
                        $setParts[] = "{$col} = ?";
                        $params[]   = $val;
                    }
                    $params[] = $id;

                    $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . " WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);

                    // Sinkronkan nama_aset dan jenis_aset ke tabel monitoring_aset bila sudah ada
                    $jenisSync = match ($kibId) {
                        'kib-a' => 'Tanah',
                        'kib-c' => 'Gedung & Bangunan',
                        'kib-d' => 'Jalan, Irigasi & Jaringan',
                        default => (string) ($dataToSave['jenis'] ?? $existingRow['jenis'] ?? ''),
                    };
                    try {
                        $stmtSyncMon = $pdo->prepare("UPDATE monitoring_aset SET nama_aset = ?, jenis_aset = ? WHERE kib = ? AND aset_id = ?");
                        $stmtSyncMon->execute([$dataToSave['nama'], $jenisSync, $kibId, $id]);
                    } catch (Throwable $syncErr) {
                        // Abaikan jika tabel monitoring_aset belum tersedia sebelum migrasi dijalankan
                    }

                    $actTeks = "Data aset \"{$dataToSave['nama']}\" diperbarui di {$cfg['kode']}";
                    $stmtLog = $pdo->prepare("INSERT INTO kib_activities (teks, pelaku, waktu, warna) VALUES (?, ?, 'Baru saja', '#3b82f6')");
                    $stmtLog->execute([$actTeks, $actor]);

                    $pdo->commit();
                } catch (Throwable $txErr) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    if ($uploadedNewFile !== null) {
                        $newFilePath = get_kib_upload_dir($kibId) . '/' . basename($uploadedNewFile);
                        if (is_file($newFilePath)) {
                            @unlink($newFilePath);
                        }
                    }
                    throw $txErr;
                }

                if ($uploadedNewFile !== null && !empty($existingRow['lampiran']) && $existingRow['lampiran'] !== $uploadedNewFile) {
                    $oldFilePath = get_kib_upload_dir($kibId) . '/' . basename((string) $existingRow['lampiran']);
                    if (is_file($oldFilePath)) {
                        @unlink($oldFilePath);
                    }
                }

                $updatedItem = array_merge($existingRow, $dataToSave, ['id' => $id]);

                json_success("Data aset di {$cfg['kode']} berhasil diperbarui.", [
                    'id'   => $id,
                    'item' => $updatedItem,
                ]);
            } else {
                // INSERT: generate kode dan no_reg ulang di server dalam transaksi agar bebas duplikat
                $pdo->beginTransaction();
                try {
                    // Urutan lock konsisten (registrasi_counter lalu kode_counter) mencegah deadlock
                    $pdo->prepare("INSERT IGNORE INTO registrasi_counter (kib, last_no) VALUES (?, 0)")
                        ->execute([$kibLetter]);

                    $stmtLockReg = $pdo->prepare("SELECT last_no FROM registrasi_counter WHERE kib = ? FOR UPDATE");
                    $stmtLockReg->execute([$kibLetter]);
                    $nextRegNo = ((int) $stmtLockReg->fetchColumn()) + 1;

                    $pdo->prepare("UPDATE registrasi_counter SET last_no = ? WHERE kib = ?")
                        ->execute([$nextRegNo, $kibLetter]);

                    $pdo->prepare("INSERT IGNORE INTO kode_counter (kib, tahun, last_no) VALUES (?, 0, 0)")
                        ->execute([$kibLetter]);

                    $stmtLockKode = $pdo->prepare("SELECT last_no FROM kode_counter WHERE kib = ? AND tahun = 0 FOR UPDATE");
                    $stmtLockKode->execute([$kibLetter]);
                    $globalLastKode = (int) $stmtLockKode->fetchColumn();

                    $stmtMaxAnyKode = $pdo->prepare("SELECT COALESCE(MAX(last_no), 0) FROM kode_counter WHERE kib = ?");
                    $stmtMaxAnyKode->execute([$kibLetter]);
                    $maxCounterKode = (int) $stmtMaxAnyKode->fetchColumn();

                    $maxTableKode = (int) $pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(kode, '/', -1) AS UNSIGNED)), 0) FROM {$table} WHERE kode REGEXP '^KIB-[A-F]/[0-9]{4}/[0-9]+$'")->fetchColumn();
                    $nextKodeNo = max($globalLastKode, $maxCounterKode, $maxTableKode) + 1;

                    $pdo->prepare("UPDATE kode_counter SET last_no = ? WHERE kib = ? AND tahun = 0")
                        ->execute([$nextKodeNo, $kibLetter]);

                    $dataToSave['kode']   = format_kode_aset($kibLetter, $tahunInt, $nextKodeNo);
                    $dataToSave['no_reg'] = format_no_registrasi($nextRegNo);

                    $cols   = array_keys($dataToSave);
                    $params = array_values($dataToSave);
                    $marks  = array_fill(0, count($cols), '?');

                    $sql = "INSERT INTO {$table} (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $marks) . ")";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);

                    $newId = (int) $pdo->lastInsertId();

                    $actTeks = "Aset baru \"{$dataToSave['nama']}\" ({$dataToSave['kode']}) ditambahkan ke {$cfg['kode']}";
                    $stmtLog = $pdo->prepare("INSERT INTO kib_activities (teks, pelaku, waktu, warna) VALUES (?, ?, 'Baru saja', '#22c55e')");
                    $stmtLog->execute([$actTeks, $actor]);

                    $pdo->commit();
                } catch (Throwable $txErr) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    if ($uploadedNewFile !== null) {
                        $newFilePath = get_kib_upload_dir($kibId) . '/' . basename($uploadedNewFile);
                        if (is_file($newFilePath)) {
                            @unlink($newFilePath);
                        }
                    }
                    throw $txErr;
                }

                json_success("Aset baru berhasil ditambahkan ke database {$cfg['kode']}.", [
                    'id'   => $newId,
                    'item' => array_merge(['id' => $newId], $dataToSave),
                ]);
            }
            break;

        // Hapus aset dari database
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_error('Metode request tidak diizinkan.', [], 405);
            }

            if (!verify_csrf_token()) {
                json_error('Token CSRF tidak valid. Silakan muat ulang halaman.', [], 403);
            }

            $kibId = $_POST['kib'] ?? '';
            $id    = (int) ($_POST['id'] ?? 0);

            if (!isset($kibConfigs[$kibId]) || $id <= 0) {
                json_error('Parameter KIB atau ID tidak valid.');
            }

            $cfg   = $kibConfigs[$kibId];
            $table = $cfg['table'];

            // Ambil nama & lampiran aset sebelum dihapus untuk log dan pembersihan file
            $stmtItem = $pdo->prepare("SELECT nama, lampiran FROM {$table} WHERE id = ? LIMIT 1");
            $stmtItem->execute([$id]);
            $item = $stmtItem->fetch();
            $namaAset = $item['nama'] ?? 'Aset';

            $stmtDel = $pdo->prepare("DELETE FROM {$table} WHERE id = ?");
            $stmtDel->execute([$id]);

            try {
                $stmtDelMon = $pdo->prepare("DELETE FROM monitoring_aset WHERE kib = ? AND aset_id = ?");
                $stmtDelMon->execute([$kibId, $id]);
            } catch (Throwable $delMonErr) {
                // Abaikan jika tabel monitoring_aset belum tersedia
            }

            if (!empty($item['lampiran'])) {
                $fileToDel = get_kib_upload_dir($kibId) . '/' . basename((string) $item['lampiran']);
                if (is_file($fileToDel)) {
                    @unlink($fileToDel);
                }
            }

            $currentUser = current_user();
            $actor = $currentUser['nama_lengkap'] ?? $currentUser['username'] ?? 'Administrator';

            // Log aktivitas penghapusan
            $actTeks = "Aset \"{$namaAset}\" dihapus dari {$cfg['kode']}";
            $stmtLog = $pdo->prepare("INSERT INTO kib_activities (teks, pelaku, waktu, warna) VALUES (?, ?, 'Baru saja', '#ef4444')");
            $stmtLog->execute([$actTeks, $actor]);

            json_success("Aset \"{$namaAset}\" berhasil dihapus dari database.", [
                'id'  => $id,
                'kib' => $kibId,
            ]);
            break;

        default:
            json_error('Aksi KIB tidak dikenali.', [], 400);
            break;
    }
} catch (Throwable $e) {
    error_log('AJAX KIB Error: ' . $e->getMessage());
    json_error('Terjadi kesalahan pada database KIB: ' . $e->getMessage(), [], 500);
}
