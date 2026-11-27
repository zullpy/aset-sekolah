-- Migrasi 014: Menambahkan kolom password_enc (AES-256-GCM) untuk fitur lihat password admin yang aman
ALTER TABLE users ADD COLUMN password_enc VARCHAR(512) DEFAULT NULL AFTER password;
