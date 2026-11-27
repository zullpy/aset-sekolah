<?php
/**
 * Helper Enkripsi Password Terenkripsi (AES-256-GCM)
 * Digunakan hanya untuk fitur "Lihat Password" oleh Administrator
 * Autentikasi login tetap menggunakan password_hash (bcrypt)
 */

function get_password_vault_key(): string
{
    $envKey = getenv('APP_PASSWORD_KEY');
    if (is_string($envKey) && $envKey !== '') {
        return hash('sha256', $envKey, true);
    }

    $keyFile = dirname(__DIR__) . '/config/.app-secret.key';
    if (is_file($keyFile)) {
        $hex = trim((string) file_get_contents($keyFile));
        if (strlen($hex) === 64 && ctype_xdigit($hex)) {
            $bin = hex2bin($hex);
            if ($bin !== false) {
                return $bin;
            }
        }
    }

    $rawKey = random_bytes(32);
    $hexKey = bin2hex($rawKey);
    @file_put_contents($keyFile, $hexKey, LOCK_EX);
    @chmod($keyFile, 0600);

    return $rawKey;
}

function encrypt_user_password(string $plain): string
{
    if ($plain === '') {
        return '';
    }

    $key = get_password_vault_key();
    $iv  = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);

    if ($cipher === false) {
        throw new RuntimeException('Gagal mengenkripsi password.');
    }

    return 'v1:' . base64_encode($iv . $tag . $cipher);
}

function decrypt_user_password(?string $encoded): ?string
{
    if (!is_string($encoded) || !str_starts_with($encoded, 'v1:')) {
        return null;
    }

    $raw = base64_decode(substr($encoded, 3), true);
    if ($raw === false || strlen($raw) <= 28) {
        return null;
    }

    $iv     = substr($raw, 0, 12);
    $tag    = substr($raw, 12, 16);
    $cipher = substr($raw, 28);

    $plain = openssl_decrypt($cipher, 'aes-256-gcm', get_password_vault_key(), OPENSSL_RAW_DATA, $iv, $tag);
    return ($plain === false) ? null : $plain;
}
