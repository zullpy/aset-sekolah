-- Migrasi 010: Integrasi perlu_pemeliharaan pada monitoring_aset dan pembuatan tabel pemeliharaan

ALTER TABLE monitoring_aset
    ADD COLUMN perlu_pemeliharaan ENUM('Tidak', 'Ya') NOT NULL DEFAULT 'Tidak' AFTER kondisi,
    ADD INDEX idx_monitoring_perlu_pemeliharaan (perlu_pemeliharaan);

CREATE TABLE IF NOT EXISTS pemeliharaan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kib ENUM('kib-a', 'kib-b', 'kib-c', 'kib-d', 'kib-e', 'kib-f') NOT NULL DEFAULT 'kib-b',
    aset_id INT UNSIGNED NOT NULL,
    monitoring_id INT NULL DEFAULT NULL,
    parent_id INT UNSIGNED NULL DEFAULT NULL,
    kode_aset VARCHAR(100) NOT NULL DEFAULT '',
    nama_aset VARCHAR(255) NOT NULL DEFAULT '',
    lokasi VARCHAR(100) NOT NULL DEFAULT 'Aset Umum',
    kondisi_awal ENUM('Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang') NOT NULL DEFAULT 'Rusak Ringan',
    kondisi_akhir ENUM('Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang') NULL DEFAULT NULL,
    jumlah INT UNSIGNED NOT NULL DEFAULT 1,
    jumlah_aktif INT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM(
        'Perlu pemeliharaan',
        'Sedang ditindak lanjut',
        'Selesai ditindak lanjut',
        'Digunakan kembali',
        'Disimpan'
    ) NOT NULL DEFAULT 'Perlu pemeliharaan',
    tanggal_update DATE NOT NULL,
    dokumentasi TEXT NULL,
    created_by VARCHAR(150) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pemeliharaan_aset_id (aset_id),
    INDEX idx_pemeliharaan_kib_aset (kib, aset_id),
    INDEX idx_pemeliharaan_monitoring_id (monitoring_id),
    INDEX idx_pemeliharaan_status (status),
    INDEX idx_pemeliharaan_tanggal_update (tanggal_update)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
