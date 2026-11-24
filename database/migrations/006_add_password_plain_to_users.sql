-- Migrasi 006: Menambahkan kolom password_plain pada tabel users agar admin dapat melihat password akun
ALTER TABLE users ADD COLUMN password_plain VARCHAR(255) DEFAULT NULL AFTER password;

-- Set nilai default password_plain untuk akun awal yang ada
UPDATE users SET password_plain = 'admin123' WHERE username = 'admin';
UPDATE users SET password_plain = 'operator123' WHERE username = 'operator';
UPDATE users SET password_plain = 'Password123!' WHERE password_plain IS NULL;
