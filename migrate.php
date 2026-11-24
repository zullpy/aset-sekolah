<?php
/**
 * CLI Migration Runner
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 * 
 * Cara menjalankan di terminal:
 * php migrate.php
 */

define('AUTO_MIGRATE', false);

// Keamanan: Restriksi ketat agar migrasi database HANYA dapat dijalankan melalui terminal CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Akses ditolak: Skrip migrasi database hanya dapat dijalankan melalui terminal CLI (php migrate.php).');
}

// Muat konfigurasi global & deteksi lingkungan
if (file_exists(__DIR__ . '/config/app.php')) {
    require_once __DIR__ . '/config/app.php';
}

require_once __DIR__ . '/config/conn.php';

$envName = defined('APP_ENV') ? APP_ENV : 'unknown';

echo "=== Migrasi Database SDN Budi Karya ===" . PHP_EOL;
echo "[INFO] Lingkungan: " . strtoupper($envName) . PHP_EOL;

try {
    $logs = run_migrations($pdo, MIGRATIONS_DIR);

    if (empty($logs)) {
        echo "[INFO] Database sudah mutakhir (up-to-date). Tidak ada migrasi baru." . PHP_EOL;
    } else {
        foreach ($logs as $log) {
            echo "[SUKSES] " . $log . PHP_EOL;
        }
        echo "[SELESAI] Seluruh file migrasi baru berhasil dieksekusi." . PHP_EOL;
    }
} catch (Throwable $e) {
    echo "[GAGAL] Migrasi terhenti: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
