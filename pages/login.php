<?php
/**
 * Halaman Login Sistem Informasi Inventaris Aset SDN Budi Karya
 * Lokasi: pages/login.php
 * Mendukung Peran: Administrator & Operator
 * Layout: Split Card 2 Kolom (Admin: Teks Kiri, Form Kanan | Operator: Form Kiri, Teks Kanan)
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/ajax.php';

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

// Tentukan Peran yang Dipilih (Default: Administrator)
$roleParam = strtolower($_GET['role'] ?? $_POST['role'] ?? 'administrator');
$isOperator = ($roleParam === 'operator');
$roleKey    = $isOperator ? 'operator' : 'administrator';
$roleTitle  = $isOperator ? 'Operator' : 'Administrator';

// Kredensial & Logika Autentikasi
$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validasi Keamanan Token CSRF
    if (!verify_csrf_token()) {
        $errorMessage = 'Sesi atau token keamanan (CSRF) tidak valid. Silakan muat ulang halaman.';
        if (is_ajax_request()) {
            json_error($errorMessage, [], 403);
        }
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $errorMessage = 'Silakan masukkan username/email dan kata sandi Anda.';
        } else {
            $authenticated = false;
            $userData = null;
            $connFile = dirname(__DIR__) . '/config/conn.php';

            if (file_exists($connFile)) {
                try {
                    require_once $connFile;
                    if (isset($pdo) && $pdo instanceof PDO) {
                        $checkTable = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
                        if ($checkTable) {
                            // Izinkan login dengan Username ATAU Email (case-insensitive & trimmed)
                            $stmt = $pdo->prepare("SELECT * FROM users WHERE (LOWER(TRIM(username)) = LOWER(TRIM(?)) OR LOWER(TRIM(email)) = LOWER(TRIM(?))) AND LOWER(role) = ? LIMIT 1");
                            $stmt->execute([$username, $username, $roleKey]);
                            $userData = $stmt->fetch();

                            // Jika tidak ditemukan di portal role saat ini, cari apakah akun terdaftar di peran lain
                            if (!$userData) {
                                $stmtAny = $pdo->prepare("SELECT * FROM users WHERE (LOWER(TRIM(username)) = LOWER(TRIM(?)) OR LOWER(TRIM(email)) = LOWER(TRIM(?))) LIMIT 1");
                                $stmtAny->execute([$username, $username]);
                                $foundAny = $stmtAny->fetch();

                                if ($foundAny && password_verify($password, $foundAny['password'])) {
                                    $userData = $foundAny;
                                    $authenticated = true;
                                    $actualRole = strtolower($userData['role'] ?? $roleKey);
                                    $isOperator = ($actualRole === 'operator');
                                    $roleKey    = $actualRole;
                                    $roleTitle  = $isOperator ? 'Operator' : 'Administrator';
                                }
                            } else {
                                if (password_verify($password, $userData['password'])) {
                                    $authenticated = true;
                                }
                            }
                        }
                    }
                } catch (Throwable $e) {
                    error_log('Login DB check error: ' . $e->getMessage());
                    $errorMessage = 'Terjadi gangguan saat menghubungkan ke database sistem inventaris.';
                }
            } else {
                $errorMessage = 'Konfigurasi database server belum terpasang.';
            }

            if ($authenticated && $userData) {
                // Cek status keaktifan akun pengguna
                if (isset($userData['status']) && strtolower($userData['status']) === 'nonaktif') {
                    $errorMessage = 'Akun Anda sedang dinonaktifkan. Silakan hubungi Administrator.';
                } else {
                    // Regenerasi ID Sesi secara ketat untuk mencegah Session Fixation
                    session_regenerate_id(true);

                    $_SESSION['user_id']      = $userData['id'];
                    $_SESSION['username']     = $userData['username'] ?? $username;
                    $_SESSION['nama_lengkap'] = $userData['nama_lengkap'] ?? $userData['username'] ?? $username;
                    $_SESSION['role']         = strtolower($userData['role'] ?? ($isOperator ? 'operator' : 'administrator'));
                    $_SESSION['role_display'] = $roleTitle;

                    // Alihkan sesuai peran: Operator langsung ke Data Aset Kelas, Administrator ke Dashboard
                    if ($isOperator) {
                        $targetRedirect = file_exists(__DIR__ . '/aset-kelas.php') ? 'aset-kelas.php' : '../index.php';
                    } else {
                        $targetRedirect = file_exists(__DIR__ . '/dashboard.php') ? 'dashboard.php' : '../index.php';
                    }

                    if (is_ajax_request()) {
                        json_success('Login berhasil! Mengalihkan ke halaman dashboard...', ['redirect' => $targetRedirect]);
                    }

                    header("Location: " . $targetRedirect);
                    exit;
                }
            } elseif (empty($errorMessage)) {
                $errorMessage = 'Username atau kata sandi tidak sesuai untuk peran ' . htmlspecialchars($roleTitle) . '.';
            }
        }
    }

    if (is_ajax_request() && !empty($errorMessage)) {
        json_error($errorMessage);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Masuk ke Sistem Informasi Inventaris Aset SDN Budi Karya sebagai <?php echo htmlspecialchars($roleTitle); ?>">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(get_csrf_token()); ?>">
    <title>Login <?php echo htmlspecialchars($roleTitle); ?> - SDN Budikarya</title>

    <!-- Favicon -->
    <link rel="icon" type="image/webp" href="<?php echo htmlspecialchars($logoSrc); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($logoSrc); ?>">

    <!-- Google Fonts & Phosphor Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/bold/style.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css"/>

    <!-- Stylesheet Khusus Login & SweetAlert2 Theme -->
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/login.css'); ?>">
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/sweetalert-theme.css'); ?>">
</head>
<body>

<div class="login-page-wrapper" style="--bg-school-image: url('<?php echo htmlspecialchars($bgSchoolUrl); ?>');">
    <!-- Tombol Kembali ke Pemilihan Role di Kiri Atas -->
    <a href="../index.php" class="login-btn-back" title="Kembali ke Halaman Pemilihan Peran">
        <i class="ph-bold ph-arrow-left"></i>
        <span>Pilih Peran</span>
    </a>

    <!-- Container Card Terbagi 2 (Admin: Teks Kiri Form Kanan | Operator: Form Kiri Teks Kanan) -->
    <div class="login-card-container <?php echo $isOperator ? 'role-operator' : 'role-admin'; ?>">

        <!-- ================= PANEL INFORMASI & KALIMAT PERAN ================= -->
        <div class="login-info-panel">
            <div class="login-info-header">
                <div class="login-info-logo-box">
                    <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="Logo SDN Budi Karya" class="login-info-logo-img" width="40" height="40">
                </div>
                <div class="login-info-school">
                    <span class="login-info-school-title">SDN Budikarya</span>
                    <span class="login-info-school-sub">Sistem Inventaris Aset</span>
                </div>
            </div>

            <div class="login-info-body">
                <div class="login-role-badge">
                    <i class="ph-bold <?php echo $isOperator ? 'ph-identification-card' : 'ph-shield-check'; ?>"></i>
                    <span>Portal <?php echo htmlspecialchars($roleTitle); ?></span>
                </div>

                <?php if ($isOperator): ?>
                    <!-- Kalimat & Poin Peran Operator -->
                    <h1 class="login-role-headline">Pencatatan & Pemantauan Aset Sekolah</h1>
                    <p class="login-role-paragraph">
                        Masuk sebagai Operator untuk melakukan inventarisasi berkala di ruang kelas, memeriksa kondisi barang fisik, serta mendata kebutuhan pemeliharaan sarana dan prasarana sekolah.
                    </p>
                <?php else: ?>
                    <!-- Kalimat & Poin Peran Administrator -->
                    <h1 class="login-role-headline">Kelola Seluruh Aset & Tata Kelola Pengguna</h1>
                    <p class="login-role-paragraph">
                        Selamat datang di portal Administrator. Dapatkan kendali penuh atas manajemen master data aset KIB A s/d F, verifikasi riwayat pemeliharaan, serta pencetakan laporan inventaris resmi sekolah.
                    </p>
                <?php endif; ?>
            </div>

            <div class="login-info-footer">
                &copy; <?php echo date('Y'); ?> SDN Budikarya. Hak Cipta Dilindungi.
            </div>
        </div>

        <!-- ================= PANEL FORM LOGIN ================= -->
        <div class="login-form-panel">
            <div class="login-form-header">
                <h2 class="login-form-greeting">Masuk Akun</h2>
                <p class="login-form-sub">Masukkan username dan kata sandi Anda sebagai <?php echo htmlspecialchars($roleTitle); ?></p>
            </div>

            <!-- Form Autentikasi -->
            <form action="login.php?role=<?php echo htmlspecialchars($roleKey); ?>" method="POST" autocomplete="on">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="role" value="<?php echo htmlspecialchars($roleKey); ?>">

                <!-- Field Username atau Email -->
                <div class="login-form-group">
                    <label for="username" class="login-form-label">Username atau Email</label>
                    <div class="login-input-wrapper">
                        <input type="text" id="username" name="username" class="login-form-input" placeholder="Masukkan username atau email" required autocomplete="username" autofocus value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        <i class="ph-bold ph-user login-input-icon"></i>
                    </div>
                </div>

                <!-- Field Password -->
                <div class="login-form-group">
                    <div class="login-label-row">
                        <label for="password" class="login-form-label">Kata Sandi</label>
                        <a href="lupa-password.php?role=<?php echo htmlspecialchars($roleKey); ?>" class="login-forgot-link">Lupa kata sandi?</a>
                    </div>
                    <div class="login-input-wrapper">
                        <input type="password" id="password" name="password" class="login-form-input" placeholder="Masukkan kata sandi" required autocomplete="current-password">
                        <i class="ph-bold ph-lock-key login-input-icon"></i>
                        <button type="button" class="login-toggle-password" id="togglePasswordBtn" aria-label="Lihat kata sandi" onclick="togglePasswordVisibility()">
                            <i class="ph-bold ph-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Tombol Submit -->
                <button type="submit" class="login-btn-submit">
                    <span>Masuk sebagai <?php echo htmlspecialchars($roleTitle); ?></span>
                    <i class="ph-bold ph-sign-in"></i>
                </button>
            </form>

        </div>

    </div>
</div>

<!-- SweetAlert2 Library & Custom Config -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?php echo htmlspecialchars($assetsPath . 'js/sweetalert-init.js'); ?>"></script>
<script src="<?php echo htmlspecialchars($assetsPath . 'js/ajax-helper.js'); ?>"></script>

<!-- Login Page Interactions -->
<script src="<?php echo htmlspecialchars($assetsPath . 'js/login.js'); ?>" defer></script>

<?php if (!empty($errorMessage)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        SDNAlert.error('Gagal Masuk', <?php echo json_encode($errorMessage); ?>);
    });
</script>
<?php endif; ?>

</body>
</html>
