-- Migrasi 009: Menambahkan kolom penempatan pada tabel aset KIB A-F dan membuat tabel monitoring_aset

ALTER TABLE kib_a
    ADD COLUMN penempatan ENUM('kelas-1','kelas-2','kelas-3','kelas-4','kelas-5','kelas-6','umum') NOT NULL DEFAULT 'umum' AFTER no_reg,
    ADD INDEX idx_kib_a_penempatan (penempatan);

ALTER TABLE kib_b
    ADD COLUMN penempatan ENUM('kelas-1','kelas-2','kelas-3','kelas-4','kelas-5','kelas-6','umum') NOT NULL DEFAULT 'umum' AFTER no_reg,
    ADD INDEX idx_kib_b_penempatan (penempatan);

ALTER TABLE kib_c
    ADD COLUMN penempatan ENUM('kelas-1','kelas-2','kelas-3','kelas-4','kelas-5','kelas-6','umum') NOT NULL DEFAULT 'umum' AFTER no_reg,
    ADD INDEX idx_kib_c_penempatan (penempatan);

ALTER TABLE kib_d
    ADD COLUMN penempatan ENUM('kelas-1','kelas-2','kelas-3','kelas-4','kelas-5','kelas-6','umum') NOT NULL DEFAULT 'umum' AFTER no_reg,
    ADD INDEX idx_kib_d_penempatan (penempatan);

ALTER TABLE kib_e
    ADD COLUMN penempatan ENUM('kelas-1','kelas-2','kelas-3','kelas-4','kelas-5','kelas-6','umum') NOT NULL DEFAULT 'umum' AFTER no_reg,
    ADD INDEX idx_kib_e_penempatan (penempatan);

ALTER TABLE kib_f
    ADD COLUMN penempatan ENUM('kelas-1','kelas-2','kelas-3','kelas-4','kelas-5','kelas-6','umum') NOT NULL DEFAULT 'umum' AFTER no_reg,
    ADD INDEX idx_kib_f_penempatan (penempatan);

CREATE TABLE IF NOT EXISTS monitoring_aset (
    id INT AUTO_INCREMENT PRIMARY KEY,
    penempatan ENUM('kelas-1','kelas-2','kelas-3','kelas-4','kelas-5','kelas-6','umum') NOT NULL DEFAULT 'umum',
    kib ENUM('kib-a','kib-b','kib-c','kib-d','kib-e','kib-f') NOT NULL,
    aset_id INT UNSIGNED NOT NULL,
    kode_aset VARCHAR(100) NOT NULL DEFAULT '',
    nama_aset VARCHAR(255) NOT NULL DEFAULT '',
    jenis_aset VARCHAR(100) NOT NULL DEFAULT '',
    jumlah INT UNSIGNED NOT NULL DEFAULT 1,
    kondisi ENUM('Baik','Rusak Ringan','Rusak Berat','Hilang') NOT NULL DEFAULT 'Baik',
    catatan TEXT,
    petugas VARCHAR(150) NOT NULL DEFAULT '',
    tgl_update DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_monitoring_penempatan (penempatan),
    INDEX idx_monitoring_kib_aset (kib, aset_id),
    INDEX idx_monitoring_kondisi (kondisi),
    INDEX idx_monitoring_tgl_update (tgl_update)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
