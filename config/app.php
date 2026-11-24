<?php
/**
 * Konfigurasi Global & Deteksi Lingkungan (Environment)
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

// Standarisasi zona waktu aplikasi ke Waktu Indonesia Barat (WIB / UTC+7)
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi Pengamanan Cookie Sesi PHP
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https'));

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

if (!function_exists('detect_environment')) {
    /**
     * Mendeteksi apakah aplikasi berjalan di 'production' atau 'local' (development)
     * dengan perlindungan penuh terhadap manipulasi header HTTP (Host Header Spoofing).
     *
     * Prioritas deteksi:
     * 1. Variabel environment APP_ENV (jika disetel di server / virtualhost / .env)
     * 2. Path direktori sistem server (aaPanel / wwwroot vs folder lokal)
     * 3. CLI terminal
     * 4. Alamat IP remote socket (bukan header HTTP) & domain whitelist
     * 5. Default aman: 'production'
     *
     * @return string 'production'|'local'
     */
    function detect_environment(): string
    {
        // 1. Cek variabel environment eksplisit dari server / OS
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

        // 2. Cek Path File Sistem Server (Sisi Server, kebal manipulasi client HTTP)
        $projectPath = str_replace('\\', '/', realpath(__DIR__ . '/..') ?: __DIR__);
        
        // Indikator direktori server produksi (aaPanel / VPS / hosting produksi)
        if (str_contains($projectPath, '/www/wwwroot/') || str_contains($projectPath, '/var/www/')) {
            return 'production';
        }

        // 3. Jika dijalankan lewat CLI terminal lokal
        if (php_sapi_name() === 'cli') {
            return 'local';
        }

        // 4. Verifikasi Koneksi Web Sisi Jaringan & Server
        // REMOTE_ADDR berasal dari TCP socket koneksi, bukan dari header HTTP client
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

        $isLoopbackRemote = in_array($remoteAddr, ['127.0.0.1', '::1'], true);
        $isPrivateRemote  = filter_var($remoteAddr, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;

        // Jika IP pemohon adalah IP publik internet, PASTI PRODUCTION (bukan dev lokal)
        if (!empty($remoteAddr) && !$isLoopbackRemote && !$isPrivateRemote) {
            return 'production';
        }

        // 5. Cek Domain Host Server dengan Whitelist Terbatas
        $rawHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        $host = strtolower(preg_replace('/:\d+$/', '', trim($rawHost)));

        // Domain produksi resmi
        $productionDomains = ['aset.sdnbudikarya.my.id', 'sdnbudikarya.sch.id', 'sdnbudikarya.my.id'];
        if (in_array($host, $productionDomains, true)) {
            return 'production';
        }

        // Hanya anggap lokal jika pemohon berasal dari loopback/LAN dan menggunakan domain lokal
        if (($isLoopbackRemote || $isPrivateRemote) && (
            $host === 'localhost' ||
            $host === '127.0.0.1' ||
            $host === '::1' ||
            preg_match('/\.(test|local|localhost|lan)$/', $host) ||
            (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false && filter_var($host, FILTER_VALIDATE_IP))
        )) {
            return 'local';
        }

        // Default aman (Fail-Closed): Selalu anggap production jika tidak terbukti lingkungan lokal
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

if (!function_exists('get_app_canonical_host')) {
    /**
     * Mengembalikan hostname resmi yang aman dan divalidasi whitelist
     * untuk mencegah Host Header Injection / Password Reset Poisoning.
     */
    function get_app_canonical_host(): string
    {
        $rawHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        $host = strtolower(preg_replace('/:\d+$/', '', trim($rawHost)));

        $allowedProductionHosts = [
            'aset.sdnbudikarya.my.id',
            'sdnbudikarya.sch.id',
            'sdnbudikarya.my.id',
        ];

        if (is_production()) {
            if (in_array($host, $allowedProductionHosts, true)) {
                if (!empty($_SERVER['HTTP_HOST']) && preg_match('/:\d+$/', $_SERVER['HTTP_HOST'], $m)) {
                    return $host . $m[0];
                }
                return $host;
            }
            return 'aset.sdnbudikarya.my.id';
        }

        // Lingkungan lokal
        $allowedLocalHosts = ['localhost', '127.0.0.1', '::1'];
        if (in_array($host, $allowedLocalHosts, true) || preg_match('/\.(test|local|localhost|lan)$/', $host)) {
            if (!empty($_SERVER['HTTP_HOST']) && preg_match('/:\d+$/', $_SERVER['HTTP_HOST'], $m)) {
                return $host . $m[0];
            }
            return $host;
        }

        return 'localhost';
    }
}
