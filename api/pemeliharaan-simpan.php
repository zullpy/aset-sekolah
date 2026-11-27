<?php
/**
 * API Endpoint - Simpan / Proses Tahapan Pemeliharaan Aset (Transaksi PDO + Upload Foto)
 * Lokasi: api/pemeliharaan-simpan.php
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/conn.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/ajax.php';
require_once dirname(__DIR__) . '/includes/pemeliharaan-helper.php';
require_once dirname(__DIR__) . '/includes/aset-kelas-helper.php';

require_auth(['administrator', 'operator']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Metode request tidak diizinkan.', [], 405);
}

if (!verify_csrf_token()) {
    json_error('Token keamanan CSRF tidak valid. Silakan muat ulang halaman.', [], 403);
}

$uploadedFullPaths = [];

function pml_cleanup_uploaded_files(array $paths): void
{
    foreach ($paths as $p) {
        if (is_string($p) && $p !== '' && file_exists($p)) {
            @unlink($p);
        }
    }
}

try {
    $sourceId     = (int) ($_POST['source_id'] ?? 0);
    $kibId        = strtolower(trim((string) ($_POST['kib'] ?? '')));
    $asetId       = (int) ($_POST['aset_id'] ?? 0);
    $lokasi       = trim((string) ($_POST['lokasi'] ?? ''));
    $kondisiAwal  = trim((string) ($_POST['kondisi_awal'] ?? ''));
    $statusTujuan = trim((string) ($_POST['status'] ?? ''));
    $kondisiAkhir = trim((string) ($_POST['kondisi_akhir'] ?? ''));
    $jumlah       = (int) ($_POST['jumlah'] ?? 0);
    $tglRaw       = (string) ($_POST['tanggal_update'] ?? '');

    if ($jumlah < 1) {
        json_error('Jumlah minimal 1 unit.', ['field' => 'jumlah'], 422);
    }

    if (!in_array($statusTujuan, PEMELIHARAAN_STAGES, true)) {
        json_error('Status pemeliharaan tidak valid.', ['field' => 'status'], 422);
    }

    $tanggalUpdate = pml_parse_date($tglRaw, true);
    if ($tanggalUpdate === null) {
        json_error('Format Tanggal Update tidak valid.', ['field' => 'tanggal_update'], 422);
    }

    if ($lokasi === '') {
        json_error('Lokasi aset wajib dipilih.', ['field' => 'lokasi'], 422);
    }

    if (in_array($statusTujuan, PEMELIHARAAN_FINAL_CONDITION_REQUIRED, true)) {
        if ($kondisiAkhir === '' || !in_array($kondisiAkhir, PEMELIHARAAN_CONDITIONS, true)) {
            json_error('Kondisi Akhir wajib diisi untuk tahap ' . $statusTujuan . '.', ['field' => 'kondisi_akhir'], 422);
        }
    } else {
        if ($kondisiAkhir !== '' && !in_array($kondisiAkhir, PEMELIHARAAN_CONDITIONS, true)) {
            json_error('Kondisi Akhir tidak valid.', ['field' => 'kondisi_akhir'], 422);
        }
        $kondisiAkhir = ($kondisiAkhir === '') ? null : $kondisiAkhir;
    }

    // Validasi & simpan multi-foto dokumentasi ke uploads/pemeliharaan/
    $relativeDocs = [];
    if (isset($_FILES['dokumentasi'])) {
        $files = $_FILES['dokumentasi'];
        $names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
        $errors = is_array($files['error']) ? $files['error'] : [$files['error']];
        $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];

        $uploadDir = dirname(__DIR__) . '/uploads/pemeliharaan';
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        foreach ($names as $idx => $origName) {
            $err = (int) ($errors[$idx] ?? UPLOAD_ERR_NO_FILE);
            if ($err === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($err !== UPLOAD_ERR_OK) {
                pml_cleanup_uploaded_files($uploadedFullPaths);
                json_error('Gagal mengunggah file dokumentasi "' . basename((string) $origName) . '".', ['field' => 'dokumentasi'], 422);
            }

            $size = (int) ($sizes[$idx] ?? 0);
            if ($size <= 0 || $size > 5 * 1024 * 1024) {
                pml_cleanup_uploaded_files($uploadedFullPaths);
                json_error('Ukuran tiap foto dokumentasi maksimal 5MB.', ['field' => 'dokumentasi'], 422);
            }

            $tmpPath = (string) ($tmpNames[$idx] ?? '');
            $mime = $tmpPath !== '' ? $finfo->file($tmpPath) : false;
            if (!is_string($mime) || !isset($allowedMimes[$mime])) {
                pml_cleanup_uploaded_files($uploadedFullPaths);
                json_error('Format foto dokumentasi harus JPG, PNG, atau WEBP.', ['field' => 'dokumentasi'], 422);
            }

            if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
                pml_cleanup_uploaded_files($uploadedFullPaths);
                json_error('Gagal menyiapkan direktori unggahan dokumentasi.', [], 500);
            }

            $randomName = bin2hex(random_bytes(16)) . '.' . $allowedMimes[$mime];
            $destPath = $uploadDir . '/' . $randomName;
            $moved = is_uploaded_file($tmpPath) ? @move_uploaded_file($tmpPath, $destPath) : @rename($tmpPath, $destPath);
            if (!$moved) {
                pml_cleanup_uploaded_files($uploadedFullPaths);
                json_error('Gagal menyimpan foto dokumentasi ke server.', [], 500);
            }

            $uploadedFullPaths[] = $destPath;
            $relativeDocs[] = 'uploads/pemeliharaan/' . $randomName;
        }
    }

    ensure_klaim_aset_table($pdo);

    $currentUser = current_user();
    $isOperatorRole = ($currentUser && ($currentUser['role'] ?? '') === 'operator');
    $operatorKelasId = $isOperatorRole ? detect_operator_kelas_id($currentUser) : null;
    $operatorLokasi = ($operatorKelasId !== null) ? pml_penempatan_to_lokasi('kelas-' . $operatorKelasId) : null;

    $actorName = pml_resolve_actor_name($pdo);
    $docsJson = !empty($relativeDocs) ? json_encode($relativeDocs, JSON_UNESCAPED_SLASHES) : null;

    $pdo->beginTransaction();

    if ($sourceId > 0) {
        $stmtSrc = $pdo->prepare("SELECT * FROM pemeliharaan WHERE id = ? LIMIT 1 FOR UPDATE");
        $stmtSrc->execute([$sourceId]);
        $sourceRow = $stmtSrc->fetch(PDO::FETCH_ASSOC);

        if (!$sourceRow) {
            $pdo->rollBack();
            pml_cleanup_uploaded_files($uploadedFullPaths);
            json_error('Data antrean pemeliharaan tidak ditemukan.', [], 404);
        }

        // Cek izin akses pemeliharaan untuk operator per kelas
        if ($operatorKelasId !== null) {
            $isAllowed = in_array($sourceRow['lokasi'], [$operatorLokasi, 'Kelas ' . $operatorKelasId], true);
            if (!$isAllowed) {
                $stmtChkKlaim = $pdo->prepare("SELECT id FROM klaim_aset WHERE kelas_id = ? AND kib = ? AND aset_id = ? LIMIT 1");
                $stmtChkKlaim->execute([$operatorKelasId, $sourceRow['kib'], (int) $sourceRow['aset_id']]);
                $isAllowed = (bool) $stmtChkKlaim->fetch();
            }
            if (!$isAllowed) {
                $pdo->rollBack();
                pml_cleanup_uploaded_files($uploadedFullPaths);
                json_error('Anda tidak memiliki izin memproses pemeliharaan aset di luar kelas Anda.', [], 403);
            }
        }

        $sisaAktif = (int) $sourceRow['jumlah_aktif'];
        if ($sisaAktif < 1) {
            $pdo->rollBack();
            pml_cleanup_uploaded_files($uploadedFullPaths);
            json_error('Seluruh unit pada baris ini sudah diproses ke tahap berikutnya.', [], 422);
        }

        $currentStatus = (string) $sourceRow['status'];
        $allowedNext = PEMELIHARAAN_TRANSITIONS[$currentStatus] ?? [];
        if (!in_array($statusTujuan, $allowedNext, true)) {
            $pdo->rollBack();
            pml_cleanup_uploaded_files($uploadedFullPaths);
            json_error("Transisi status tidak valid dari \"{$currentStatus}\" ke \"{$statusTujuan}\".", ['field' => 'status'], 422);
        }

        if ($jumlah > $sisaAktif) {
            $pdo->rollBack();
            pml_cleanup_uploaded_files($uploadedFullPaths);
            json_error("Jumlah melebihi sisa unit pada tahap ini (maksimal {$sisaAktif} unit).", ['field' => 'jumlah'], 422);
        }

        // Pecah baris: kurangi jumlah_aktif di baris sumber tanpa menimpa jumlah riwayatnya
        $stmtDec = $pdo->prepare("UPDATE pemeliharaan SET jumlah_aktif = jumlah_aktif - ? WHERE id = ?");
        $stmtDec->execute([$jumlah, $sourceId]);

        $kibId        = (string) $sourceRow['kib'];
        $asetId       = (int) $sourceRow['aset_id'];
        $monitoringId = $sourceRow['monitoring_id'] !== null ? (int) $sourceRow['monitoring_id'] : null;
        $kodeAset     = (string) $sourceRow['kode_aset'];
        $namaAset     = (string) $sourceRow['nama_aset'];
        if ($kondisiAwal === '' || !in_array($kondisiAwal, PEMELIHARAAN_CONDITIONS, true)) {
            $kondisiAwal = (string) $sourceRow['kondisi_awal'];
        }
        $parentId     = $sourceId;
    } else {
        $asset = pml_find_kib_asset($pdo, $kibId, $asetId);
        if ($asset === null) {
            $pdo->rollBack();
            pml_cleanup_uploaded_files($uploadedFullPaths);
            json_error('Aset tidak ditemukan pada Data Aset.', ['field' => 'aset'], 422);
        }

        if (!in_array($statusTujuan, PEMELIHARAAN_NEW_ENTRY_STATUSES, true)) {
            $pdo->rollBack();
            pml_cleanup_uploaded_files($uploadedFullPaths);
            json_error('Status awal proses baru hanya boleh Perlu pemeliharaan atau Sedang ditindak lanjut.', ['field' => 'status'], 422);
        }

        if ($kondisiAwal === '' || !in_array($kondisiAwal, PEMELIHARAAN_CONDITIONS, true)) {
            $pdo->rollBack();
            pml_cleanup_uploaded_files($uploadedFullPaths);
            json_error('Kondisi awal wajib dipilih.', ['field' => 'kondisi_awal'], 422);
        }

        if ($operatorKelasId !== null) {
            $stmtKlaim = $pdo->prepare("SELECT jumlah FROM klaim_aset WHERE kelas_id = ? AND kib = ? AND aset_id = ? LIMIT 1");
            $stmtKlaim->execute([$operatorKelasId, $kibId, $asetId]);
            $klaimRow = $stmtKlaim->fetch(PDO::FETCH_ASSOC);
            if (!$klaimRow) {
                $pdo->rollBack();
                pml_cleanup_uploaded_files($uploadedFullPaths);
                json_error('Aset ini belum diklaim oleh kelas Anda.', ['field' => 'aset'], 422);
            }

            $lokasi = $operatorLokasi;
            $klaimQty = (int) $klaimRow['jumlah'];

            $stmtUsed = $pdo->prepare("
                SELECT COALESCE(SUM(jumlah_aktif), 0)
                FROM pemeliharaan
                WHERE kib = ? AND aset_id = ?
                  AND lokasi IN (?, ?)
                  AND jumlah_aktif > 0
                  AND status IN ('Perlu pemeliharaan', 'Sedang ditindak lanjut', 'Selesai ditindak lanjut')
                FOR UPDATE
            ");
            $stmtUsed->execute([$kibId, $asetId, $operatorLokasi, 'Kelas ' . $operatorKelasId]);
            $inMaint = (int) $stmtUsed->fetchColumn();
            $maxAllowed = max(0, $klaimQty - $inMaint);
            if ($jumlah > $maxAllowed) {
                $pdo->rollBack();
                pml_cleanup_uploaded_files($uploadedFullPaths);
                json_error("Jumlah melebihi sisa unit aset yang tersedia untuk kelas Anda (maksimal {$maxAllowed} unit).", ['field' => 'jumlah'], 422);
            }
        } else {
            $stmtUsed = $pdo->prepare("
                SELECT COALESCE(SUM(jumlah_aktif), 0)
                FROM pemeliharaan
                WHERE kib = ? AND aset_id = ? AND jumlah_aktif > 0
                  AND status IN ('Perlu pemeliharaan', 'Sedang ditindak lanjut', 'Selesai ditindak lanjut')
                FOR UPDATE
            ");
            $stmtUsed->execute([$asset['kib'], $asset['aset_id']]);
            $inMaint = (int) $stmtUsed->fetchColumn();
            $maxAllowed = max(0, (int) $asset['jumlah_total'] - $inMaint);
            if ($jumlah > $maxAllowed) {
                $pdo->rollBack();
                pml_cleanup_uploaded_files($uploadedFullPaths);
                json_error("Jumlah melebihi sisa unit aset yang tersedia untuk pemeliharaan (maksimal {$maxAllowed} unit).", ['field' => 'jumlah'], 422);
            }
        }

        $monitoringId = null;
        $kodeAset     = $asset['kode'];
        $namaAset     = $asset['nama'];
        $parentId     = null;
    }

    $stmtIns = $pdo->prepare("
        INSERT INTO pemeliharaan
            (kib, aset_id, monitoring_id, parent_id, kode_aset, nama_aset, lokasi,
             kondisi_awal, kondisi_akhir, jumlah, jumlah_aktif, status, tanggal_update, dokumentasi, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtIns->execute([
        $kibId,
        $asetId,
        $monitoringId,
        $parentId,
        $kodeAset,
        $namaAset,
        $lokasi,
        $kondisiAwal,
        $kondisiAkhir,
        $jumlah,
        $jumlah,
        $statusTujuan,
        $tanggalUpdate,
        $docsJson,
        $actorName,
    ]);
    $newId = (int) $pdo->lastInsertId();

    // Catat ke log aktivitas dashboard
    $actColor = match ($statusTujuan) {
        'Perlu pemeliharaan'      => '#d9363e',
        'Sedang ditindak lanjut'  => '#2f62b8',
        'Selesai ditindak lanjut' => '#f08a24',
        'Digunakan kembali'       => '#2e9e57',
        'Disimpan'                => '#8e44ad',
        default                   => '#3b82f6',
    };
    $actText = "Pemeliharaan {$namaAset} ({$kodeAset}) di {$lokasi}: {$jumlah} unit -> {$statusTujuan}";
    $stmtLog = $pdo->prepare("INSERT INTO kib_activities (teks, pelaku, waktu, warna) VALUES (?, ?, 'Baru saja', ?)");
    $stmtLog->execute([$actText, $actorName, $actColor]);

    $pdo->commit();

    json_success("Proses pemeliharaan \"{$namaAset}\" berhasil disimpan ({$statusTujuan}).", [
        'id'            => $newId,
        'status'        => $statusTujuan,
        'jumlah'        => $jumlah,
        'dokumentasi'   => $relativeDocs,
        'tanggal_update'=> $tanggalUpdate,
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    pml_cleanup_uploaded_files($uploadedFullPaths);
    error_log('API Pemeliharaan Simpan Error: ' . $e->getMessage());
    json_error('Terjadi kesalahan saat menyimpan proses pemeliharaan.', [], 500);
}
