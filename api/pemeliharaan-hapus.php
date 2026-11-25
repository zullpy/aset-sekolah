<?php
/**
 * API Endpoint - Hapus Data Pemeliharaan Aset
 * Lokasi: api/pemeliharaan-hapus.php
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/conn.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/ajax.php';
require_once dirname(__DIR__) . '/includes/pemeliharaan-helper.php';

require_auth(['administrator', 'operator']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Metode request tidak diizinkan.', [], 405);
}

if (!verify_csrf_token()) {
    json_error('Token keamanan CSRF tidak valid. Silakan muat ulang halaman.', [], 403);
}

try {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        $rawBody = file_get_contents('php://input');
        $jsonBody = json_decode((string) $rawBody, true);
        if (is_array($jsonBody) && isset($jsonBody['id'])) {
            $id = (int) $jsonBody['id'];
        }
    }

    if ($id <= 0) {
        json_error('ID data pemeliharaan tidak valid.', [], 422);
    }

    $pdo->beginTransaction();

    $stmtRow = $pdo->prepare("SELECT * FROM pemeliharaan WHERE id = ? LIMIT 1 FOR UPDATE");
    $stmtRow->execute([$id]);
    $row = $stmtRow->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $pdo->rollBack();
        json_error('Data pemeliharaan tidak ditemukan.', [], 404);
    }

    $stmtChild = $pdo->prepare("SELECT COUNT(*) FROM pemeliharaan WHERE parent_id = ?");
    $stmtChild->execute([$id]);
    if ((int) $stmtChild->fetchColumn() > 0) {
        $pdo->rollBack();
        json_error('Data ini sudah memiliki tahapan lanjutan. Hapus tahapan lanjutannya terlebih dahulu.', [], 422);
    }

    if (!empty($row['parent_id'])) {
        $parentId = (int) $row['parent_id'];
        $restoreQty = max(0, (int) $row['jumlah']);
        if ($restoreQty > 0) {
            $stmtRestore = $pdo->prepare("UPDATE pemeliharaan SET jumlah_aktif = jumlah_aktif + ? WHERE id = ?");
            $stmtRestore->execute([$restoreQty, $parentId]);
        }
    }

    $docs = pml_parse_dokumentasi($row['dokumentasi'] ?? '');

    $stmtDel = $pdo->prepare("DELETE FROM pemeliharaan WHERE id = ?");
    $stmtDel->execute([$id]);

    $actorName = pml_resolve_actor_name($pdo);
    $actText = "Menghapus data pemeliharaan {$row['nama_aset']} ({$row['kode_aset']}) [{$row['status']}]";
    $stmtLog = $pdo->prepare("INSERT INTO kib_activities (teks, pelaku, waktu, warna) VALUES (?, ?, 'Baru saja', '#dc2626')");
    $stmtLog->execute([$actText, $actorName]);

    $pdo->commit();

    // Bersihkan file foto fisik jika tidak dipakai baris lain
    $baseDir = realpath(dirname(__DIR__) . '/uploads/pemeliharaan');
    if ($baseDir !== false && !empty($docs)) {
        foreach ($docs as $relPath) {
            $stmtRef = $pdo->prepare("SELECT COUNT(*) FROM pemeliharaan WHERE dokumentasi LIKE ?");
            $stmtRef->execute(['%' . $relPath . '%']);
            if ((int) $stmtRef->fetchColumn() === 0) {
                $fullPath = realpath(dirname(__DIR__) . '/' . $relPath);
                if ($fullPath !== false && str_starts_with($fullPath, $baseDir . DIRECTORY_SEPARATOR) && is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }
        }
    }

    json_success("Data pemeliharaan \"{$row['nama_aset']}\" berhasil dihapus.", [
        'id' => $id,
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('API Pemeliharaan Hapus Error: ' . $e->getMessage());
    json_error('Terjadi kesalahan saat menghapus data pemeliharaan.', [], 500);
}
