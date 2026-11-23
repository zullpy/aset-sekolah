<?php
// ====== KONFIGURASI ======
const DB_HOST = '';
const DB_NAME = '';
const DB_USER = '';
const DB_PASS = '';

const MIGRATIONS_DIR = __DIR__ . '/../database/migrations';

// Muat deteksi lingkungan (production vs local)
require_once __DIR__ . '/app.php';

// Otomatis aktif di LOCAL/DEVELOPMENT, non-aktif di PRODUCTION
// true  = migrasi jalan otomatis tiap file ini di-include (praktis saat development)
// false = migrasi hanya jalan manual lewat `php migrate.php` (aman untuk produksi)
if (!defined('AUTO_MIGRATE')) {
    define('AUTO_MIGRATE', is_local());
}

// ====== KONEKSI ======
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Koneksi DB gagal: ' . $e->getMessage());
    http_response_code(500);
    exit('Koneksi database gagal.');
}

// ====== MIGRATION RUNNER ======
function run_migrations(PDO $pdo, string $dir): array
{
    $log = [];

    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        batch INT NOT NULL,
        executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $files = glob($dir . '/*.sql') ?: [];
    sort($files);

    $getPending = function () use ($pdo, $files) {
        $done = $pdo->query("SELECT migration FROM migrations")->fetchAll(PDO::FETCH_COLUMN);
        return array_values(array_filter($files, fn($f) => !in_array(basename($f), $done, true)));
    };

    // cek cepat: kalau tidak ada yang baru, selesai
    if (!$getPending()) {
        return $log;
    }

    // lock supaya tidak jalan dobel kalau ada 2 request bersamaan
    $locked = (int) $pdo->query("SELECT GET_LOCK('app_migrate', 10)")->fetchColumn();
    if ($locked !== 1) {
        throw new RuntimeException('Gagal mendapatkan lock migrasi.');
    }

    try {
        $pending = $getPending(); // cek ulang setelah dapat lock
        if (!$pending) {
            return $log;
        }

        $batch  = (int) $pdo->query("SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations")->fetchColumn();
        $insert = $pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");

        foreach ($pending as $path) {
            $name = basename($path);

            $sql = file_get_contents($path);
            $sql = preg_replace('/^\s*--.*$/m', '', $sql);
            $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql);

            try {
                foreach ($statements as $stmt) {
                    $stmt = trim($stmt);
                    if ($stmt !== '') {
                        $pdo->exec($stmt);
                    }
                }
                $insert->execute([$name, $batch]);
                $log[] = "OK    $name";
            } catch (Throwable $e) {
                throw new RuntimeException("Migrasi $name gagal: " . $e->getMessage(), 0, $e);
            }
        }
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('app_migrate')");
    }

    return $log;
}

// ====== JALANKAN OTOMATIS ======
if (AUTO_MIGRATE) {
    try {
        run_migrations($pdo, MIGRATIONS_DIR);
    } catch (Throwable $e) {
        error_log($e->getMessage());
        http_response_code(500);
        exit('Migrasi database gagal. Cek log server.');
    }
}