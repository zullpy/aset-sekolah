<?php
/**
 * Backend Endpoint AJAX - Kelola Akun Pengguna
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 * Lokasi: includes/ajax-akun.php
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/conn.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ajax.php';

// Pastikan hanya Administrator yang memiliki izin mengakses pengelolaan akun
require_auth('administrator');

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            $stmt = $pdo->query("SELECT id, nama_lengkap AS nama, username, email, role, status, password_plain FROM users ORDER BY id ASC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format peran agar sesuai dengan UI ('Administrator' / 'Operator')
            $users = array_map(function ($row) {
                return [
                    'id'       => (int) $row['id'],
                    'nama'     => $row['nama'],
                    'username' => $row['username'],
                    'email'    => $row['email'],
                    'password' => !empty($row['password_plain']) ? $row['password_plain'] : 'Password123!',
                    'peran'    => ($row['role'] === 'administrator') ? 'Administrator' : 'Operator',
                    'status'   => ($row['status'] === 'Nonaktif') ? 'Nonaktif' : 'Aktif',
                ];
            }, $rows);

            json_success('Data akun berhasil dimuat.', ['users' => $users]);
            break;

        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_error('Metode request tidak diizinkan.', [], 405);
            }

            if (!verify_csrf_token()) {
                json_error('Token keamanan CSRF tidak valid. Silakan muat ulang halaman.', [], 403);
            }

            $nama     = trim($_POST['nama'] ?? '');
            $username = strtolower(trim($_POST['username'] ?? ''));
            $email    = strtolower(trim($_POST['email'] ?? ''));
            $password = $_POST['password'] ?? '';
            $peran    = strtolower(trim($_POST['peran'] ?? 'operator'));
            $status   = ucfirst(strtolower(trim($_POST['status'] ?? 'Aktif')));

            if (empty($nama) || mb_strlen($nama) < 3) {
                json_error('Nama lengkap wajib diisi minimal 3 karakter.');
            }

            if (empty($username) || !preg_match('/^[a-z0-9._-]{3,50}$/', $username)) {
                json_error('Username harus 3-50 karakter (huruf, angka, titik, strip).');
            }

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                json_error('Format alamat email tidak valid.');
            }

            if (empty($password) || strlen($password) < 6) {
                json_error('Password minimal terdiri dari 6 karakter.');
            }

            $role = ($peran === 'administrator') ? 'administrator' : 'operator';
            $statusVal = ($status === 'Nonaktif') ? 'Nonaktif' : 'Aktif';

            // Cek duplikasi username
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = ? LIMIT 1");
            $stmtCheck->execute([$username]);
            if ($stmtCheck->fetch()) {
                json_error('Username sudah digunakan oleh akun lain.');
            }

            // Cek duplikasi email
            $stmtCheckEmail = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = ? LIMIT 1");
            $stmtCheckEmail->execute([$email]);
            if ($stmtCheckEmail->fetch()) {
                json_error('Alamat email sudah terdaftar.');
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $stmtInsert = $pdo->prepare("
                INSERT INTO users (username, nama_lengkap, email, password, password_plain, role, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtInsert->execute([$username, $nama, $email, $hashedPassword, $password, $role, $statusVal]);

            $newId = (int) $pdo->lastInsertId();

            json_success('Akun baru berhasil ditambahkan ke database.', [
                'user' => [
                    'id'       => $newId,
                    'nama'     => $nama,
                    'username' => $username,
                    'email'    => $email,
                    'password' => $password,
                    'peran'    => ($role === 'administrator') ? 'Administrator' : 'Operator',
                    'status'   => $statusVal,
                ]
            ]);
            break;

        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_error('Metode request tidak diizinkan.', [], 405);
            }

            if (!verify_csrf_token()) {
                json_error('Token keamanan CSRF tidak valid. Silakan muat ulang halaman.', [], 403);
            }

            $id       = (int) ($_POST['id'] ?? 0);
            $nama     = trim($_POST['nama'] ?? '');
            $username = strtolower(trim($_POST['username'] ?? ''));
            $email    = strtolower(trim($_POST['email'] ?? ''));
            $password = $_POST['password'] ?? '';
            $peran    = strtolower(trim($_POST['peran'] ?? 'operator'));
            $status   = isset($_POST['status']) ? ucfirst(strtolower(trim($_POST['status']))) : null;

            if ($id <= 0) {
                json_error('ID pengguna tidak valid.');
            }

            // Cek keberadaan akun
            $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
            $stmtUser->execute([$id]);
            $currentUser = $stmtUser->fetch();
            if (!$currentUser) {
                json_error('Data pengguna tidak ditemukan di database.');
            }

            if (empty($nama) || mb_strlen($nama) < 3) {
                json_error('Nama lengkap wajib diisi minimal 3 karakter.');
            }

            if (empty($username) || !preg_match('/^[a-z0-9._-]{3,50}$/', $username)) {
                json_error('Username harus 3-50 karakter (huruf, angka, titik, strip).');
            }

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                json_error('Format alamat email tidak valid.');
            }

            $role = ($peran === 'administrator') ? 'administrator' : 'operator';

            // Cek duplikasi username (kecuali milik sendiri)
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = ? AND id != ? LIMIT 1");
            $stmtCheck->execute([$username, $id]);
            if ($stmtCheck->fetch()) {
                json_error('Username sudah digunakan oleh akun lain.');
            }

            // Cek duplikasi email (kecuali milik sendiri)
            $stmtCheckEmail = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = ? AND id != ? LIMIT 1");
            $stmtCheckEmail->execute([$email, $id]);
            if ($stmtCheckEmail->fetch()) {
                json_error('Alamat email sudah terdaftar.');
            }

            // Update data pengguna
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    json_error('Password baru minimal terdiri dari 6 karakter.');
                }
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmtUpdate = $pdo->prepare("
                    UPDATE users
                    SET nama_lengkap = ?, username = ?, email = ?, role = ?, password = ?, password_plain = ?
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$nama, $username, $email, $role, $hashedPassword, $password, $id]);
            } else {
                $stmtUpdate = $pdo->prepare("
                    UPDATE users
                    SET nama_lengkap = ?, username = ?, email = ?, role = ?
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$nama, $username, $email, $role, $id]);
            }

            if ($status !== null) {
                $statusVal = ($status === 'Nonaktif') ? 'Nonaktif' : 'Aktif';
                $stmtStatus = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                $stmtStatus->execute([$statusVal, $id]);
            }

            json_success('Perubahan data akun berhasil disimpan.', [
                'password' => $password ?: null
            ]);
            break;

        case 'toggle_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_error('Metode request tidak diizinkan.', [], 405);
            }

            if (!verify_csrf_token()) {
                json_error('Token keamanan CSRF tidak valid. Silakan muat ulang halaman.', [], 403);
            }

            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                json_error('ID pengguna tidak valid.');
            }

            // Cegah admin menonaktifkan akunnya sendiri yang sedang aktif digunakan
            if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] === $id) {
                json_error('Anda tidak dapat menonaktifkan akun Anda sendiri yang sedang aktif digunakan.');
            }

            $stmtUser = $pdo->prepare("SELECT id, status FROM users WHERE id = ? LIMIT 1");
            $stmtUser->execute([$id]);
            $user = $stmtUser->fetch();
            if (!$user) {
                json_error('Data pengguna tidak ditemukan.');
            }

            $newStatus = ($user['status'] === 'Aktif') ? 'Nonaktif' : 'Aktif';
            $stmtUpdate = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmtUpdate->execute([$newStatus, $id]);

            json_success('Status akun berhasil diubah menjadi ' . $newStatus . '.', [
                'id'     => $id,
                'status' => $newStatus,
            ]);
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                json_error('Metode request tidak diizinkan.', [], 405);
            }

            if (!verify_csrf_token()) {
                json_error('Token keamanan CSRF tidak valid. Silakan muat ulang halaman.', [], 403);
            }

            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                json_error('ID pengguna tidak valid.');
            }

            // Cegah admin menghapus akunnya sendiri
            if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] === $id) {
                json_error('Anda tidak dapat menghapus akun Anda sendiri.');
            }

            $stmtDelete = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmtDelete->execute([$id]);

            json_success('Akun pengguna berhasil dihapus dari database.');
            break;

        default:
            json_error('Aksi tidak dikenali.', [], 400);
            break;
    }
} catch (Throwable $e) {
    error_log('AJAX Akun Error: ' . $e->getMessage());
    json_error('Terjadi kesalahan pada server database: ' . $e->getMessage(), [], 500);
}
