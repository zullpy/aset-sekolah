<?php
/**
 * Helper Respon AJAX & JSON
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

if (!function_exists('is_ajax_request')) {
    /**
     * Memeriksa apakah request saat ini dikirim melalui AJAX / Fetch
     */
    function is_ajax_request(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
            || (isset($_POST['ajax']) && $_POST['ajax'] === '1');
    }
}

if (!function_exists('json_response')) {
    /**
     * Mengirimkan respon JSON terstandar dan menghentikan eksekusi
     */
    function json_response(bool $success, string $message = '', array $data = [], int $httpCode = 200): void
    {
        // Bersihkan output buffer jika ada karakter tak disengaja sebelumnya
        if (ob_get_length()) {
            ob_clean();
        }

        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success'   => $success,
            'message'   => $message,
            'data'      => $data,
            'timestamp' => time(),
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

if (!function_exists('json_success')) {
    /**
     * Helper respon JSON sukses
     */
    function json_success(string $message = 'Operasi berhasil', array $data = []): void
    {
        json_response(true, $message, $data, 200);
    }
}

if (!function_exists('json_error')) {
    /**
     * Helper respon JSON gagal / validasi
     */
    function json_error(string $message = 'Terjadi kesalahan', array $data = [], int $httpCode = 400): void
    {
        json_response(false, $message, $data, $httpCode);
    }
}
