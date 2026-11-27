<?php
/**
 * Halaman Lupa Password - Sistem Informasi Inventaris Aset SDN Budi Karya
 * Lokasi: pages/lupa-password.php
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/ajax.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

// Deteksi path dasar aplikasi & file aset
$projectDir = str_replace('\\', '/', realpath(dirname(__DIR__)));
$docRoot    = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';

if ($docRoot !== '' && str_starts_with($projectDir, $docRoot)) {
    $subPath = substr($projectDir, strlen($docRoot));
    $appUrlPath = '/' . ltrim($subPath, '/');
    $appUrlPath = rtrim($appUrlPath, '/') . '/';
} else {
    $appUrlPath = '/';
}

$assetsPath     = '../assets/';
$logoUrl        = $assetsPath . 'logo.webp';
$bgSchoolUrl    = $assetsPath . 'sekolah.webp';
$logoSrc        = $logoUrl;

// Peran aktif sebelumnya (jika ada)
$roleParam = strtolower($_GET['role'] ?? 'administrator');
$loginBackUrl = 'login.php' . ($roleParam === 'operator' ? '?role=operator' : '?role=administrator');

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validasi Token Keamanan CSRF
    if (!verify_csrf_token()) {
        $errorMessage = 'Sesi atau token keamanan (CSRF) tidak valid. Silakan muat ulang halaman.';
        if (is_ajax_request()) {
            json_error($errorMessage, [], 403);
        }
    } else {
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            $errorMessage = 'Silakan masukkan alamat email akun Anda.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'Format alamat email tidak valid.';
        } else {
            $userFound = null;
            $dbConnected = false;
            $tableExists = false;

            // Cek database jika tersedia
            if (file_exists(dirname(__DIR__) . '/config/conn.php')) {
                try {
                    require_once dirname(__DIR__) . '/config/conn.php';
                    if (isset($pdo)) {
                        $dbConnected = true;
                        $checkTable = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
                        if ($checkTable) {
                            $tableExists = true;
                            $stmt = $pdo->prepare("SELECT id, username, nama_lengkap, email, role FROM users WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) OR LOWER(TRIM(username)) = LOWER(TRIM(?)) LIMIT 1");
                            $stmt->execute([$email, $email]);
                            $userFound = $stmt->fetch();
                            if ($userFound && !empty($userFound['email'])) {
                                $email = $userFound['email'];
                            }
                        }
                    }
                } catch (Throwable $e) {
                    error_log('DB check error: ' . $e->getMessage());
                }
            }

            // Pesan respon generik terstandar untuk mencegah kebocoran/enumerasi akun (User Enumeration)
            $standardSuccessMsg = 'Jika alamat email terdaftar pada sistem inventaris, tautan pemulihan kata sandi telah dikirimkan. Silakan periksa kotak masuk atau folder spam email Anda (berlaku selama 15 menit).';

            // Jika akun ada dan valid, buat token reset dan kirim email
            if ($userFound) {
                // Buat token acak berkekuatan tinggi (32 byte = 64 karakter hex)
                $token = bin2hex(random_bytes(32));
                // Simpan hanya hash SHA-256 token di database untuk mencegah kebocoran jika database disusupi
                $tokenHash = hash('sha256', $token);

                $recipientName = $userFound['nama_lengkap'] ?? $userFound['username'] ?? '';
                $expiresAtTimestamp = time() + 900; // 15 menit
                $expiresAt = date('Y-m-d H:i:s', $expiresAtTimestamp);

                if ($dbConnected) {
                    try {
                        $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            email VARCHAR(255) NOT NULL,
                            token VARCHAR(255) NOT NULL UNIQUE,
                            expires_at DATETIME NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            INDEX idx_email (email),
                            INDEX idx_token (token)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                        // Bersihkan token yang kedaluwarsa
                        $pdo->exec("DELETE FROM password_resets WHERE expires_at < NOW()");

                        // Hapus token lama untuk email yang sama
                        $stmtDel = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                        $stmtDel->execute([$email]);

                        // Simpan hash token baru
                        $stmtIns = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                        $stmtIns->execute([$email, $tokenHash, $expiresAt]);
                    } catch (Throwable $e) {
                        error_log('Gagal menyimpan password_resets: ' . $e->getMessage());
                    }
                }

                // Simpan hash token di sesi sebagai cadangan
                if (!isset($_SESSION['password_reset_cache'])) {
                    $_SESSION['password_reset_cache'] = [];
                }
                $_SESSION['password_reset_cache'][$tokenHash] = [
                    'email'      => $email,
                    'name'       => $recipientName,
                    'expires_at' => $expiresAtTimestamp,
                ];

                // URL absolut aman dengan validasi host whitelist untuk mencegah Password Reset Poisoning
                $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
                    || (isset($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https'));
                $protocol = $isHttps ? 'https://' : 'http://';
                $host = get_app_canonical_host();
                $resetLink = $protocol . $host . $appUrlPath . 'pages/reset-password.php?token=' . urlencode($token);

                // Kirim email
                $mailResult = send_reset_password_mail($email, $resetLink, $recipientName);

                if ($mailResult['success']) {
                    $successMessage = 'Tautan pemulihan kata sandi telah dikirim ke <strong>' . htmlspecialchars($email) . '</strong>. Silakan periksa kotak masuk atau folder spam email Anda (berlaku selama 15 menit).';
                } else {
                    error_log('Mailer error: ' . $mailResult['message']);
                    if (is_local()) {
                        error_log('[DEV TESTING RESET LINK]: ' . $resetLink);
                        $errorMessage = 'Gagal mengirim email: ' . $mailResult['message'];
                    } else {
                        $errorMessage = 'Layanan email sedang mengalami gangguan. Silakan hubungi administrator sistem.';
                    }
                }
            } else {
                // Akun tidak ditemukan
                if (is_local()) {
                    $errorMessage = 'Akun dengan email / username "' . htmlspecialchars($email) . '" tidak ditemukan di database.';
                } else {
                    // Mencegah enumerasi akun di mode produksi
                    $successMessage = $standardSuccessMsg;
                }
            }
        }
    }

    if (is_ajax_request()) {
        if (!empty($errorMessage)) {
            json_error($errorMessage);
        } else {
            json_success($successMessage, ['email' => $email ?? '']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Lupa Password - Sistem Informasi Inventaris Aset SDN Budi Karya">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(get_csrf_token()); ?>">
    <title>Lupa Kata Sandi - SDN Budi Karya</title>

    <!-- Favicon -->
    <link rel="icon" type="image/webp" href="<?php echo htmlspecialchars($logoSrc); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($logoSrc); ?>">

    <!-- Preload LCP Resources & Primary Fonts -->
    <link rel="preload" as="image" href="<?php echo htmlspecialchars($logoSrc); ?>" fetchpriority="high">
    <link rel="preload" as="image" href="<?php echo htmlspecialchars($bgSchoolUrl); ?>" fetchpriority="high">
    <link rel="preload" as="font" type="font/woff2" href="<?php echo htmlspecialchars($assetsPath . 'fonts/plus-jakarta-sans-600.woff2'); ?>" crossorigin>
    <link rel="preload" as="font" type="font/woff2" href="<?php echo htmlspecialchars($assetsPath . 'fonts/plus-jakarta-sans-700.woff2'); ?>" crossorigin>

    <!-- Self-Hosted Core Fonts & Lupa Password Stylesheet -->
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/fonts.css'); ?>">
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/lupa-password.css'); ?>">

    <!-- Asynchronous Non-blocking Stylesheets (Phosphor & SweetAlert) -->
    <link rel="preload" href="<?php echo htmlspecialchars($assetsPath . 'css/phosphor.css'); ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="<?php echo htmlspecialchars($assetsPath . 'css/sweetalert-theme.css'); ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($assetsPath . 'css/phosphor.css'); ?>">
        <link rel="stylesheet" href="<?php echo htmlspecialchars($assetsPath . 'css/sweetalert-theme.css'); ?>">
    </noscript>
</head>
<body>

<div class="forgot-page-wrapper" style="--bg-school-image: url('<?php echo htmlspecialchars($bgSchoolUrl); ?>');">
    <!-- Tombol Kembali ke Halaman Login di Kiri Atas -->
    <a href="<?php echo htmlspecialchars($loginBackUrl); ?>" class="forgot-btn-back" title="Kembali ke Halaman Login">
        <i class="ph-bold ph-arrow-left"></i>
        <span>Kembali ke Login</span>
    </a>

    <!-- Card Lupa Password -->
    <main class="forgot-card">
        <div class="forgot-header">
            <div class="forgot-icon-box">
                <i class="ph-bold ph-lock-key-open"></i>
            </div>
            <h1 class="forgot-title">Lupa Kata Sandi?</h1>
            <p class="forgot-desc">
                Masukkan alamat email yang terdaftar pada akun Anda. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi.
            </p>
        </div>

        <!-- Form Kirim Tautan -->
        <form action="lupa-password.php?role=<?php echo htmlspecialchars($roleParam); ?>" method="POST">
            <?php echo csrf_input(); ?>

            <div class="forgot-form-group">
                <label for="email" class="forgot-form-label">Alamat Email</label>
                <div class="forgot-input-wrapper">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="forgot-form-input"
                        placeholder="contoh@sdnbudikarya.sch.id"
                        required
                        autocomplete="email"
                        autofocus
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    >
                    <i class="ph-bold ph-envelope-simple forgot-input-icon"></i>
                </div>
            </div>

            <button type="submit" class="forgot-btn-submit">
                <span>Kirim Tautan</span>
                <i class="ph-bold ph-paper-plane-tilt"></i>
            </button>
        </form>
    </main>
</div>

<!-- SweetAlert2 Library & Custom Config (Deferred to avoid blocking main thread) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
<script src="<?php echo htmlspecialchars($assetsPath . 'js/sweetalert-init.js'); ?>" defer></script>
<script src="<?php echo htmlspecialchars($assetsPath . 'js/ajax-helper.js'); ?>" defer></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action*="lupa-password.php"]');
    if (form && window.SDNAjax) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            SDNAjax.submitForm(this, {
                loadingText: 'Mengirim tautan...',
                errorTitle: 'Permintaan Gagal',
                onSuccess: (res) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Tautan Terkirim!',
                        html: res.message || 'Tautan reset kata sandi telah dikirim ke email Anda jika terdaftar.',
                        confirmButtonColor: '#852019',
                        confirmButtonText: 'Selesai'
                    }).then(() => {
                        const emailInput = document.getElementById('email');
                        if (emailInput) emailInput.value = '';
                    });
                },
                onError: (res) => {
                    SDNAlert.error('Permintaan Gagal', res.message || 'Terjadi kesalahan saat memproses permintaan.');
                }
            });
        });
    }
});
</script>

<?php if (!empty($successMessage)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        SDNAlert.success('Tautan Terkirim!', <?php echo json_encode($successMessage); ?>);
    });
</script>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        SDNAlert.error('Permintaan Gagal', <?php echo json_encode($errorMessage); ?>);
    });
</script>
<?php endif; ?>

</body>
</html>
