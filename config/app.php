<?php
/**
 * Konfigurasi Global & Deteksi Lingkungan (Environment)
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

if (!function_exists('detect_environment')) {
    /**
     * Mendeteksi apakah aplikasi berjalan di 'production' atau 'local' (development)
     *
     * Prioritas deteksi:
     * 1. Variabel environment APP_ENV (jika disetel di server / virtualhost / .env)
     * 2. Nama domain / host server (localhost, 127.0.0.1, .test, .local, IP privat LAN)
     * 3. Jika CLI dan tidak ada konfigurasi spesifik, default ke 'local'
     *
     * @return string 'production'|'local'
     */
    function detect_environment(): string
    {
        // 1. Cek variabel environment eksplisit
        $explicitEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? null);
        if (!empty($explicitEnv)) {
            $normalized = strtolower(trim($explicitEnv));
            if (in_array($normalized, ['prod', 'production', 'live'])) {
                return 'production';
            }
            if (in_array($normalized, ['local', 'dev', 'development', 'staging', 'test'])) {
                return 'local';
            }
        }

        // 2. Jika dijalankan lewat CLI terminal
        if (php_sapi_name() === 'cli') {
            return 'local';
        }

        // 3. Cek domain / host HTTP web
        $host = strtolower($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
        $host = preg_replace('/:\d+$/', '', $host); // hilangkan port (misal localhost:8000 -> localhost)

        if (empty($host)) {
            return 'local';
        }

        // Host lokal standar
        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return 'local';
        }

        // TLD lokal umum (.test, .local, .localhost, .lan)
        if (preg_match('/\.(test|local|localhost|lan|site)$/', $host)) {
            return 'local';
        }

        // IP Privat (LAN / WiFi lokal: 192.168.x.x, 10.x.x.x, 172.16.x.x - 172.31.x.x)
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false && filter_var($host, FILTER_VALIDATE_IP)) {
            return 'local';
        }

        // Selain di atas (misal sdnbudikarya.sch.id, inventaris.sekolah.id, VPS IP Publik) dianggap PRODUCTION
        return 'production';
    }
}

if (!defined('APP_ENV')) {
    define('APP_ENV', detect_environment());
}

if (!defined('IS_PRODUCTION')) {
    define('IS_PRODUCTION', APP_ENV === 'production');
}

if (!defined('IS_LOCAL')) {
    define('IS_LOCAL', APP_ENV === 'local');
}

if (!function_exists('is_production')) {
    function is_production(): bool
    {
        return defined('IS_PRODUCTION') ? IS_PRODUCTION : (APP_ENV === 'production');
    }
}

if (!function_exists('is_local')) {
    function is_local(): bool
    {
        return defined('IS_LOCAL') ? IS_LOCAL : (APP_ENV === 'local');
    }
}
