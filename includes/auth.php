<?php
/**
 * Middleware Autentikasi, Otorisasi & Keamanan Sesi (CSRF)
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

require_once dirname(__DIR__) . '/config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('generate_csrf_token')) {
    /**
     * Menghasilkan atau mengambil token CSRF unik untuk sesi aktif
     */
    function generate_csrf_token(): string
    {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('get_csrf_token')) {
    /**
     * Alias untuk mengambil token CSRF aktif
     */
    function get_csrf_token(): string
    {
        return generate_csrf_token();
    }
}

if (!function_exists('verify_csrf_token')) {
    /**
     * Memverifikasi validitas token CSRF dari POST body atau Header HTTP
     *
     * @param string|null $token Token yang ingin diverifikasi (jika null, otomatis mendeteksi dari $_POST / $_SERVER)
     * @return bool
     */
    function verify_csrf_token(?string $token = null): bool
    {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (empty($sessionToken) || !is_string($sessionToken)) {
            return false;
        }

        if ($token === null) {
            $token = $_POST['csrf_token']
                ?? $_SERVER['HTTP_X_CSRF_TOKEN']
                ?? $_SERVER['HTTP_X_XSRF_TOKEN']
                ?? '';
        }

        if (empty($token) || !is_string($token)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }
}

if (!function_exists('csrf_input')) {
    /**
     * Menghasilkan tag input HTML hidden untuk token CSRF
     */
    function csrf_input(): string
    {
        $token = htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}

if (!function_exists('is_logged_in')) {
    /**
     * Memeriksa apakah pengguna saat ini sudah terautentikasi / login
     */
    function is_logged_in(): bool
    {
        return !empty($_SESSION['user_id']);
    }
}

if (!function_exists('current_user')) {
    /**
     * Mengambil informasi pengguna yang sedang login
     */
    function current_user(): ?array
    {
        if (!is_logged_in()) {
            return null;
        }

        return [
            'id'           => $_SESSION['user_id'] ?? null,
            'username'     => $_SESSION['username'] ?? '',
            'nama_lengkap' => $_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? '',
            'role'         => strtolower(trim((string) ($_SESSION['role'] ?? ''))),
            'role_display' => ucfirst(strtolower(trim((string) ($_SESSION['role'] ?? '')))),
        ];
    }
}

if (!function_exists('release_session_lock')) {
    /**
     * Melepaskan lock session PHP agar request paralel lainnya tidak terblokir
     */
    function release_session_lock(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }
}

if (!function_exists('require_auth')) {
    /**
     * Mewajibkan pengguna untuk login sebelum dapat mengakses halaman
     *
     * @param string|array $requiredRole Peran yang diizinkan ('administrator', 'operator', atau array keduanya)
     * @param string|null $redirectUrl URL pengalihan jika belum login
     */
    function require_auth(string|array $requiredRole = '', ?string $redirectUrl = null): void
    {
        if (!is_logged_in()) {
            $docRoot    = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
            $projectDir = str_replace('\\', '/', realpath(dirname(__DIR__)));

            if ($docRoot !== '' && str_starts_with($projectDir, $docRoot)) {
                $subPath = substr($projectDir, strlen($docRoot));
                $appUrlPath = '/' . ltrim($subPath, '/');
                $appUrlPath = rtrim($appUrlPath, '/') . '/';
            } else {
                $appUrlPath = '/';
            }

            $loginUrl = $redirectUrl ?? ($appUrlPath . 'pages/login.php');

            if (function_exists('is_ajax_request') && is_ajax_request()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Sesi Anda telah berakhir. Silakan login kembali.',
                    'data'    => ['redirect' => $loginUrl],
                ]);
                exit;
            }

            header('Location: ' . $loginUrl);
            exit;
        }

        // Cek otorisasi peran (Role-Based Access Control)
        if (!empty($requiredRole)) {
            $userRole = strtolower($_SESSION['role'] ?? '');
            $allowedRoles = is_array($requiredRole)
                ? array_map('strtolower', $requiredRole)
                : [strtolower($requiredRole)];

            if (!in_array($userRole, $allowedRoles, true)) {
                http_response_code(403);
                if (function_exists('is_ajax_request') && is_ajax_request()) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Akses ditolak: Anda tidak memiliki izin untuk mengakses fitur ini.',
                    ]);
                    exit;
                }

                exit('<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h2>403 - Akses Ditolak</h2><p>Peran akun Anda (' . htmlspecialchars($_SESSION['role'] ?? '-') . ') tidak diizinkan membuka menu ini.</p><a href="javascript:history.back()">Kembali</a></body></html>');
            }
        }
    }
}

if (!function_exists('logout_user')) {
    /**
     * Menghancurkan sesi pengguna dengan aman
     */
    function logout_user(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }
}
