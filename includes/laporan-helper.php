<?php
/**
 * Helper Query & Generator PDF Laporan Inventaris Aset
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

declare(strict_types=1);

/**
 * Daftar opsi jenis laporan (mudah ditambah bila ada jenis laporan baru)
 */
function laporan_jenis_options(): array
{
    return [
        ''          => 'Semua Jenis Laporan',
        'data_aset' => 'Laporan Data Aset',
    ];
}

/**
 * Daftar opsi KIB A - F
 */
function laporan_kib_options(): array
{
    return [
        ''      => 'Semua KIB',
        'kib-a' => 'KIB A (Tanah)',
        'kib-b' => 'KIB B (Peralatan & Mesin)',
        'kib-c' => 'KIB C (Gedung & Bangunan)',
        'kib-d' => 'KIB D (Jalan, Irigasi & Jaringan)',
        'kib-e' => 'KIB E (Aset Tetap Lainnya)',
        'kib-f' => 'KIB F (Konstruksi Dalam Pengerjaan)',
    ];
}

/**
 * Daftar opsi kondisi aset
 */
function laporan_kondisi_options(): array
{
    return [
        ''             => 'Semua Kondisi',
        'Baik'         => 'Baik',
        'Rusak Ringan' => 'Rusak Ringan',
        'Rusak Berat'  => 'Rusak Berat',
        'Hilang'       => 'Hilang',
    ];
}

/**
 * Memetakan kode penempatan database ke label lokasi yang mudah dibaca
 */
function laporan_format_lokasi(string $raw): string
{
    $val = strtolower(trim($raw));
    if ($val === '' || $val === 'umum' || $val === 'aset-umum') {
        return 'Aset Umum';
    }
    if (preg_match('/^kelas-(\d+)$/', $val, $m)) {
        return 'Kelas ' . $m[1];
    }
    return $raw;
}

/**
 * Mengambil daftar lokasi unik dari database (penempatan KIB & monitoring_aset)
 */
function laporan_get_lokasi_options(PDO $pdo): array
{
    $options = [
        ''        => 'Semua Lokasi',
        'kelas-1' => 'Kelas 1',
        'kelas-2' => 'Kelas 2',
        'kelas-3' => 'Kelas 3',
        'kelas-4' => 'Kelas 4',
        'kelas-5' => 'Kelas 5',
        'kelas-6' => 'Kelas 6',
        'umum'    => 'Aset Umum',
    ];

    try {
        $stmt = $pdo->query("SELECT DISTINCT penempatan FROM monitoring_aset WHERE penempatan IS NOT NULL AND penempatan <> ''");
        if ($stmt) {
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $lok) {
                $key = strtolower(trim((string) $lok));
                if ($key !== '' && !isset($options[$key])) {
                    $options[$key] = laporan_format_lokasi((string) $lok);
                }
            }
        }
    } catch (Throwable $e) {
        // Gunakan daftar default jika tabel belum tersedia
    }

    return $options;
}

/**
 * Mengambil daftar status aset/pemeliharaan dari database
 */
function laporan_get_status_options(PDO $pdo): array
{
    $options = [
        ''                        => 'Semua Status',
        'Perlu pemeliharaan'      => 'Perlu pemeliharaan',
        'Sedang ditindak lanjut'  => 'Sedang ditindak lanjut',
        'Selesai ditindak lanjut' => 'Selesai ditindak lanjut',
        'Digunakan kembali'       => 'Digunakan kembali',
        'Disimpan'                => 'Disimpan',
    ];

    try {
        $stmt = $pdo->query("SELECT DISTINCT status FROM pemeliharaan WHERE status IS NOT NULL AND status <> '' ORDER BY status ASC");
        if ($stmt) {
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $st) {
                $val = trim((string) $st);
                if ($val !== '' && !isset($options[$val])) {
                    $options[$val] = $val;
                }
            }
        }
    } catch (Throwable $e) {
        // Fallback ke daftar status bawaan
    }

    return $options;
}

/**
 * Format tanggal bahasa Indonesia: Hari, dd/mm/yy (misal: Kamis, 26/11/26)
 */
function laporan_format_titimangsa_tanggal(?string $ymd = null): string
{
    $hariList = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $ts = ($ymd !== null && $ymd !== '' && strtotime($ymd) !== false) ? strtotime($ymd) : time();
    $hari = $hariList[(int) date('w', $ts)] ?? 'Senin';
    return $hari . ', ' . date('d/m/y', $ts);
}

