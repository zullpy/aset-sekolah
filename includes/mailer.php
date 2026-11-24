<?php
/**
 * Modul Pengirim Email (Mailer)
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 * 
 * Mendukung 2 metode pengiriman:
 * 1. PHPMailer (jika diinstal via Composer di vendor/autoload.php atau includes/PHPMailer/)
 * 2. Native SMTP Socket Client (bawaan PHP murni menggunakan stream_socket_client SSL/TLS, tanpa dependensi Composer)
 */

require_once __DIR__ . '/email_templates/reset_password.php';

if (!function_exists('get_mail_config')) {
    /**
     * Memuat konfigurasi email dari config/mail.php atau fallback
     */
    function get_mail_config(): array
    {
        $configFile = dirname(__DIR__) . '/config/mail.php';
        $exampleFile = dirname(__DIR__) . '/config/mail.example.php';

        $config = [];
        if (file_exists($configFile)) {
            $config = require $configFile;
        } elseif (file_exists($exampleFile)) {
            $config = require $exampleFile;
        }

        $defaults = [
            'host'       => 'smtp.gmail.com',
            'port'       => 465,
            'encryption' => 'ssl', // 'ssl' atau 'tls'
            'auth'       => true,
            'username'   => '',
            'password'   => '',
            'from_email' => 'sdnbudikarya.aset@gmail.com',
            'from_name'  => 'Inventaris Aset SDN Budi Karya',
            'reply_to'   => '',
        ];

        return array_merge($defaults, is_array($config) ? $config : []);
    }
}

if (!function_exists('send_mail')) {
    /**
     * Mengirim email menggunakan PHPMailer (jika ada) atau Native Socket SMTP
     * 
     * @param string $to Alamat email penerima
     * @param string $subject Judul email
     * @param string $htmlBody Konten email dalam format HTML
     * @param string $altBody Konten alternatif teks polos (opsional)
     * @param string $toName Nama penerima (opsional)
     * @return array ['success' => bool, 'message' => string]
     */
    function send_mail(string $to, string $subject, string $htmlBody, string $altBody = '', string $toName = ''): array
    {
        $config = get_mail_config();

        // Cek validasi email tujuan
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Alamat email penerima tidak valid.'];
        }

        // Cek apakah password masih placeholder atau kosong
        $rawPass = trim($config['password'] ?? '');
        if ($rawPass === '' || $rawPass === 'ganti-dengan-app-password') {
            return [
                'success' => false,
                'message' => 'Konfigurasi SMTP belum selesai. Harap atur App Password di file config/mail.php.'
            ];
        }

        // Cek apakah PHPMailer tersedia melalui Composer autoload
        $autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }

        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return send_mail_with_phpmailer($config, $to, $subject, $htmlBody, $altBody, $toName);
        }

        // Fallback: Menggunakan Native Socket SMTP Client
        return send_mail_with_native_smtp($config, $to, $subject, $htmlBody, $altBody, $toName);
    }
}

if (!function_exists('send_mail_with_phpmailer')) {
    /**
     * Pengiriman email menggunakan library resmi PHPMailer
     */
    function send_mail_with_phpmailer(array $config, string $to, string $subject, string $htmlBody, string $altBody, string $toName): array
    {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // Server settings
            $mail->isSMTP();
            $mail->Host       = $config['host'];
            $mail->SMTPAuth   = (bool) $config['auth'];
            $mail->Username   = $config['username'];
            $mail->Password   = $config['password'];
            $mail->SMTPSecure = strtolower($config['encryption']) === 'tls' 
                ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS 
                : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = (int) $config['port'];
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 15;

            // Recipients
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($to, $toName);
            if (!empty($config['reply_to'])) {
                $mail->addReplyTo($config['reply_to']);
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = !empty($altBody) ? $altBody : strip_tags($htmlBody);

            $mail->send();
            return ['success' => true, 'message' => 'Email berhasil dikirim.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Gagal mengirim email via PHPMailer: ' . $e->getMessage()];
        }
    }
}

