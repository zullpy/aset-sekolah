<?php
/**
 * Konfigurasi SMTP PHPMailer / Mailer
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 * 
 * Salin file ini menjadi 'config/mail.php' dan sesuaikan kredensial SMTP Anda.
 * Untuk Gmail:
 * 1. Aktifkan 2-Step Verification pada akun Google pengirim.
 * 2. Buat App Password (Sandi Aplikasi) di: https://myaccount.google.com/apppasswords
 * 3. Gunakan sandi 16 digit tersebut pada SMTP_PASS (tanpa spasi).
 */

return [
    // Host server SMTP (contoh: smtp.gmail.com untuk Google Mail)
    'host'       => 'smtp.gmail.com',

    // Port SMTP (465 untuk SSL, 587 untuk STARTTLS)
    'port'       => 465,

    // Tipe enkripsi: 'ssl' atau 'tls'
    'encryption' => 'ssl',

    // Otentikasi SMTP (true/false)
    'auth'       => true,

    // Alamat email akun pengirim
    'username'   => '[EMAIL_ADDRESS]',

    // Kata sandi aplikasi / App Password (16 digit)
    'password'   => 'ganti-dengan-app-password',

    // Pengirim email yang tampil di penerima
    'from_email' => '[EMAIL_ADDRESS]',
    'from_name'  => 'Inventaris Aset SDN Budikarya',

    // Email tujuan balas (reply-to) opsional
    'reply_to'   => 'sdnbudikarya.aset@gmail.com',
];