/**
 * Validasi input filter laporan beserta whitelist nilai yang diizinkan
 *
 * @return array{ok: bool, message: string, data: array}
 */
function laporan_parse_filters(array $input, ?PDO $pdo = null): array
{
    $jenisAllowed   = array_keys(laporan_jenis_options());
    $kibAllowed     = array_keys(laporan_kib_options());
    $kondisiAllowed = array_keys(laporan_kondisi_options());

    $jenisLaporan = trim((string) ($input['jenis_laporan'] ?? ''));
    if (!in_array($jenisLaporan, $jenisAllowed, true)) {
        return ['ok' => false, 'message' => 'Jenis laporan tidak valid.', 'data' => []];
    }

    $namaInstansi = trim((string) ($input['nama_instansi'] ?? ''));
    if ($namaInstansi === '') {
        $namaInstansi = 'SDN BUDIKARYA';
    }

    $tanggalPembuatan = trim((string) ($input['tanggal_pembuatan'] ?? ''));
    if ($tanggalPembuatan === '') {
        $tanggalPembuatan = laporan_format_titimangsa_tanggal();
    }

    $periodeAwal  = trim((string) ($input['periode_awal'] ?? ''));
    $periodeAkhir = trim((string) ($input['periode_akhir'] ?? ''));

    if ($periodeAwal !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodeAwal)) {
        return ['ok' => false, 'message' => 'Format Periode Awal tidak valid.', 'data' => []];
    }
    if ($periodeAkhir !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodeAkhir)) {
        return ['ok' => false, 'message' => 'Format Periode Akhir tidak valid.', 'data' => []];
    }
    if ($periodeAwal !== '' && $periodeAkhir !== '' && $periodeAkhir < $periodeAwal) {
        return ['ok' => false, 'message' => 'Periode Akhir tidak boleh lebih awal dari Periode Awal.', 'data' => []];
    }

    $kib = strtolower(trim((string) ($input['kib'] ?? '')));
    if (!in_array($kib, $kibAllowed, true)) {
        return ['ok' => false, 'message' => 'Pilihan KIB tidak valid.', 'data' => []];
    }

    $lokasi = strtolower(trim((string) ($input['lokasi'] ?? '')));
    if ($lokasi !== '' && $pdo !== null) {
        $lokasiAllowed = array_keys(laporan_get_lokasi_options($pdo));
        if (!in_array($lokasi, $lokasiAllowed, true)) {
            return ['ok' => false, 'message' => 'Pilihan Lokasi tidak valid.', 'data' => []];
        }
    }

    $kondisi = trim((string) ($input['kondisi'] ?? ''));
    if (!in_array($kondisi, $kondisiAllowed, true)) {
        return ['ok' => false, 'message' => 'Pilihan Kondisi tidak valid.', 'data' => []];
    }

    $status = trim((string) ($input['status'] ?? ''));
    if ($status !== '' && $pdo !== null) {
        $statusAllowed = array_keys(laporan_get_status_options($pdo));
        if (!in_array($status, $statusAllowed, true)) {
            return ['ok' => false, 'message' => 'Pilihan Status tidak valid.', 'data' => []];
        }
    }

    $penanggungJawab = trim((string) ($_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'Pengurus Barang Sekolah'));

    return [
        'ok'      => true,
        'message' => '',
        'data'    => [
            'jenis_laporan'     => $jenisLaporan,
            'nama_instansi'     => $namaInstansi,
            'tanggal_pembuatan' => $tanggalPembuatan,
            'periode_awal'      => $periodeAwal,
            'periode_akhir'     => $periodeAkhir,
            'kib'               => $kib,
            'lokasi'            => $lokasi,
            'kondisi'           => $kondisi,
            'status'            => $status,
            'penanggung_jawab'  => $penanggungJawab,
        ],
    ];
}

/**
 * Mengambil data aset terpadu dari tabel KIB A-F beserta kondisi/status terbaru menggunakan prepared statement
 */
