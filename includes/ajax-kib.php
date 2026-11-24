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

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? 'all';

// Pemetaan ID KIB dengan nama tabel dan konfigurasi kolom
$kibConfigs = [
    'kib-a' => [
        'table'  => 'kib_a',
        'nama'   => 'KIB A (Tanah)',
        'kode'   => 'KIB A',
        'warna'  => '#852019',
        'file'   => 'kib-a.php',
        'fields' => ['kode', 'nama', 'no_reg', 'luas', 'tahun', 'lokasi', 'status_tanah', 'sertifikat', 'penggunaan', 'sumber', 'harga', 'lampiran'],
    ],
    'kib-b' => [
        'table'  => 'kib_b',
        'nama'   => 'KIB B (Peralatan & Mesin)',
        'kode'   => 'KIB B',
        'warna'  => '#f97316',
        'file'   => 'kib-b.php',
        'fields' => ['kode', 'jenis', 'nama', 'no_reg', 'merk', 'spesifikasi', 'bahan', 'no_pabrik', 'tahun', 'jumlah', 'harga', 'sumber', 'lampiran'],
    ],
    'kib-c' => [
        'table'  => 'kib_c',
        'nama'   => 'KIB C (Gedung & Bangunan)',
        'kode'   => 'KIB C',
        'warna'  => '#eab308',
        'file'   => 'kib-c.php',
        'fields' => ['kode', 'nama', 'no_reg', 'konstruksi', 'luas', 'lokasi', 'tgl_izin', 'status_tanah', 'tahun', 'harga', 'sumber', 'lampiran'],
    ],
    'kib-d' => [
        'table'  => 'kib_d',
        'nama'   => 'KIB D (Jalan, Irigasi & Jaringan)',
        'kode'   => 'KIB D',
        'warna'  => '#22c55e',
        'file'   => 'kib-d.php',
        'fields' => ['kode', 'nama', 'no_reg', 'konstruksi', 'ukuran', 'luas', 'tahun', 'dokumen', 'harga', 'sumber', 'lampiran'],
    ],
    'kib-e' => [
        'table'  => 'kib_e',
        'nama'   => 'KIB E (Aset Tetap Lainnya)',
        'kode'   => 'KIB E',
        'warna'  => '#06b6d4',
        'file'   => 'kib-e.php',
        'fields' => ['kode', 'jenis', 'nama', 'no_reg', 'spesifikasi_judul', 'spesifikasi_pengarang', 'spesifikasi_penerbit', 'spesifikasi_tahun', 'tahun', 'jumlah', 'harga_satuan', 'harga_total', 'sumber', 'lampiran'],
    ],
    'kib-f' => [
        'table'  => 'kib_f',
        'nama'   => 'KIB F (Konstruksi Dalam Pengerjaan)',
        'kode'   => 'KIB F',
        'warna'  => '#8b5cf6',
        'file'   => 'kib-f.php',
        'fields' => ['kode', 'jenis', 'nama', 'tingkat', 'beton', 'luas', 'letak_bangunan', 'dokumen', 'tgl_mulai', 'status_tanah', 'biaya', 'persen_fisik', 'status_pengerjaan', 'sumber', 'keterangan', 'lampiran'],
    ],
];

try {
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

            // Ambil 8 aktivitas terbaru
            $stmtAct = $pdo->query("SELECT id, teks, pelaku, waktu, warna, created_at FROM kib_activities ORDER BY id DESC LIMIT 8");
            $activities = $stmtAct->fetchAll(PDO::FETCH_ASSOC);

            json_success('Seluruh dataset KIB berhasil dimuat dari database.', [
                'datasets'    => $allData,
                'summary'     => $summary,
                'totalAset'   => $totalUnitAll,
                'activities'  => $activities,
            ]);
            break;

        // Ambil aktivitas terbaru
        case 'activities':
            $stmtAct = $pdo->query("SELECT id, teks, pelaku, waktu, warna, created_at FROM kib_activities ORDER BY id DESC LIMIT 8");
            $activities = $stmtAct->fetchAll(PDO::FETCH_ASSOC);
            json_success('Data aktivitas berhasil dimuat.', ['activities' => $activities]);
            break;

        // Simpan / Tambah / Perbarui satu aset
        case 'save':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_error('Metode request tidak diizinkan.', [], 405);
            }

            if (!verify_csrf_token()) {
                json_error('Token CSRF tidak valid. Silakan muat ulang halaman.', [], 403);
            }

            $kibId = $_POST['kib'] ?? '';
            if (!isset($kibConfigs[$kibId])) {
                json_error('Kategori KIB tidak valid.');
            }

            $cfg   = $kibConfigs[$kibId];
            $table = $cfg['table'];
            $id    = (int) ($_POST['id'] ?? 0);

            // Kumpulkan kolom yang sesuai
            $dataToSave = [];
            foreach ($cfg['fields'] as $f) {
                if (isset($_POST[$f])) {
                    $dataToSave[$f] = trim((string) $_POST[$f]);
                }
            }

            if (empty($dataToSave['nama'])) {
                json_error('Nama aset wajib diisi.');
            }

            // Jika kode aset kosong, generate otomatis
            if (empty($dataToSave['kode'])) {
                $year = $dataToSave['tahun'] ?? date('Y');
                $nextCount = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn() + 1;
                $dataToSave['kode'] = strtoupper($kibId) . '/' . $year . '/' . str_pad($nextCount, 3, '0', STR_PAD_LEFT);
            }

            $currentUser = current_user();
            $actor = $currentUser['nama_lengkap'] ?? $currentUser['username'] ?? 'Administrator';

            if ($id > 0) {
                // UPDATE
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

                // Log aktivitas
                $actTeks = "Data aset \"{$dataToSave['nama']}\" diperbarui di {$cfg['kode']}";
                $stmtLog = $pdo->prepare("INSERT INTO kib_activities (teks, pelaku, waktu, warna) VALUES (?, ?, 'Baru saja', '#3b82f6')");
                $stmtLog->execute([$actTeks, $actor]);

                json_success("Data aset di {$cfg['kode']} berhasil diperbarui.", [
                    'id'   => $id,
                    'item' => array_merge(['id' => $id], $dataToSave),
                ]);
            } else {
                // INSERT
                $cols   = array_keys($dataToSave);
                $params = array_values($dataToSave);
                $marks  = array_fill(0, count($cols), '?');

                $sql = "INSERT INTO {$table} (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $marks) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                $newId = (int) $pdo->lastInsertId();

                // Log aktivitas
                $actTeks = "Aset baru \"{$dataToSave['nama']}\" ditambahkan ke {$cfg['kode']}";
                $stmtLog = $pdo->prepare("INSERT INTO kib_activities (teks, pelaku, waktu, warna) VALUES (?, ?, 'Baru saja', '#22c55e')");
                $stmtLog->execute([$actTeks, $actor]);

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

            // Ambil nama aset sebelum dihapus untuk log
            $stmtItem = $pdo->prepare("SELECT nama FROM {$table} WHERE id = ? LIMIT 1");
            $stmtItem->execute([$id]);
            $item = $stmtItem->fetch();
            $namaAset = $item['nama'] ?? 'Aset';

            $stmtDel = $pdo->prepare("DELETE FROM {$table} WHERE id = ?");
            $stmtDel->execute([$id]);

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
