<?php
/**
 * CLI Migration Runner
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 * 
 * Cara menjalankan di terminal:
 * php migrate.php
 */

define('AUTO_MIGRATE', false);

require_once __DIR__ . '/config/conn.php';

$isCli   = (php_sapi_name() === 'cli');
$newline = $isCli ? PHP_EOL : "<br>";

echo ($isCli ? "=== Migrasi Database SDN Budi Karya ===" : "<h3>Migrasi Database SDN Budi Karya</h3>") . $newline;
echo "[INFO] Lingkungan: " . strtoupper(APP_ENV) . $newline;

try {
    $logs = run_migrations($pdo, MIGRATIONS_DIR);

    if (empty($logs)) {
        echo "[INFO] Database sudah mutakhir (up-to-date). Tidak ada migrasi baru." . $newline;
    } else {
        foreach ($logs as $log) {
            echo "[SUKSES] " . $log . $newline;
        }
        echo "[SELESAI] Seluruh file migrasi baru berhasil dieksekusi." . $newline;
    }
} catch (Throwable $e) {
    echo "[GAGAL] Migrasi terhenti: " . $e->getMessage() . $newline;
    exit(1);
}