function laporan_fetch_rows(PDO $pdo, array $filters): array
{
    $kibMap = [
        'kib-a' => ['table' => 'kib_a', 'label' => 'KIB A', 'has_jumlah' => false],
        'kib-b' => ['table' => 'kib_b', 'label' => 'KIB B', 'has_jumlah' => true],
        'kib-c' => ['table' => 'kib_c', 'label' => 'KIB C', 'has_jumlah' => false],
        'kib-d' => ['table' => 'kib_d', 'label' => 'KIB D', 'has_jumlah' => false],
        'kib-e' => ['table' => 'kib_e', 'label' => 'KIB E', 'has_jumlah' => true],
        'kib-f' => ['table' => 'kib_f', 'label' => 'KIB F', 'has_jumlah' => false],
    ];

    $selectedKibs = ($filters['kib'] !== '' && isset($kibMap[$filters['kib']]))
        ? [$filters['kib'] => $kibMap[$filters['kib']]]
        : $kibMap;

    $rows = [];

    foreach ($selectedKibs as $kibKey => $meta) {
        $table      = $meta['table'];
        $jumlahExpr = $meta['has_jumlah'] ? "COALESCE(NULLIF(k.jumlah, ''), '1')" : "'1'";

        $sql = "
            SELECT
                :kib_label AS kib_label,
                k.id AS aset_id,
                k.kode,
                k.nama,
                k.penempatan,
                {$jumlahExpr} AS jumlah,
                DATE(k.created_at) AS tgl_catat,
                COALESCE(
                    (SELECT m.kondisi FROM monitoring_aset m
                     WHERE m.kib = :kib_m AND m.aset_id = k.id
                     ORDER BY m.tgl_update DESC, m.id DESC LIMIT 1),
                    'Baik'
                ) AS kondisi,
                COALESCE(
                    (SELECT p.status FROM pemeliharaan p
                     WHERE p.kib = :kib_p AND p.aset_id = k.id
                     ORDER BY p.tanggal_update DESC, p.id DESC LIMIT 1),
                    'Tercatat'
                ) AS status_aset
            FROM {$table} k
            WHERE 1=1
        ";

        $params = [
            ':kib_label' => $meta['label'],
            ':kib_m'     => $kibKey,
            ':kib_p'     => $kibKey,
        ];

        if ($filters['lokasi'] !== '') {
            $sql .= " AND k.penempatan = :lokasi";
            $params[':lokasi'] = $filters['lokasi'];
        }
        if ($filters['periode_awal'] !== '') {
            $sql .= " AND DATE(k.created_at) >= :periode_awal";
            $params[':periode_awal'] = $filters['periode_awal'];
        }
        if ($filters['periode_akhir'] !== '') {
            $sql .= " AND DATE(k.created_at) <= :periode_akhir";
            $params[':periode_akhir'] = $filters['periode_akhir'];
        }

        $sql .= " ORDER BY k.id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $fetched = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($fetched as $item) {
            if ($filters['kondisi'] !== '' && strcasecmp((string) $item['kondisi'], $filters['kondisi']) !== 0) {
                continue;
            }
            if ($filters['status'] !== '' && strcasecmp((string) $item['status_aset'], $filters['status']) !== 0) {
                continue;
            }
            $rows[] = $item;
        }
    }

    return $rows;
}

/**
 * Menghitung ringkasan jumlah kondisi aset dari data hasil filter
 */
function laporan_compute_summary(array $rows): array
{
    $summary = [
        'total'        => count($rows),
        'baik'         => 0,
        'rusak_ringan' => 0,
        'rusak_berat'  => 0,
    ];

    foreach ($rows as $r) {
        $kondisi = strtolower(trim((string) ($r['kondisi'] ?? 'Baik')));
        if ($kondisi === 'baik') {
            $summary['baik']++;
        } elseif ($kondisi === 'rusak ringan') {
            $summary['rusak_ringan']++;
        } elseif ($kondisi === 'rusak berat') {
            $summary['rusak_berat']++;
        }
    }

    return $summary;
}

/**
 * Merender template HTML laporan PDF dari templates/laporan_aset_pdf.php
 */
function laporan_build_html(array $filters, array $rows, array $summary): string
{
    $templatePath = dirname(__DIR__) . '/templates/laporan_aset_pdf.php';
    ob_start();
    include $templatePath;
    return (string) ob_get_clean();
}

/**
 * Generator PDF minimal (PDF 1.4) tanpa dependensi eksternal sebagai fallback bila Dompdf belum terpasang.
 * Tata letak menyamai templates/laporan_aset_pdf.php.
 */
