<?php
/**
 * Halaman Lupa Password - Sistem Informasi Inventaris Aset SDN Budi Karya
 * Lokasi: pages/lupa-password.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
$logoLocalFile  = dirname(__DIR__) . '/assets/logo.webp';
$bgLocalFile    = dirname(__DIR__) . '/assets/sekolah.webp';

// Gambar Logo Sekolah
$logoSrc = file_exists($logoLocalFile)
    ? ('data:image/webp;base64,' . base64_encode(file_get_contents($logoLocalFile)))
    : ($appUrlPath . 'assets/logo.webp');

// Gambar Background Sekolah
$bgSchoolUrl = file_exists($bgLocalFile)
    ? ('data:image/webp;base64,' . base64_encode(file_get_contents($bgLocalFile)))
    : ($appUrlPath . 'assets/sekolah.webp');

// Peran aktif sebelumnya (jika ada)
$roleParam = strtolower($_GET['role'] ?? 'administrator');
$loginBackUrl = 'login.php' . ($roleParam === 'operator' ? '?role=operator' : '?role=administrator');

// Muat konfigurasi global & mailer & ajax
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/ajax.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                        $stmt = $pdo->prepare("SELECT id, username, nama_lengkap, email FROM users WHERE email = ? LIMIT 1");
                        $stmt->execute([$email]);
                        $userFound = $stmt->fetch();
                    }
                }
            } catch (Throwable $e) {
                error_log('DB check error: ' . $e->getMessage());
            }
        }

        // Jika DB & tabel users ada namun email tidak ditemukan
        if ($dbConnected && $tableExists && !$userFound) {
            $errorMessage = 'Alamat email tidak ditemukan dalam sistem inventaris. Gunakan email akun terdaftar (contoh: admin@sdnbudikarya.sch.id atau operator@sdnbudikarya.sch.id).';
        } else {
            // Buat token acak yang aman
            $token = bin2hex(random_bytes(32));
            $recipientName = $userFound['nama_lengkap'] ?? $userFound['username'] ?? '';

            // Simpan token ke database jika koneksi tersedia
            if ($dbConnected) {
                try {
                    // Buat tabel password_resets jika belum ada
                    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        email VARCHAR(255) NOT NULL,
                        token VARCHAR(255) NOT NULL UNIQUE,
                        expires_at DATETIME NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_email (email),
                        INDEX idx_token (token)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                    // Hapus token lama untuk email ini
                    $stmtDel = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                    $stmtDel->execute([$email]);

                    // Simpan token baru berlaku selama 15 menit
                    $stmtIns = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
                    $stmtIns->execute([$email, $token]);
                } catch (Throwable $e) {
                    error_log('Gagal menyimpan password_resets: ' . $e->getMessage());
                }
            }

            // Simpan juga di session sebagai fallback (berlaku 15 menit = 900 detik)
            if (!isset($_SESSION['password_reset_cache'])) {
                $_SESSION['password_reset_cache'] = [];
            }
            $_SESSION['password_reset_cache'][$token] = [
                'email'      => $email,
                'name'       => $recipientName,
                'expires_at' => time() + 900,
            ];

            // Tentukan URL absolut link reset password
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? '') == 443) ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $resetLink = $protocol . $host . $appUrlPath . 'pages/reset-password.php?token=' . urlencode($token);

            // Kirim email menggunakan Mailer (PHPMailer / Native SMTP)
            $mailResult = send_reset_password_mail($email, $resetLink, $recipientName);

            if ($mailResult['success']) {
                $successMessage = 'Tautan pemulihan kata sandi telah berhasil dikirim ke <strong>' . htmlspecialchars($email) . '</strong>. Silakan periksa kotak masuk atau folder spam email Anda (berlaku selama 15 menit).';
            } else {
                // Tampilkan pesan status pengiriman SMTP
                $errorMessage = $mailResult['message'];

                // Di lingkungan LOCAL (development), sediakan tautan langsung untuk memudahkan pengujian
                if (is_local()) {
                    $devResetLink = $resetLink;
                }
            }
        }
    }

    if (is_ajax_request()) {
        if (!empty($successMessage)) {
            json_success($successMessage, ['email' => $email]);
        } else {
            json_error($errorMessage, ['devResetLink' => $devResetLink ?? null]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Lupa Password - Sistem Informasi Inventaris Aset SDN Budi Karya">
    <title>Lupa Kata Sandi - SDN Budi Karya</title>

    <!-- Favicon -->
    <link rel="icon" type="image/webp" href="<?php echo htmlspecialchars($logoSrc); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($logoSrc); ?>">

    <!-- Google Fonts & Phosphor Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/bold/style.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css"/>

    <!-- Stylesheet Khusus Lupa Password & SweetAlert2 Theme -->
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/lupa-password.css'); ?>">
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/sweetalert-theme.css'); ?>">
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

<!-- SweetAlert2 Library & Custom Config -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?php echo htmlspecialchars($assetsPath . 'js/sweetalert-init.js'); ?>"></script>
<script src="<?php echo htmlspecialchars($assetsPath . 'js/ajax-helper.js'); ?>"></script>

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
                        html: res.message || 'Tautan reset kata sandi telah dikirim ke email Anda.',
                        confirmButtonColor: '#852019',
                        confirmButtonText: 'Selesai'
                    }).then(() => {
                        const emailInput = document.getElementById('email');
                        if (emailInput) emailInput.value = '';
                    });
                },
                onError: (res) => {
                    if (res.data && res.data.devResetLink) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Mode Uji Coba Lokal',
                            html: res.message + '<br><br><a href="' + res.data.devResetLink + '" class="swal2-confirm swal2-styled" style="display:inline-block; text-decoration:none; margin: 0 auto; background-color: #852019;">Buka Tautan Reset Password</a>',
                            showConfirmButton: false,
                            showCloseButton: true
                        });
                    } else {
                        SDNAlert.error('Permintaan Gagal', res.message || 'Alamat email tidak ditemukan.');
                    }
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
        <?php if (!empty($devResetLink)): ?>
            Swal.fire({
                icon: 'warning',
                title: 'Mode Uji Coba Lokal',
                html: <?php echo json_encode(
                    htmlspecialchars($errorMessage) . '<br><br>' .
                    '<a href="' . htmlspecialchars($devResetLink) . '" class="swal2-confirm swal2-styled" style="display:inline-block; text-decoration:none; margin: 0 auto; background-color: #852019;">Buka Tautan Reset Password</a>'
                ); ?>,
                showConfirmButton: false,
                showCloseButton: true
            });
        <?php else: ?>
            SDNAlert.error('Permintaan Gagal', <?php echo json_encode($errorMessage); ?>);
        <?php endif; ?>
    });
</script>
<?php endif; ?>

</body>
</html>
