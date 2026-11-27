<?php
/**
 * Endpoint POST - Simpan & Ubah Klaim Aset Kelas
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 * Lokasi: actions/aset_kelas_klaim.php
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/conn.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/ajax.php';
require_once dirname(__DIR__) . '/includes/aset-kelas-helper.php';

require_auth(['administrator', 'operator']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_error('Metode request tidak diizinkan.', [], 405);
}

if (!verify_csrf_token()) {
    json_error('Token keamanan (CSRF) tidak valid. Silakan muat ulang halaman.', [], 403);
}

$currentUser = current_user();
$kelasId = resolve_kelas_id($_POST['kelas_id'] ?? null, $currentUser);
if ($kelasId === null) {
    json_error('Parameter kelas tidak valid.', [], 400);
}

if (!user_can_access_kelas($currentUser, $kelasId)) {
    json_error('Anda tidak memiliki izin untuk mengklaim aset di kelas ini.', [], 403);
}

$klaimId = isset($_POST['klaim_id']) && $_POST['klaim_id'] !== '' ? (int) $_POST['klaim_id'] : 0;
$kib     = strtolower(trim((string) ($_POST['kib'] ?? '')));
$asetId  = (int) ($_POST['aset_id'] ?? 0);
$jumlah  = (int) ($_POST['jumlah'] ?? 0);

if ($jumlah < 1) {
    json_error('Jumlah aset minimal adalah 1.', [], 422);
}

try {
    ensure_klaim_aset_table($pdo);

    // Mode edit jumlah klaim yang sudah ada
    if ($klaimId > 0) {
        $pdo->beginTransaction();

        $stmtExisting = $pdo->prepare("SELECT * FROM klaim_aset WHERE id = ? AND kelas_id = ? LIMIT 1 FOR UPDATE");
        $stmtExisting->execute([$klaimId, $kelasId]);
        $existing = $stmtExisting->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            $pdo->rollBack();
            json_error('Data klaim aset tidak ditemukan di kelas ini.', [], 404);
        }

        $master = fetch_master_aset_by_id($pdo, $existing['kib'], (int) $existing['aset_id']);
        if ($master) {
            $stmtUsedOther = $pdo->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM klaim_aset WHERE kib = ? AND aset_id = ? AND id <> ? FOR UPDATE");
            $stmtUsedOther->execute([$existing['kib'], (int) $existing['aset_id'], $klaimId]);
            $usedOther = (int) $stmtUsedOther->fetchColumn();
            $maxAllowed = max(0, $master['jumlah_total'] - $usedOther);

            if ($jumlah > $maxAllowed) {
                $pdo->rollBack();
                json_error("Jumlah melebihi sisa stok aset tersedia ({$maxAllowed}).", [], 422);
            }
        }

        $stmtUpdate = $pdo->prepare("UPDATE klaim_aset SET jumlah = ? WHERE id = ? AND kelas_id = ?");
        $stmtUpdate->execute([$jumlah, $klaimId, $kelasId]);
        $pdo->commit();

        json_success('Data klaim aset berhasil diperbarui.', ['id' => $klaimId, 'jumlah' => $jumlah]);
    }

    // Mode tambah klaim baru
    if (!isset(ASET_KELAS_KIB_META[$kib]) || $asetId <= 0) {
        json_error('Pilih nama aset dan spesifikasi aset terlebih dahulu.', [], 422);
    }

    $pdo->beginTransaction();

    $master = fetch_master_aset_by_id($pdo, $kib, $asetId);
    if (!$master) {
        $pdo->rollBack();
        json_error('Data master aset yang dipilih tidak ditemukan.', [], 404);
    }

    // Validasi sisa stok aset yang belum diklaim (dengan lock transaksi)
    $stmtUsed = $pdo->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM klaim_aset WHERE kib = ? AND aset_id = ? FOR UPDATE");
    $stmtUsed->execute([$kib, $asetId]);
    $used = (int) $stmtUsed->fetchColumn();
    $sisaStok = max(0, $master['jumlah_total'] - $used);

    if ($sisaStok < 1) {
        $pdo->rollBack();
        json_error('Seluruh stok aset ini sudah diklaim.', [], 422);
    }

    if ($jumlah > $sisaStok) {
        $pdo->rollBack();
        json_error("Jumlah klaim melebihi sisa stok tersedia ({$sisaStok}).", [], 422);
    }

    // Cek apakah aset yang sama sudah pernah diklaim oleh kelas ini -> gabungkan jumlahnya atau tambah baris baru
    // Untuk menjaga kejelasan no registrasi per klaim, tambahkan baris baru dengan no_reg otomatis atau dari master
    $noRegInput = trim((string) ($_POST['no_reg'] ?? ''));
    $noReg = $noRegInput !== '' ? format_no_reg_5digit($noRegInput) : get_next_klaim_no_reg($pdo, $kelasId);

    $stmtInsert = $pdo->prepare("
        INSERT INTO klaim_aset (
            kelas_id, kib, aset_id, kode_aset, jenis_aset, jenis_kib,
            nama_aset, no_reg, spesifikasi, tahun, sumber, harga, jumlah, user_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtInsert->execute([
        $kelasId,
        $kib,
        $asetId,
        $master['kode_aset'],
        $master['jenis_aset'],
        $master['jenis_kib'],
        $master['nama_aset'],
        $noReg,
        $master['spesifikasi'],
        $master['tahun'],
        $master['sumber'],
        $master['harga'],
        $jumlah,
        $currentUser['id'] ?? null,
    ]);

    $newId = (int) $pdo->lastInsertId();
    $pdo->commit();
    json_success('Klaim aset berhasil disimpan.', ['id' => $newId]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('aset_kelas_klaim error: ' . $e->getMessage());
    json_error('Terjadi kesalahan saat menyimpan klaim aset.', [], 500);
}