if (!function_exists('send_mail_with_native_smtp')) {
    /**
     * Pengiriman email menggunakan Native SMTP Socket (SSL/TLS) bawaan PHP
     * Bekerja langsung di semua instalasi PHP tanpa perlu dependensi composer.
     */
    function send_mail_with_native_smtp(array $config, string $to, string $subject, string $htmlBody, string $altBody, string $toName): array
    {
        $host       = $config['host'];
        $port       = (int) $config['port'];
        $encryption = strtolower($config['encryption']);
        $username   = $config['username'];
        $password   = $config['password'];
        $fromEmail  = $config['from_email'];
        $fromName   = $config['from_name'];

        $socketPrefix = ($encryption === 'ssl' || $port === 465) ? 'ssl://' : 'tcp://';
        $socketHost   = $socketPrefix . $host . ':' . $port;

        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
                'SNI_enabled'       => true,
                'peer_name'         => $host,
            ]
        ]);

        $timeout = 15;
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client($socketHost, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);

        if (!$socket) {
            return ['success' => false, 'message' => "Tidak dapat terhubung ke server SMTP ($host:$port): $errstr"];
        }

        stream_set_timeout($socket, $timeout);

        $readResponse = function() use ($socket): string {
            $response = '';
            while ($line = fgets($socket, 515)) {
                $response .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            return $response;
        };

        $sendCommand = function(string $cmd) use ($socket, $readResponse): string {
            fputs($socket, $cmd . "\r\n");
            return $readResponse();
        };

        // Baca greeting dari server (220)
        $greet = $readResponse();
        if (!str_starts_with($greet, '220')) {
            fclose($socket);
            return ['success' => false, 'message' => "Respon server SMTP tidak terduga: $greet"];
        }

        // Kirim EHLO
        $ehlo = $sendCommand("EHLO " . gethostname());

        // Jika enkripsi TLS pada port non-SSL (seperti 587)
        if (($encryption === 'tls' || $port === 587) && str_starts_with($socketPrefix, 'tcp')) {
            $starttls = $sendCommand("STARTTLS");
            if (!str_starts_with($starttls, '220')) {
                fclose($socket);
                return ['success' => false, 'message' => "Gagal memulai TLS: $starttls"];
            }
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                return ['success' => false, 'message' => "Gagal negosiasi enkripsi TLS."];
            }
            $sendCommand("EHLO " . gethostname());
        }

        // Autentikasi SMTP AUTH LOGIN
        if ($config['auth']) {
            $authInit = $sendCommand("AUTH LOGIN");
            if (!str_starts_with($authInit, '334')) {
                fclose($socket);
                return ['success' => false, 'message' => "Server menolak AUTH LOGIN: $authInit"];
            }

            $authUser = $sendCommand(base64_encode($username));
            if (!str_starts_with($authUser, '334')) {
                fclose($socket);
                return ['success' => false, 'message' => "Username SMTP ditolak: $authUser"];
            }

            $authPass = $sendCommand(base64_encode($password));
            if (!str_starts_with($authPass, '235')) {
                fclose($socket);
                return ['success' => false, 'message' => "Password / App Password SMTP salah: $authPass"];
            }
        }

        // MAIL FROM
        $mailFrom = $sendCommand("MAIL FROM:<$fromEmail>");
        if (!str_starts_with($mailFrom, '250')) {
            fclose($socket);
            return ['success' => false, 'message' => "MAIL FROM ditolak: $mailFrom"];
        }

        // RCPT TO
        $rcptTo = $sendCommand("RCPT TO:<$to>");
        if (!str_starts_with($rcptTo, '250')) {
            fclose($socket);
            return ['success' => false, 'message' => "Penerima email ($to) ditolak: $rcptTo"];
        }

        // DATA
        $dataInit = $sendCommand("DATA");
        if (!str_starts_with($dataInit, '354')) {
            fclose($socket);
            return ['success' => false, 'message' => "Server tidak siap menerima DATA: $dataInit"];
        }

        // Susun MIME Message
        $boundary = '=_mb_' . md5(uniqid(microtime(true), true));
        $recipientHeader = !empty($toName) ? "=?UTF-8?B?" . base64_encode($toName) . "?= <$to>" : "<$to>";
        $senderHeader    = "=?UTF-8?B?" . base64_encode($fromName) . "?= <$fromEmail>";
        $subjectHeader   = "=?UTF-8?B?" . base64_encode($subject) . "?=";

        $headers = [
            "Date: " . date('r'),
            "From: $senderHeader",
            "To: $recipientHeader",
            "Subject: $subjectHeader",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"$boundary\"",
        ];

        if (!empty($config['reply_to'])) {
            $headers[] = "Reply-To: <" . $config['reply_to'] . ">";
        }

        $plainTextContent = !empty($altBody) ? $altBody : strip_tags($htmlBody);

        $body  = implode("\r\n", $headers) . "\r\n\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($plainTextContent)) . "\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $body .= "--$boundary--\r\n.";

        $dataSend = $sendCommand($body);
        $sendCommand("QUIT");
        fclose($socket);

        if (!str_starts_with($dataSend, '250')) {
            return ['success' => false, 'message' => "Pengiriman data email gagal: $dataSend"];
        }

        return ['success' => true, 'message' => 'Email berhasil dikirim.'];
    }
}

if (!function_exists('send_reset_password_mail')) {
    /**
     * Helper praktis untuk mengirim email reset password dengan template SDN Budi Karya
     *
     * @param string $toEmail Email penerima
     * @param string $resetLink Tautan khusus reset password
     * @param string $recipientName Nama lengkap/username (opsional)
     * @return array ['success' => bool, 'message' => string]
     */
    function send_reset_password_mail(string $toEmail, string $resetLink, string $recipientName = ''): array
    {
        $subject  = 'Atur Ulang Kata Sandi - Inventaris Aset SDN Budi Karya';
        $htmlBody = get_reset_password_email_html($resetLink, $toEmail, $recipientName, 15);
        $textBody = get_reset_password_email_text($resetLink, $toEmail, $recipientName, 15);

        return send_mail($toEmail, $subject, $htmlBody, $textBody, $recipientName);
    }
}
