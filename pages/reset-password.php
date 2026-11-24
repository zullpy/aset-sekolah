<?php
/**
 * Halaman Atur Ulang Kata Sandi (Reset Password)
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 * Lokasi: pages/reset-password.php
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

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$tokenStatus = 'invalid'; // 'valid', 'expired', 'invalid', 'missing'
$targetEmail = '';
$recipientName = '';
$errorMessage = '';
$resetCompleted = false;

// Verifikasi keberadaan token
if (empty($token)) {
    $tokenStatus = 'missing';
} else {
    $tokenHash = hash('sha256', $token);

    // 1. Cek token di database jika koneksi tersedia
    $connFile = dirname(__DIR__) . '/config/conn.php';
    $dbConnected = false;

    if (file_exists($connFile)) {
        try {
            require_once $connFile;
            if (isset($pdo) && $pdo instanceof PDO) {
                $dbConnected = true;
                $checkTable = $pdo->query("SHOW TABLES LIKE 'password_resets'")->fetch();
                if ($checkTable) {
                    // Cari berdasarkan hash SHA-256 atau token mentah (kompatibilitas)
                    $stmt = $pdo->prepare("SELECT email, expires_at, (expires_at > NOW()) AS sql_valid FROM password_resets WHERE token = ? OR token = ? LIMIT 1");
                    $stmt->execute([$tokenHash, $token]);
                    $resetRow = $stmt->fetch();

                    if ($resetRow) {
                        $expiresTime = strtotime($resetRow['expires_at']);
                        $sqlValid = !empty($resetRow['sql_valid']);

                        // Token valid jika PHP timestamp belum kedaluwarsa ATAU evaluasi MySQL NOW() masih valid
                        $isValidTime = ($expiresTime !== false && $expiresTime >= time()) || $sqlValid;

                        if (!$isValidTime) {
                            $tokenStatus = 'expired';
                        } else {
                            $tokenStatus = 'valid';
                            $targetEmail = $resetRow['email'];
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('Reset password token DB error: ' . $e->getMessage());
        }
    }

    // 2. Cek token di session cache (fallback dev mode)
    if ($tokenStatus === 'invalid') {
        $cached = $_SESSION['password_reset_cache'][$tokenHash] ?? $_SESSION['password_reset_cache'][$token] ?? null;
        if ($cached) {
            if (time() > ($cached['expires_at'] ?? 0)) {
                $tokenStatus = 'expired';
            } else {
                $tokenStatus = 'valid';
                $targetEmail = $cached['email'] ?? '';
                $recipientName = $cached['name'] ?? '';
            }
        }
    }
}

// Proses submit kata sandi baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenStatus === 'valid') {
    // 1. Validasi Token Keamanan CSRF
    if (!verify_csrf_token()) {
        $errorMessage = 'Sesi atau token keamanan (CSRF) tidak valid. Silakan muat ulang halaman.';
        if (is_ajax_request()) {
            json_error($errorMessage, [], 403);
        }
    } else {
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($newPassword) || empty($confirmPassword)) {
            $errorMessage = 'Silakan isi kata sandi baru dan konfirmasi kata sandi.';
        } elseif (strlen($newPassword) < 6) {
            $errorMessage = 'Kata sandi minimal harus terdiri dari 6 karakter.';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMessage = 'Konfirmasi kata sandi tidak cocok dengan kata sandi baru.';
        } else {
            // Hash kata sandi baru dengan aman menggunakan bcrypt terstandar PHP
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updated = false;
            $matchedUser = null;
            $tokenHash = hash('sha256', $token);

            // Update di database jika tersedia
            $connFile = dirname(__DIR__) . '/config/conn.php';
            if (file_exists($connFile)) {
                try {
                    require_once $connFile;
                    if (isset($pdo) && $pdo instanceof PDO) {
                        $checkUsers = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
                        if ($checkUsers) {
                            $stmtFind = $pdo->prepare("SELECT id, username, email, role FROM users WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) OR LOWER(TRIM(username)) = LOWER(TRIM(?)) LIMIT 1");
                            $stmtFind->execute([$targetEmail, $targetEmail]);
                            $matchedUser = $stmtFind->fetch();

                            if ($matchedUser) {
                                $stmtUpdate = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                                $stmtUpdate->execute([$hashedPassword, $matchedUser['id']]);
                                $updated = true;
                            } else {
                                $errorMessage = 'Akun pengguna (' . htmlspecialchars($targetEmail) . ') tidak ditemukan di tabel pengguna database.';
                            }
                        } else {
                            $errorMessage = 'Tabel users belum dibuat di database. Silakan jalankan migrasi database.';
                        }

                        if ($updated) {
                            // Hapus token yang sudah terpakai dan semua token lama untuk email ini
                            $checkResets = $pdo->query("SHOW TABLES LIKE 'password_resets'")->fetch();
                            if ($checkResets) {
                                $stmtClean = $pdo->prepare("DELETE FROM password_resets WHERE token = ? OR token = ? OR email = ?");
                                $stmtClean->execute([$tokenHash, $token, $targetEmail]);
                            }
                        }
                    }
                } catch (Throwable $e) {
                    error_log('Gagal update password user: ' . $e->getMessage());
                    $errorMessage = 'Terjadi kesalahan sistem saat memperbarui kata sandi.';
                }
            } else {
                $errorMessage = 'Koneksi database tidak tersedia. Gagal memperbarui kata sandi.';
            }

            if ($updated) {
                // Hapus token dari session cache
                unset($_SESSION['password_reset_cache'][$tokenHash], $_SESSION['password_reset_cache'][$token]);

                // Regenerasi ID sesi untuk mencegah Session Fixation
                session_regenerate_id(true);

                $resetCompleted = true;
            }
        }
    }

    if (is_ajax_request()) {
        if ($resetCompleted) {
            $userRole = strtolower($matchedUser['role'] ?? 'administrator');
            $loginTargetUrl = 'login.php?role=' . urlencode($userRole);
            $msgDetail = !empty($matchedUser['username']) ? ' untuk akun "' . htmlspecialchars($matchedUser['username']) . '"' : '';
            json_success('Kata sandi berhasil diperbarui' . $msgDetail . '! Silakan masuk kembali menggunakan kata sandi baru Anda.', [
                'redirect' => $loginTargetUrl,
                'role'     => $userRole,
                'username' => $matchedUser['username'] ?? '',
            ]);
        } else {
            json_error(!empty($errorMessage) ? $errorMessage : 'Gagal memperbarui kata sandi. Silakan coba lagi.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Atur Ulang Kata Sandi - Sistem Informasi Inventaris Aset SDN Budi Karya">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(get_csrf_token()); ?>">
    <title>Atur Ulang Kata Sandi - SDN Budi Karya</title>

    <!-- Favicon -->
    <link rel="icon" type="image/webp" href="<?php echo htmlspecialchars($logoSrc); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($logoSrc); ?>">

    <!-- Google Fonts & Phosphor Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/bold/style.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css"/>

    <!-- Stylesheet Khusus Reset Password & SweetAlert2 Theme -->
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/reset-password.css'); ?>">
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars($assetsPath . 'css/sweetalert-theme.css'); ?>">
</head>
<body>

<div class="reset-page-wrapper" style="--bg-school-image: url('<?php echo htmlspecialchars($bgSchoolUrl); ?>');">
    <!-- Tombol Kembali ke Halaman Login di Kiri Atas -->
    <a href="login.php" class="reset-btn-back" title="Kembali ke Halaman Login">
        <i class="ph-bold ph-arrow-left"></i>
        <span>Kembali ke Login</span>
    </a>

    <!-- Card Reset Password -->
    <main class="reset-card">

        <?php if ($resetCompleted): ?>
            <!-- KONDISI 1: KATA SANDI BERHASIL DIPERBARUI -->
            <div class="reset-header">
                <div class="reset-icon-box success">
                    <i class="ph-bold ph-check"></i>
                </div>
                <h1 class="reset-title">Kata Sandi Diperbarui!</h1>
                <p class="reset-desc">
                    Kata sandi untuk akun Anda telah berhasil diubah. Sekarang Anda dapat masuk ke dalam sistem inventaris menggunakan kata sandi yang baru.
                </p>
            </div>

            <div style="margin-top: 10px;">
                <a href="login.php" class="reset-btn-submit">
                    <span>Masuk ke Sistem</span>
                    <i class="ph-bold ph-sign-in"></i>
                </a>
            </div>

        <?php elseif ($tokenStatus !== 'valid'): ?>
            <!-- KONDISI 2: TOKEN TIDAK VALID / KEDALUWARSA -->
            <div class="reset-header">
                <div class="reset-icon-box warning">
                    <i class="ph-bold ph-warning"></i>
                </div>
                <h1 class="reset-title">Tautan Tidak Berlaku</h1>
                <p class="reset-desc">
                    <?php if ($tokenStatus === 'expired'): ?>
                        Tautan pemulihan kata sandi ini telah <strong>kedaluwarsa</strong> (melebihi batas waktu 15 menit). Silakan ajukan tautan baru.
                    <?php else: ?>
                        Tautan pemulihan kata sandi tidak valid atau sudah digunakan sebelumnya. Silakan ajukan permohonan tautan baru.
                    <?php endif; ?>
                </p>
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="lupa-password.php" class="reset-btn-submit">
                    <span>Kirim Ulang Tautan Baru</span>
                    <i class="ph-bold ph-arrow-clockwise"></i>
                </a>
                <a href="login.php" class="reset-btn-secondary">
                    <span>Kembali ke Login</span>
                </a>
            </div>

        <?php else: ?>
            <!-- KONDISI 3: FORM GANTI KATA SANDI -->
            <div class="reset-header">
                <div class="reset-icon-box">
                    <i class="ph-bold ph-key"></i>
                </div>
                <h1 class="reset-title">Buat Kata Sandi Baru</h1>
                <p class="reset-desc">
                    Buat kata sandi baru yang kuat untuk akun Anda.
                </p>
                <?php if (!empty($targetEmail)): ?>
                    <div class="reset-user-badge">
                        <i class="ph-bold ph-user"></i>
                        <span><?php echo htmlspecialchars($targetEmail); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Form Reset Password -->
            <form action="reset-password.php" method="POST" id="form-reset-password">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <!-- Input Kata Sandi Baru -->
                <div class="reset-form-group">
                    <label for="new_password" class="reset-form-label">Kata Sandi Baru</label>
                    <div class="reset-input-wrapper">
                        <input
                            type="password"
                            id="new_password"
                            name="new_password"
                            class="reset-form-input"
                            placeholder="Minimal 6 karakter"
                            required
                            autofocus
                        >
                        <i class="ph-bold ph-lock-key reset-input-icon"></i>
                        <button type="button" class="reset-toggle-password" data-target="new_password" aria-label="Tampilkan kata sandi">
                            <i class="ph-bold ph-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Input Konfirmasi Kata Sandi Baru -->
                <div class="reset-form-group">
                    <label for="confirm_password" class="reset-form-label">Ulangi Kata Sandi Baru</label>
                    <div class="reset-input-wrapper">
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="reset-form-input"
                            placeholder="Ketik ulang kata sandi baru"
                            required
                        >
                        <i class="ph-bold ph-lock-key-open reset-input-icon"></i>
                        <button type="button" class="reset-toggle-password" data-target="confirm_password" aria-label="Tampilkan kata sandi">
                            <i class="ph-bold ph-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Petunjuk Keamanan Kata Sandi -->
                <div class="reset-password-hints">
                    <div class="reset-hint-item" id="hint-length">
                        <i class="ph-bold ph-circle"></i>
                        <span>Minimal 6 karakter</span>
                    </div>
                    <div class="reset-hint-item" id="hint-match">
                        <i class="ph-bold ph-circle"></i>
                        <span>Konfirmasi kata sandi cocok</span>
                    </div>
                </div>

                <button type="submit" class="reset-btn-submit">
                    <span>Simpan Kata Sandi Baru</span>
                    <i class="ph-bold ph-check"></i>
                </button>
            </form>
        <?php endif; ?>

    </main>
</div>

<!-- SweetAlert2 Library & Custom Config -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?php echo htmlspecialchars($assetsPath . 'js/sweetalert-init.js'); ?>"></script>
<script src="<?php echo htmlspecialchars($assetsPath . 'js/ajax-helper.js'); ?>"></script>

<!-- Reset Password Interactions -->
<script src="<?php echo htmlspecialchars($assetsPath . 'js/reset-password.js'); ?>" defer></script>

<?php if ($resetCompleted): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'success',
            title: 'Kata Sandi Diperbarui!',
            text: 'Kata sandi untuk akun Anda telah berhasil diubah. Silakan masuk kembali menggunakan kata sandi baru Anda.',
            confirmButtonColor: '#852019',
            confirmButtonText: 'Masuk ke Sistem'
        }).then(function() {
            window.location.href = 'login.php';
        });
    });
</script>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        SDNAlert.error('Gagal Menyimpan', <?php echo json_encode($errorMessage); ?>);
    });
</script>
<?php endif; ?>

</body>
</html>