function laporan_generate_native_pdf(array $filters, array $rows, array $summary): string
{
    $jenisMap = laporan_jenis_options();
    $kibMap   = laporan_kib_options();

    $judul = ($filters['jenis_laporan'] !== '' && isset($jenisMap[$filters['jenis_laporan']]))
        ? strtoupper($jenisMap[$filters['jenis_laporan']])
        : 'LAPORAN DATA ASET';

    $namaJenisTeks = ($filters['jenis_laporan'] !== '' && isset($jenisMap[$filters['jenis_laporan']]))
        ? strtolower($jenisMap[$filters['jenis_laporan']])
        : 'data aset';

    if ($filters['periode_awal'] !== '' && $filters['periode_akhir'] !== '') {
        $periodeTeks = date('d/m/y', strtotime($filters['periode_awal'])) . ' sampai ' . date('d/m/y', strtotime($filters['periode_akhir']));
    } elseif ($filters['periode_awal'] !== '') {
        $periodeTeks = date('d/m/y', strtotime($filters['periode_awal'])) . ' sampai sekarang';
    } elseif ($filters['periode_akhir'] !== '') {
        $periodeTeks = 'awal sampai ' . date('d/m/y', strtotime($filters['periode_akhir']));
    } else {
        $periodeTeks = 'semua periode';
    }

    $labelKib = ($filters['kib'] !== '' && isset($kibMap[$filters['kib']])) ? $kibMap[$filters['kib']] : '';
    $judulTabel = $labelKib !== '' ? "Tabel data {$labelKib}" : "Tabel data {$namaJenisTeks}";

    $instansi = (string) $filters['nama_instansi'];
    $tanggal  = (string) $filters['tanggal_pembuatan'];
    $pj       = (string) ($filters['penanggung_jawab'] ?? 'Pengurus Barang Sekolah');

    $escapePdf = static function (string $text): string {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($ascii === false) {
            $ascii = preg_replace('/[^\x20-\x7E]/', ' ', $text) ?? '';
        }
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $ascii);
    };

    $truncate = static function (string $text, int $max): string {
        $clean = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if (strlen($clean) <= $max) {
            return $clean;
        }
        return substr($clean, 0, max(1, $max - 3)) . '...';
    };

    $pageWidth  = 595;
    $pageHeight = 842;
    $marginX    = 42;
    $tableWidth = $pageWidth - ($marginX * 2);

    $firstPageLimit = 18;
    $nextPageLimit  = 26;
    $rowChunks      = [];
    if (empty($rows)) {
        $rowChunks[] = [];
    } else {
        $rowChunks[] = array_slice($rows, 0, $firstPageLimit);
        $offset = $firstPageLimit;
        while ($offset < count($rows)) {
            $rowChunks[] = array_slice($rows, $offset, $nextPageLimit);
            $offset += $nextPageLimit;
        }
    }

    $totalPages   = count($rowChunks);
    $pagesStreams = [];
    $runningNo    = 1;

    foreach ($rowChunks as $chunkIndex => $chunk) {
        $stream = '';
        $y = $pageHeight - 48;

        if ($chunkIndex === 0) {
            // 1. Judul tengah + garis ganda
            $titleWidthEst = strlen($judul) * 7.2;
            $titleX = max($marginX, ($pageWidth - $titleWidthEst) / 2);
            $stream .= sprintf("BT /F2 14 Tf %.2f %.2f Td (%s) Tj ET\n", $titleX, $y, $escapePdf($judul));
            $y -= 10;

            $stream .= sprintf("0.9 w %d %.2f m %d %.2f l S\n", $marginX, $y, $pageWidth - $marginX, $y);
            $stream .= sprintf("0.9 w %d %.2f m %d %.2f l S\n", $marginX, $y - 3, $pageWidth - $marginX, $y - 3);
            $y -= 18;

            // 2. Identitas rata kiri
            $stream .= sprintf("BT /F2 9.5 Tf %d %.2f Td (%s) Tj ET\n", $marginX, $y, $escapePdf($instansi));
            $y -= 13;
            $stream .= sprintf("BT /F1 9.5 Tf %d %.2f Td (%s) Tj ET\n", $marginX, $y, $escapePdf($tanggal));
            $y -= 16;

            // 3. Teks ringkasan
            $summaryText = "Ringkasan data {$namaJenisTeks} periode {$periodeTeks}.";
            $stream .= sprintf("BT /F1 9.5 Tf %d %.2f Td (%s) Tj ET\n", $marginX, $y, $escapePdf($summaryText));
            $y -= 14;

            // 4. 4 Kotak ringkasan sejajar
            $boxGap = 8;
            $boxW   = ($tableWidth - ($boxGap * 3)) / 4;
            $boxH   = 44;
            $stats  = [
                [(string) ($summary['total'] ?? 0), 'TOTAL ASET'],
                [(string) ($summary['baik'] ?? 0), 'KONDISI BAIK'],
                [(string) ($summary['rusak_ringan'] ?? 0), 'RUSAK RINGAN'],
                [(string) ($summary['rusak_berat'] ?? 0), 'RUSAK BERAT'],
            ];

            foreach ($stats as $si => $st) {
                $bx = $marginX + ($si * ($boxW + $boxGap));
                $stream .= sprintf("0.6 w 0.6 G %d %.2f %.2f %d re S 0 G\n", (int) $bx, $y - $boxH, $boxW, $boxH);
                $valX = $bx + ($boxW / 2) - (strlen($st[0]) * 4.2);
                $lblX = $bx + ($boxW / 2) - (strlen($st[1]) * 2.2);
                $stream .= sprintf("BT /F2 15 Tf %.2f %.2f Td (%s) Tj ET\n", $valX, $y - 21, $escapePdf($st[0]));
                $stream .= sprintf("BT /F2 7 Tf %.2f %.2f Td (%s) Tj ET\n", $lblX, $y - 36, $escapePdf($st[1]));
            }
            $y -= ($boxH + 18);

            // 5. Judul tabel
            $stream .= sprintf("BT /F2 10 Tf %d %.2f Td (%s) Tj ET\n", $marginX, $y, $escapePdf($judulTabel));
            $y -= 10;
        }

        // 6. Tabel 6 Kolom (#f3ecec)
        $cols = [
            ['label' => 'No',          'w' => 28,  'align' => 'left'],
            ['label' => 'Kode Barang', 'w' => 90,  'align' => 'center'],
            ['label' => 'Nama Barang', 'w' => 168, 'align' => 'center'],
            ['label' => 'Lokasi',      'w' => 80,  'align' => 'center'],
            ['label' => 'Jumlah',      'w' => 60,  'align' => 'center'],
            ['label' => 'Kondisi',     'w' => 85,  'align' => 'center'],
        ];
        $rowH = 22;

        $stream .= sprintf("0.953 0.925 0.925 rg %d %.2f %d %d re f 0 g\n", $marginX, $y - $rowH, $tableWidth, $rowH);
        $stream .= sprintf("0.5 w 0.6 G %d %.2f %d %d re S 0 G\n", $marginX, $y - $rowH, $tableWidth, $rowH);
        $cx = $marginX;
        foreach ($cols as $c) {
            $txtW = strlen($c['label']) * 4.5;
            $tx = ($c['align'] === 'left') ? ($cx + 6) : ($cx + ($c['w'] - $txtW) / 2);
            $stream .= sprintf("BT /F2 8.5 Tf %.2f %.2f Td (%s) Tj ET\n", $tx, $y - 14, $escapePdf($c['label']));
            $cx += $c['w'];
            if ($cx < $marginX + $tableWidth) {
                $stream .= sprintf("0.6 G %.2f %.2f m %.2f %.2f l S 0 G\n", $cx, $y, $cx, $y - $rowH);
            }
        }
        $y -= $rowH;

        if (empty($chunk)) {
            $stream .= sprintf("0.953 0.925 0.925 rg %d %.2f %d %d re f 0 g\n", $marginX, $y - $rowH, $tableWidth, $rowH);
            $stream .= sprintf("0.5 w 0.6 G %d %.2f %d %d re S 0 G\n", $marginX, $y - $rowH, $tableWidth, $rowH);
            $emptyMsg = 'Tidak ada data';
            $tx = $marginX + ($tableWidth - strlen($emptyMsg) * 4.2) / 2;
            $stream .= sprintf("BT /F1 8.5 Tf %.2f %.2f Td (%s) Tj ET\n", $tx, $y - 14, $escapePdf($emptyMsg));
            $y -= $rowH;
        } else {
            foreach ($chunk as $r) {
                $vals = [
                    $runningNo++ . '.',
                    $truncate((string) ($r['kode'] ?? '-'), 16),
                    $truncate((string) ($r['nama'] ?? '-'), 30),
                    $truncate(laporan_format_lokasi((string) ($r['penempatan'] ?? 'umum')), 14),
                    $truncate((string) ($r['jumlah'] ?? '1'), 10),
                    $truncate((string) ($r['kondisi'] ?? 'Baik'), 14),
                ];

                $stream .= sprintf("0.953 0.925 0.925 rg %d %.2f %d %d re f 0 g\n", $marginX, $y - $rowH, $tableWidth, $rowH);
                $stream .= sprintf("0.5 w 0.6 G %d %.2f %d %d re S 0 G\n", $marginX, $y - $rowH, $tableWidth, $rowH);

                $cx = $marginX;
                foreach ($cols as $ci => $c) {
                    $txtW = strlen($vals[$ci]) * 4.1;
                    $tx = ($c['align'] === 'left') ? ($cx + 6) : ($cx + ($c['w'] - $txtW) / 2);
                    $stream .= sprintf("BT /F1 8.5 Tf %.2f %.2f Td (%s) Tj ET\n", $tx, $y - 14, $escapePdf($vals[$ci]));
                    $cx += $c['w'];
                    if ($cx < $marginX + $tableWidth) {
                        $stream .= sprintf("0.6 G %.2f %.2f m %.2f %.2f l S 0 G\n", $cx, $y, $cx, $y - $rowH);
                    }
                }
                $y -= $rowH;
            }
        }

        // 8. Titimangsa di halaman terakhir
        if ($chunkIndex === $totalPages - 1) {
            $y -= 24;
            if ($y < 95) {
                $y = 95;
            }
            $tX = $pageWidth - $marginX - 185;
            $stream .= sprintf("BT /F1 9 Tf %.2f %.2f Td (%s) Tj ET\n", $tX, $y, $escapePdf($instansi . ', ' . $tanggal));
            $stream .= sprintf("BT /F1 9 Tf %.2f %.2f Td (%s) Tj ET\n", $tX, $y - 13, $escapePdf('Penanggung Jawab Aset'));
            $stream .= sprintf("BT /F2 9 Tf %.2f %.2f Td (%s) Tj ET\n", $tX, $y - 52, $escapePdf($pj));
        }

        // 7. Footer Halaman X dari Y
        $footerTxt = 'Halaman ' . ($chunkIndex + 1) . ' dari ' . $totalPages;
        $footerX   = $pageWidth - $marginX - (strlen($footerTxt) * 4.2);
        $stream .= sprintf("BT /F1 8 Tf %.2f 26 Td (%s) Tj ET\n", $footerX, $escapePdf($footerTxt));

        $pagesStreams[] = $stream;
    }

    // Susun objek PDF 1.4
    $objects = [];
    $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objects[3] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $objects[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";

    $pageObjIds = [];
    $nextId = 5;
    foreach ($pagesStreams as $stream) {
        $contentId = $nextId++;
        $pageId    = $nextId++;
        $objects[$contentId] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
        $objects[$pageId]    = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents {$contentId} 0 R >>";
        $pageObjIds[] = $pageId . ' 0 R';
    }

    $objects[2] = "<< /Type /Pages /Kids [" . implode(' ', $pageObjIds) . "] /Count " . count($pageObjIds) . " >>";
    ksort($objects);

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $id => $body) {
        $offsets[$id] = strlen($pdf);
        $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
    }

    $xrefPos = strlen($pdf);
    $totalObj = count($objects) + 1;
    $pdf .= "xref\n0 {$totalObj}\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i < $totalObj; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size {$totalObj} /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";

    return $pdf;
}

/**
 * Menghasilkan binary PDF (Dompdf jika tersedia, atau fallback generator PDF native)
 */
function laporan_render_pdf_bytes(array $filters, array $rows): string
{
    $summary  = laporan_compute_summary($rows);
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    }

    if (class_exists(\Dompdf\Dompdf::class)) {
        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
        $dompdf->loadHtml(laporan_build_html($filters, $rows, $summary), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    return laporan_generate_native_pdf($filters, $rows, $summary);
}
