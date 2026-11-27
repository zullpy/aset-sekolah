-- Migrasi 013: Menghapus kolom password_plain demi keamanan kredensial pengguna
ALTER TABLE users DROP COLUMN password_plain;
