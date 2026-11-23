<?php
/**
 * Template Email: Atur Ulang Kata Sandi (Password Reset)
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 * 
 * Fungsi ini menghasilkan HTML email yang ramah untuk berbagai client (Gmail, Outlook, Yahoo, Apple Mail).
 * Menggunakan inline-styling, table-based layout, dan palet warna Maroon (#852019).
 */

if (!function_exists('get_reset_password_email_html')) {
    /**
     * Menghasilkan string HTML email reset password
     *
     * @param string $resetLink URL lengkap untuk reset password
     * @param string $recipientEmail Email penerima
     * @param string $recipientName Nama lengkap/username penerima (opsional)
     * @param int $expireMinutes Masa berlaku link dalam menit (default 15)
     * @return string
     */
    function get_reset_password_email_html(string $resetLink, string $recipientEmail, string $recipientName = '', int $expireMinutes = 15): string
    {
        $displayName = !empty($recipientName) ? htmlspecialchars($recipientName) : htmlspecialchars($recipientEmail);
        $safeLink    = htmlspecialchars($resetLink);
        $year        = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Ulang Kata Sandi - SDN Budi Karya</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; }
        @media screen and (max-width: 600px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .content-padding { padding: 24px 18px !important; }
            .btn-reset { display: block !important; width: 100% !important; text-align: center !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9;">

    <!-- Wrapper Keseluruhan -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; padding: 30px 10px;">
        <tr>
            <td align="center">
                <!-- Card Container Email (Maksimal 580px) -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="email-container" style="max-width: 580px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
                    
                    <!-- Header Banner Maroon -->
                    <tr>
                        <td align="center" style="background: linear-gradient(135deg, #6d1c16 0%, #852019 100%); padding: 32px 24px; text-align: center;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center">
                                        <!-- Title Header -->
                                        <h1 style="margin: 0; font-size: 19px; font-weight: 800; color: #ffffff; letter-spacing: -0.2px; text-transform: uppercase;">
                                            SDN Budi Karya
                                        </h1>
                                        <p style="margin: 6px 0 0 0; font-size: 12.5px; color: #fbeeed; opacity: 0.92; font-weight: 500;">
                                            Sistem Informasi Inventaris & Aset Sekolah
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Isi Email -->
                    <tr>
                        <td class="content-padding" style="padding: 36px 36px 28px 36px;">
                            
                            <!-- Salam & Judul -->
                            <h2 style="margin: 0 0 12px 0; font-size: 20px; font-weight: 700; color: #0f172a;">
                                Atur Ulang Kata Sandi
                            </h2>
                            <p style="margin: 0 0 18px 0; font-size: 14.5px; line-height: 1.6; color: #475569;">
                                Halo <strong>{$displayName}</strong>,
                            </p>
                            <p style="margin: 0 0 24px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                Kami menerima permintaan untuk mereset kata sandi akun Anda pada sistem Inventaris Aset SDN Budi Karya. Klik tombol di bawah ini untuk membuat kata sandi baru:
                            </p>

                            <!-- CTA Button (Maroon) -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 28px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{$safeLink}" target="_blank" class="btn-reset" style="background-color: #852019; color: #ffffff; font-size: 14.5px; font-weight: 700; text-decoration: none; padding: 13px 32px; border-radius: 10px; display: inline-block; box-shadow: 0 4px 14px rgba(133,32,25,0.30); text-align: center;">
                                            Atur Ulang Kata Sandi
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Informasi Masa Berlaku -->
                            <div style="background-color: #fef2f2; border: 1px solid #fee2e2; border-left: 4px solid #852019; border-radius: 8px; padding: 12px 16px; margin: 26px 0 20px 0;">
                                <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #7f1d1d;">
                                    <strong>Penting:</strong> Tautan ini hanya berlaku selama <strong>{$expireMinutes} menit</strong> sejak email ini dikirim. Demi keamanan, jangan sebarkan tautan ini kepada siapapun.
                                </p>
                            </div>

                            <!-- Link Alternatif jika Tombol Tidak Bisa Diklik -->
                            <p style="margin: 22px 0 8px 0; font-size: 12.5px; line-height: 1.5; color: #64748b;">
                                Jika tombol di atas tidak dapat diklik, salin dan buka tautan berikut di peramban (browser) Anda:
                            </p>
                            <p style="margin: 0 0 20px 0; font-size: 12px; line-height: 1.5; word-break: break-all; color: #852019;">
                                <a href="{$safeLink}" target="_blank" style="color: #852019; text-decoration: underline;">
                                    {$safeLink}
                                </a>
                            </p>

                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 26px 0 18px 0;">

                            <p style="margin: 0; font-size: 12.5px; line-height: 1.5; color: #94a3b8;">
                                Jika Anda tidak pernah meminta perubahan kata sandi ini, silakan abaikan email ini. Akun dan kata sandi Anda saat ini tetap aman.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer Email -->
                    <tr>
                        <td align="center" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 22px 24px; text-align: center;">
                            <p style="margin: 0 0 6px 0; font-size: 12px; font-weight: 600; color: #64748b;">
                                SDN Budi Karya &copy; {$year}
                            </p>
                            <p style="margin: 0; font-size: 11.5px; color: #94a3b8; line-height: 1.4;">
                                Pesan ini dikirim secara otomatis oleh Sistem Informasi Inventaris Aset.<br>
                                Harap jangan membalas langsung ke alamat email ini.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
HTML;
    }
}

if (!function_exists('get_reset_password_email_text')) {
    /**
     * Menghasilkan teks polos (Plain Text) email reset password sebagai alternatif MIME
     *
     * @param string $resetLink
     * @param string $recipientEmail
     * @param string $recipientName
     * @param int $expireMinutes
     * @return string
     */
    function get_reset_password_email_text(string $resetLink, string $recipientEmail, string $recipientName = '', int $expireMinutes = 15): string
    {
        $displayName = !empty($recipientName) ? $recipientName : $recipientEmail;
        return "Halo {$displayName},\n\n"
             . "Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda pada Sistem Informasi Inventaris Aset SDN Budi Karya.\n\n"
             . "Silakan klik atau buka tautan di bawah ini untuk membuat kata sandi baru:\n"
             . "{$resetLink}\n\n"
             . "Tautan ini hanya berlaku selama {$expireMinutes} menit.\n"
             . "Jika Anda tidak meminta perubahan kata sandi, abaikan email ini.\n\n"
             . "--\n"
             . "SDN Budi Karya - Sistem Informasi Inventaris Aset\n";
    }
}
