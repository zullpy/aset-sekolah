<?php
/**
 * Template HTML Laporan PDF Data Aset
 * Digunakan bersama oleh fitur Pratinjau dan Ekspor Laporan agar hasil identik.
 *
 * Variabel yang diharapkan:
 * @var array $filters  Data filter yang sudah divalidasi
 * @var array $rows     Baris data aset hasil query
 * @var array $summary  Ringkasan jumlah aset (total, baik, rusak_ringan, rusak_berat)
 */

$jenisMap = laporan_jenis_options();
$kibMap   = laporan_kib_options();

// Judul laporan dinamis sesuai jenis laporan yang dipilih
$judulLaporan = ($filters['jenis_laporan'] !== '' && isset($jenisMap[$filters['jenis_laporan']]))
    ? strtoupper($jenisMap[$filters['jenis_laporan']])
    : 'LAPORAN DATA ASET';

$namaJenisTeks = ($filters['jenis_laporan'] !== '' && isset($jenisMap[$filters['jenis_laporan']]))
    ? strtolower($jenisMap[$filters['jenis_laporan']])
    : 'data aset';

$labelKib = ($filters['kib'] !== '' && isset($kibMap[$filters['kib']]))
    ? $kibMap[$filters['kib']]
    : 'Semua KIB';

// Format periode untuk teks ringkasan
if ($filters['periode_awal'] !== '' && $filters['periode_akhir'] !== '') {
    $periodeTeks = date('d/m/y', strtotime($filters['periode_awal']))
        . ' sampai '
        . date('d/m/y', strtotime($filters['periode_akhir']));
} elseif ($filters['periode_awal'] !== '') {
    $periodeTeks = date('d/m/y', strtotime($filters['periode_awal'])) . ' sampai sekarang';
} elseif ($filters['periode_akhir'] !== '') {
    $periodeTeks = 'awal sampai ' . date('d/m/y', strtotime($filters['periode_akhir']));
} else {
    $periodeTeks = 'semua periode';
}

$instansiEsc   = htmlspecialchars((string) $filters['nama_instansi'], ENT_QUOTES, 'UTF-8');
$tanggalEsc    = htmlspecialchars((string) $filters['tanggal_pembuatan'], ENT_QUOTES, 'UTF-8');
$judulEsc      = htmlspecialchars($judulLaporan, ENT_QUOTES, 'UTF-8');
$ringkasanEsc  = htmlspecialchars("Ringkasan data {$namaJenisTeks} periode {$periodeTeks}.", ENT_QUOTES, 'UTF-8');
$judulTabelEsc = htmlspecialchars(
    $filters['kib'] !== '' ? "Tabel data {$labelKib}" : "Tabel data {$namaJenisTeks}",
    ENT_QUOTES,
    'UTF-8'
);

