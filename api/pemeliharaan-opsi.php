<?php
/**
 * API Endpoint - Opsi Dinamis Pemeliharaan (Lokasi, Nama Aset, Detail Aset)
 * Lokasi: api/pemeliharaan-opsi.php
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
    $lokasiOptions = pml_get_lokasi_options($pdo);

    // Opsi Nama Aset unik untuk Modal Filter Data
    $stmtFilterNama = $pdo->query("
        SELECT DISTINCT nama_aset
        FROM pemeliharaan
        WHERE jumlah_aktif > 0 AND TRIM(nama_aset) <> ''
        ORDER BY nama_aset ASC
    ");
    $filterNamaOptions = $stmtFilterNama->fetchAll(PDO::FETCH_COLUMN);

    // Hitung unit aset yang sedang dalam alur pemeliharaan aktif (belum digunakan kembali/disimpan)
    $activeMaintMap = [];
    $stmtActive = $pdo->query("
        SELECT kib, aset_id, COALESCE(SUM(jumlah_aktif), 0) AS qty
        FROM pemeliharaan
        WHERE jumlah_aktif > 0
          AND status IN ('Perlu pemeliharaan', 'Sedang ditindak lanjut', 'Selesai ditindak lanjut')
        GROUP BY kib, aset_id
    ");
    while ($aRow = $stmtActive->fetch(PDO::FETCH_ASSOC)) {
        $activeMaintMap[$aRow['kib'] . ':' . (int) $aRow['aset_id']] = (int) $aRow['qty'];
    }

    // Kondisi terbaru per (kib, aset_id) dari monitoring_aset (diutamakan yang rusak)
    $monCondMap = [];
    $stmtMon = $pdo->query("
        SELECT kib, aset_id, penempatan, kondisi
        FROM monitoring_aset
        ORDER BY FIELD(kondisi, 'Rusak Berat', 'Rusak Ringan', 'Baik', 'Hilang'), tgl_update DESC, id DESC
    ");
    while ($mRow = $stmtMon->fetch(PDO::FETCH_ASSOC)) {
        $key = $mRow['kib'] . ':' . (int) $mRow['aset_id'];
        if (!isset($monCondMap[$key])) {
            $monCondMap[$key] = [
                'kondisi' => (string) $mRow['kondisi'],
                'lokasi'  => pml_penempatan_to_lokasi((string) ($mRow['penempatan'] ?? 'umum')),
            ];
        }
    }

    $formAsetOptions = [];

    // 1. Prioritaskan entri antrean 'Perlu pemeliharaan' yang sudah ada (mis. dari Monitoring)
    $stmtQueue = $pdo->query("
        SELECT id, kib, aset_id, kode_aset, nama_aset, lokasi, kondisi_awal, jumlah_aktif, tanggal_update
        FROM pemeliharaan
        WHERE status = 'Perlu pemeliharaan' AND jumlah_aktif > 0
        ORDER BY tanggal_update DESC, id DESC
    ");
    while ($qRow = $stmtQueue->fetch(PDO::FETCH_ASSOC)) {
        $kibId = (string) $qRow['kib'];
        $queueId = (int) $qRow['id'];
        $sisaQty = (int) $qRow['jumlah_aktif'];
        $formAsetOptions[] = [
            'option_key'       => 'queue:' . $queueId,
            'source'           => 'queue',
            'pemeliharaan_id'  => $queueId,
            'kib'              => $kibId,
            'kib_label'        => PEMELIHARAAN_KIB_TABLES[$kibId]['label'] ?? strtoupper($kibId),
            'aset_id'          => (int) $qRow['aset_id'],
            'kode_aset'        => (string) $qRow['kode_aset'],
            'nama_aset'        => (string) $qRow['nama_aset'],
            'lokasi'           => (string) $qRow['lokasi'],
            'kondisi_awal'     => (string) $qRow['kondisi_awal'],
            'current_status'   => 'Perlu pemeliharaan',
            'allowed_statuses' => PEMELIHARAAN_TRANSITIONS['Perlu pemeliharaan'],
            'sisa_jumlah'      => $sisaQty,
            'label'            => "{$qRow['nama_aset']} ({$qRow['kode_aset']} • {$qRow['lokasi']} • {$sisaQty} unit perlu pemeliharaan)",
        ];
    }

    // 2. Sertakan aset KIB terdaftar yang masih memiliki kuota unit untuk dipelihara
    foreach (PEMELIHARAAN_KIB_TABLES as $kibId => $meta) {
        $table = $meta['table'];
        $jumlahCol = $meta['has_jumlah'] ? 'jumlah' : 'NULL AS jumlah';
        $stmtKib = $pdo->query("SELECT id, kode, nama, penempatan, {$jumlahCol} FROM {$table} ORDER BY nama ASC, id ASC");
        while ($row = $stmtKib->fetch(PDO::FETCH_ASSOC)) {
            $asetId = (int) $row['id'];
            $totalQty = $meta['has_jumlah'] ? pml_parse_jumlah_int($row['jumlah'] ?? 1) : 1;
            $inMaint = $activeMaintMap[$kibId . ':' . $asetId] ?? 0;
            $sisaKuota = max(0, $totalQty - $inMaint);
            if ($sisaKuota <= 0) {
                continue;
            }

            $monInfo = $monCondMap[$kibId . ':' . $asetId] ?? null;
            $defaultKondisi = ($monInfo && in_array($monInfo['kondisi'], ['Rusak Ringan', 'Rusak Berat'], true))
                ? $monInfo['kondisi']
                : 'Rusak Ringan';
            $defaultLokasi = $monInfo['lokasi'] ?? pml_penempatan_to_lokasi((string) ($row['penempatan'] ?? 'umum'));

            $formAsetOptions[] = [
                'option_key'       => 'kib:' . $kibId . ':' . $asetId,
                'source'           => 'kib',
                'pemeliharaan_id'  => null,
                'kib'              => $kibId,
                'kib_label'        => $meta['label'],
                'aset_id'          => $asetId,
                'kode_aset'        => (string) $row['kode'],
                'nama_aset'        => (string) $row['nama'],
                'lokasi'           => $defaultLokasi,
                'kondisi_awal'     => $defaultKondisi,
                'current_status'   => null,
                'allowed_statuses' => PEMELIHARAAN_NEW_ENTRY_STATUSES,
                'sisa_jumlah'      => $sisaKuota,
                'label'            => "{$row['nama']} ({$row['kode']} • {$meta['label']} • maks {$sisaKuota} unit)",
            ];
        }
    }

    json_success('Opsi pemeliharaan berhasil dimuat.', [
        'lokasi_options'            => $lokasiOptions,
        'filter_nama_aset_options'  => $filterNamaOptions,
        'form_aset_options'         => $formAsetOptions,
    ]);
} catch (Throwable $e) {
    error_log('API Pemeliharaan Opsi Error: ' . $e->getMessage());
    json_error('Gagal memuat opsi pemeliharaan.', [], 500);
}
