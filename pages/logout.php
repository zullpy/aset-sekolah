<?php
/**
 * Handler & Halaman Konfirmasi Logout
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/ajax.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$redirectTarget = '../index.php';

if (!is_logged_in()) {
    if (is_ajax_request()) {
        json_success('Sesi sudah berakhir.', ['redirect' => $redirectTarget]);
    }
    header('Location: ' . $redirectTarget);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    if (!verify_csrf_token()) {
        if (is_ajax_request()) {
            json_error('Token keamanan (CSRF) tidak valid. Silakan muat ulang halaman.', [], 403);
        }
        http_response_code(403);
        exit('<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>403 - Permintaan Ditolak</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h2>403 - Permintaan Ditolak</h2><p>Token keamanan tidak valid. Silakan kembali ke halaman sebelumnya dan coba lagi.</p><a href="dashboard.php">Kembali ke Dashboard</a></body></html>');
    }

    logout_user();

    if (is_ajax_request()) {
        json_success('Anda berhasil keluar dari sistem.', ['redirect' => $redirectTarget]);
    }

    header('Location: ' . $redirectTarget);
    exit;
}

// Jika diakses lewat GET dengan token CSRF yang valid, langsung proses logout
$queryToken = $_GET['csrf_token'] ?? '';
if (is_string($queryToken) && $queryToken !== '' && verify_csrf_token($queryToken)) {
    logout_user();
    header('Location: ' . $redirectTarget);
    exit;
}

// Fallback halaman konfirmasi (non-JS / akses URL langsung) agar tetap terlindung dari Logout CSRF
$currentUser = current_user();
$displayName = $currentUser['nama_lengkap'] ?? $currentUser['username'] ?? 'Pengguna';
$logoLocalFile = dirname(__DIR__) . '/assets/logo.webp';
$logoSrc = file_exists($logoLocalFile)
    ? ('data:image/webp;base64,' . base64_encode(file_get_contents($logoLocalFile)))
    : '../assets/logo.webp';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Keluar - SDN Budikarya</title>
    <link rel="icon" type="image/webp" href="<?php echo htmlspecialchars($logoSrc); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }
        .logout-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 28px 24px;
            box-shadow: 0 18px 40px -15px rgba(15, 23, 42, 0.14);
            text-align: center;
        }
        .logout-logo {
            width: 56px;
            height: 56px;
            object-fit: contain;
            margin-bottom: 14px;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
        }
        p {
            margin: 0 0 22px;
            font-size: 14px;
            line-height: 1.55;
            color: #475569;
        }
        .logout-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .btn {
            height: 42px;
            padding: 0 18px;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
        }
        .btn-cancel {
            background: #f1f5f9;
            color: #334155;
            border-color: #cbd5e1;
        }
        .btn-cancel:hover { background: #e2e8f0; }
        .btn-confirm {
            background: #852019;
            color: #ffffff;
        }
        .btn-confirm:hover { background: #6d1c16; }
    </style>
</head>
<body>
    <div class="logout-card">
        <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="Logo SDN Budi Karya" class="logout-logo">
        <h1>Konfirmasi Keluar</h1>
        <p>Anda masuk sebagai <strong><?php echo htmlspecialchars($displayName); ?></strong>. Apakah Anda yakin ingin keluar dari sistem inventaris?</p>
        <div class="logout-actions">
            <a href="dashboard.php" class="btn btn-cancel">Batal</a>
            <form action="logout.php" method="POST" style="margin: 0;">
                <?php echo csrf_input(); ?>
                <button type="submit" class="btn btn-confirm">Ya, Keluar</button>
            </form>
        </div>
    </div>
</body>
</html>
