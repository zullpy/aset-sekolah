-- Migrasi 012: Membuat tabel klaim_aset untuk fitur Data Aset Kelas
CREATE TABLE IF NOT EXISTS klaim_aset (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kelas_id TINYINT UNSIGNED NOT NULL,
    kib ENUM('kib-a','kib-b','kib-c','kib-d','kib-e','kib-f') NOT NULL,
    aset_id INT UNSIGNED NOT NULL,
    kode_aset VARCHAR(100) NOT NULL DEFAULT '',
    jenis_aset VARCHAR(100) NOT NULL DEFAULT '',
    jenis_kib CHAR(1) NOT NULL DEFAULT '',
    nama_aset VARCHAR(255) NOT NULL DEFAULT '',
    no_reg VARCHAR(20) NOT NULL DEFAULT '',
    spesifikasi VARCHAR(255) NOT NULL DEFAULT '',
    tahun VARCHAR(20) NOT NULL DEFAULT '',
    sumber VARCHAR(100) NOT NULL DEFAULT '',
    harga VARCHAR(100) NOT NULL DEFAULT '',
    jumlah INT UNSIGNED NOT NULL DEFAULT 1,
    user_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_klaim_kelas (kelas_id),
    INDEX idx_klaim_kib_aset (kib, aset_id),
    INDEX idx_klaim_kode (kode_aset)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