$totalAset   = (int) ($summary['total'] ?? 0);
$baikCount   = (int) ($summary['baik'] ?? 0);
$ringanCount = (int) ($summary['rusak_ringan'] ?? 0);
$beratCount  = (int) ($summary['rusak_berat'] ?? 0);
$penanggungJawab = htmlspecialchars((string) ($filters['penanggung_jawab'] ?? 'Pengurus Barang Sekolah'), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?php echo $judulEsc; ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 18mm 16mm 20mm 16mm;
        }

        body {
            font-family: 'Inter', DejaVu Sans, Arial, sans-serif;
            font-size: 10.5px;
            color: #111827;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* 1. Kop Judul Laporan + Garis Ganda */
        .pdf-header {
            text-align: center;
            padding-bottom: 8px;
            border-bottom: 3.5px double #111827;
            margin-bottom: 14px;
        }

        .pdf-header-title {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: #111827;
        }

        /* 2. Identitas Rata Kiri */
        .pdf-identity {
            text-align: left;
            margin-bottom: 10px;
            line-height: 1.5;
            font-size: 10.5px;
        }

        .pdf-identity-instansi {
            font-weight: 700;
            color: #111827;
        }

        .pdf-identity-tanggal {
            color: #374151;
        }

        /* 3. Teks Ringkasan */
        .pdf-summary-text {
            margin: 0 0 12px;
            font-size: 10.5px;
            color: #1f2937;
        }

        /* 4. 4 Kotak Ringkasan Sejajar Satu Baris */
        .pdf-stats-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin: 0 -8px 16px -8px;
        }

        .pdf-stat-box {
            width: 25%;
            border: 1px solid #9ca3af;
            border-radius: 0;
            padding: 10px 6px;
            text-align: center;
            background-color: #ffffff;
            vertical-align: middle;
        }

        .pdf-stat-value {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            line-height: 1.2;
            margin-bottom: 4px;
        }

        .pdf-stat-label {
            font-size: 9px;
            font-weight: 600;
            color: #4b5563;
            text-transform: uppercase;
        }

        /* 5. Judul Tabel */
        .pdf-table-title {
            font-size: 11px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 8px;
        }

        /* 6. Tabel Data 5 Kolom */
        .pdf-data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .pdf-data-table thead {
            display: table-header-group;
        }

        .pdf-data-table tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .pdf-data-table th,
        .pdf-data-table td {
            border: 1px solid #9ca3af;
            padding: 6px 8px;
            background-color: #f3ecec;
            font-size: 10px;
            vertical-align: middle;
        }

        .pdf-data-table th {
            font-weight: 700;
            color: #111827;
        }

        .pdf-col-no {
            text-align: left;
            width: 34px;
        }

        .pdf-col-center {
            text-align: center;
        }

        .pdf-empty-cell {
            text-align: center;
            padding: 12px 8px;
            color: #4b5563;
            font-style: italic;
        }

        /* 8. Titimangsa / Tanda Tangan Rata Kanan */
        .pdf-signature-wrap {
            width: 100%;
            margin-top: 22px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .pdf-signature-box {
            width: 230px;
            margin-left: auto;
            text-align: left;
            font-size: 10.5px;
            line-height: 1.5;
        }

        .pdf-signature-space {
            height: 54px;
        }

        /* 7. Footer Halaman */
        .pdf-footer {
            position: fixed;
            bottom: -12mm;
            left: 0;
            right: 0;
            text-align: right;
            font-size: 9px;
            color: #4b5563;
        }

        .pdf-page-num:before {
            content: counter(page);
        }

        .pdf-page-total:before {
            content: counter(pages);
        }
    </style>
</head>
<body>
    <div class="pdf-footer">
        Halaman <span class="pdf-page-num"></span> dari <span class="pdf-page-total"></span>
    </div>

    <!-- 1. Kop Judul -->
    <div class="pdf-header">
        <h1 class="pdf-header-title"><?php echo $judulEsc; ?></h1>
    </div>

    <!-- 2. Identitas -->
    <div class="pdf-identity">
        <div class="pdf-identity-instansi"><?php echo $instansiEsc; ?></div>
        <div class="pdf-identity-tanggal"><?php echo $tanggalEsc; ?></div>
    </div>

    <!-- 3. Teks Ringkasan -->
    <p class="pdf-summary-text"><?php echo $ringkasanEsc; ?></p>

    <!-- 4. 4 Kotak Ringkasan -->
    <table class="pdf-stats-table">
        <tr>
            <td class="pdf-stat-box">
                <div class="pdf-stat-value"><?php echo $totalAset; ?></div>
                <div class="pdf-stat-label">Total Aset</div>
            </td>
            <td class="pdf-stat-box">
                <div class="pdf-stat-value"><?php echo $baikCount; ?></div>
                <div class="pdf-stat-label">Kondisi Baik</div>
            </td>
            <td class="pdf-stat-box">
                <div class="pdf-stat-value"><?php echo $ringanCount; ?></div>
                <div class="pdf-stat-label">Rusak Ringan</div>
            </td>
            <td class="pdf-stat-box">
                <div class="pdf-stat-value"><?php echo $beratCount; ?></div>
                <div class="pdf-stat-label">Rusak Berat</div>
            </td>
        </tr>
    </table>

    <!-- 5. Judul Tabel -->
    <div class="pdf-table-title"><?php echo $judulTabelEsc; ?></div>

    <!-- 6. Tabel Data 6 Kolom -->
    <table class="pdf-data-table">
        <thead>
            <tr>
                <th class="pdf-col-no">No</th>
                <th class="pdf-col-center" style="width: 105px;">Kode Barang</th>
                <th class="pdf-col-center">Nama Barang</th>
                <th class="pdf-col-center" style="width: 85px;">Lokasi</th>
                <th class="pdf-col-center" style="width: 65px;">Jumlah</th>
                <th class="pdf-col-center" style="width: 85px;">Kondisi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="6" class="pdf-empty-cell">Tidak ada data</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $idx => $r): ?>
                    <tr>
                        <td class="pdf-col-no"><?php echo ($idx + 1) . '.'; ?></td>
                        <td class="pdf-col-center"><?php echo htmlspecialchars((string) ($r['kode'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="pdf-col-center"><?php echo htmlspecialchars((string) ($r['nama'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="pdf-col-center"><?php echo htmlspecialchars(laporan_format_lokasi((string) ($r['penempatan'] ?? 'umum')), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="pdf-col-center"><?php echo htmlspecialchars((string) ($r['jumlah'] ?? '1'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="pdf-col-center"><?php echo htmlspecialchars((string) ($r['kondisi'] ?? 'Baik'), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- 8. Titimangsa / Tanda Tangan -->
    <div class="pdf-signature-wrap">
        <div class="pdf-signature-box">
            <div><?php echo $instansiEsc; ?>, <?php echo $tanggalEsc; ?></div>
            <div>Penanggung Jawab Aset</div>
            <div class="pdf-signature-space"></div>
            <div><strong><?php echo $penanggungJawab; ?></strong></div>
        </div>
    </div>
</body>
</html>
