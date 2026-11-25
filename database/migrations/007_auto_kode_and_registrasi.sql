-- Migrasi 007: Tabel counter & index unik untuk Kode Aset dan No Registrasi otomatis

CREATE TABLE IF NOT EXISTS kode_counter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kib CHAR(1) NOT NULL,
    tahun SMALLINT UNSIGNED NOT NULL,
    last_no INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_kode_counter_kib_tahun (kib, tahun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS registrasi_counter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kib CHAR(1) NOT NULL,
    last_no INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_registrasi_counter_kib (kib)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE kib_f
    ADD COLUMN no_reg VARCHAR(50) NOT NULL DEFAULT '' AFTER nama;

-- Normalisasi no_reg agar 6 digit & bebas duplikat sebelum memasang UNIQUE index
UPDATE kib_a SET no_reg = LPAD(CAST(no_reg AS UNSIGNED), 6, '0') WHERE no_reg REGEXP '^[0-9]+$' AND LENGTH(no_reg) < 6;
SET @reg_a = (SELECT COALESCE(MAX(CAST(no_reg AS UNSIGNED)), 0) FROM kib_a WHERE no_reg REGEXP '^[0-9]+$');
UPDATE kib_a t
JOIN (
    SELECT k.id
    FROM kib_a k
    LEFT JOIN (
        SELECT MIN(id) AS keep_id
        FROM kib_a
        WHERE no_reg REGEXP '^[0-9]+$' AND no_reg <> ''
        GROUP BY CAST(no_reg AS UNSIGNED)
    ) keepers ON k.id = keepers.keep_id
    WHERE keepers.keep_id IS NULL
    ORDER BY k.id ASC
) dup ON t.id = dup.id
SET t.no_reg = LPAD(@reg_a := @reg_a + 1, 6, '0');

UPDATE kib_b SET no_reg = LPAD(CAST(no_reg AS UNSIGNED), 6, '0') WHERE no_reg REGEXP '^[0-9]+$' AND LENGTH(no_reg) < 6;
SET @reg_b = (SELECT COALESCE(MAX(CAST(no_reg AS UNSIGNED)), 0) FROM kib_b WHERE no_reg REGEXP '^[0-9]+$');
UPDATE kib_b t
JOIN (
    SELECT k.id
    FROM kib_b k
    LEFT JOIN (
        SELECT MIN(id) AS keep_id
        FROM kib_b
        WHERE no_reg REGEXP '^[0-9]+$' AND no_reg <> ''
        GROUP BY CAST(no_reg AS UNSIGNED)
    ) keepers ON k.id = keepers.keep_id
    WHERE keepers.keep_id IS NULL
    ORDER BY k.id ASC
) dup ON t.id = dup.id
SET t.no_reg = LPAD(@reg_b := @reg_b + 1, 6, '0');

UPDATE kib_c SET no_reg = LPAD(CAST(no_reg AS UNSIGNED), 6, '0') WHERE no_reg REGEXP '^[0-9]+$' AND LENGTH(no_reg) < 6;
SET @reg_c = (SELECT COALESCE(MAX(CAST(no_reg AS UNSIGNED)), 0) FROM kib_c WHERE no_reg REGEXP '^[0-9]+$');
UPDATE kib_c t
JOIN (
    SELECT k.id
    FROM kib_c k
    LEFT JOIN (
        SELECT MIN(id) AS keep_id
        FROM kib_c
        WHERE no_reg REGEXP '^[0-9]+$' AND no_reg <> ''
        GROUP BY CAST(no_reg AS UNSIGNED)
    ) keepers ON k.id = keepers.keep_id
    WHERE keepers.keep_id IS NULL
    ORDER BY k.id ASC
) dup ON t.id = dup.id
SET t.no_reg = LPAD(@reg_c := @reg_c + 1, 6, '0');

UPDATE kib_d SET no_reg = LPAD(CAST(no_reg AS UNSIGNED), 6, '0') WHERE no_reg REGEXP '^[0-9]+$' AND LENGTH(no_reg) < 6;
SET @reg_d = (SELECT COALESCE(MAX(CAST(no_reg AS UNSIGNED)), 0) FROM kib_d WHERE no_reg REGEXP '^[0-9]+$');
UPDATE kib_d t
JOIN (
    SELECT k.id
    FROM kib_d k
    LEFT JOIN (
        SELECT MIN(id) AS keep_id
        FROM kib_d
        WHERE no_reg REGEXP '^[0-9]+$' AND no_reg <> ''
        GROUP BY CAST(no_reg AS UNSIGNED)
    ) keepers ON k.id = keepers.keep_id
    WHERE keepers.keep_id IS NULL
    ORDER BY k.id ASC
) dup ON t.id = dup.id
SET t.no_reg = LPAD(@reg_d := @reg_d + 1, 6, '0');

UPDATE kib_e SET no_reg = LPAD(CAST(no_reg AS UNSIGNED), 6, '0') WHERE no_reg REGEXP '^[0-9]+$' AND LENGTH(no_reg) < 6;
SET @reg_e = (SELECT COALESCE(MAX(CAST(no_reg AS UNSIGNED)), 0) FROM kib_e WHERE no_reg REGEXP '^[0-9]+$');
UPDATE kib_e t
JOIN (
    SELECT k.id
    FROM kib_e k
    LEFT JOIN (
        SELECT MIN(id) AS keep_id
        FROM kib_e
        WHERE no_reg REGEXP '^[0-9]+$' AND no_reg <> ''
        GROUP BY CAST(no_reg AS UNSIGNED)
    ) keepers ON k.id = keepers.keep_id
    WHERE keepers.keep_id IS NULL
    ORDER BY k.id ASC
) dup ON t.id = dup.id
SET t.no_reg = LPAD(@reg_e := @reg_e + 1, 6, '0');

SET @reg_f = 0;
UPDATE kib_f t
JOIN (
    SELECT id FROM kib_f ORDER BY id ASC
) seq ON t.id = seq.id
SET t.no_reg = LPAD(@reg_f := @reg_f + 1, 6, '0');

-- Tambahkan UNIQUE index pada no_reg untuk setiap tabel KIB (kode sudah UNIQUE sejak migrasi 004)
ALTER TABLE kib_a ADD UNIQUE KEY uq_kib_a_no_reg (no_reg);
ALTER TABLE kib_b ADD UNIQUE KEY uq_kib_b_no_reg (no_reg);
ALTER TABLE kib_c ADD UNIQUE KEY uq_kib_c_no_reg (no_reg);
ALTER TABLE kib_d ADD UNIQUE KEY uq_kib_d_no_reg (no_reg);
ALTER TABLE kib_e ADD UNIQUE KEY uq_kib_e_no_reg (no_reg);
ALTER TABLE kib_f ADD UNIQUE KEY uq_kib_f_no_reg (no_reg);

-- Inisialisasi kode_counter dari data aset yang sudah ada (MAX nomor urut per KIB + tahun)
INSERT INTO kode_counter (kib, tahun, last_no)
SELECT
    'A',
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '/', 2), '/', -1) AS UNSIGNED) AS tahun,
    MAX(CAST(SUBSTRING_INDEX(kode, '/', -1) AS UNSIGNED)) AS last_no
FROM kib_a
WHERE kode REGEXP '^KIB-A/[0-9]{4}/[0-9]+$'
GROUP BY CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '/', 2), '/', -1) AS UNSIGNED)
ON DUPLICATE KEY UPDATE last_no = GREATEST(kode_counter.last_no, VALUES(last_no));

INSERT INTO kode_counter (kib, tahun, last_no)
SELECT
    'B',
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '/', 2), '/', -1) AS UNSIGNED) AS tahun,
    MAX(CAST(SUBSTRING_INDEX(kode, '/', -1) AS UNSIGNED)) AS last_no
FROM kib_b
WHERE kode REGEXP '^KIB-B/[0-9]{4}/[0-9]+$'
GROUP BY CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '/', 2), '/', -1) AS UNSIGNED)
ON DUPLICATE KEY UPDATE last_no = GREATEST(kode_counter.last_no, VALUES(last_no));

INSERT INTO kode_counter (kib, tahun, last_no)
SELECT
    'C',
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '/', 2), '/', -1) AS UNSIGNED) AS tahun,
    MAX(CAST(SUBSTRING_INDEX(kode, '/', -1) AS UNSIGNED)) AS last_no
FROM kib_c
WHERE kode REGEXP '^KIB-C/[0-9]{4}/[0-9]+$'
GROUP BY CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '/', 2), '/', -1) AS UNSIGNED)
ON DUPLICATE KEY UPDATE last_no = GREATEST(kode_counter.last_no, VALUES(last_no));

INSERT INTO kode_counter (kib, tahun, last_no)
SELECT
    'D',
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '/', 2), '/', -1) AS UNSIGNED) AS tahun,
    MAX(CAST(SUBSTRING_INDEX(kode, '/', -1) AS UNSIGNED)) AS last_no
FROM kib_d
WHERE kode REGEXP '^KIB-D/[0-9]{4}/[0-9]+$'
GROUP BY CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '/', 2), '/', -1) AS UNSIGNED)
ON DUPLICATE KEY UPDATE last_no = GREATEST(kode_counter.last_no, VALUES(last_no));

INSERT INTO kode_counter (kib, tahun, last_no)
SELECT
    'E',
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '/', 2), '/', -1) AS UNSIGNED) AS tahun,
    MAX(CAST(SUBSTRING_INDEX(kode, '/', -1) AS UNSIGNED)) AS last_no
FROM kib_e
WHERE kode REGEXP '^KIB-E/[0-9]{4}/[0-9]+$'
GROUP BY CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '/', 2), '/', -1) AS UNSIGNED)
ON DUPLICATE KEY UPDATE last_no = GREATEST(kode_counter.last_no, VALUES(last_no));

INSERT INTO kode_counter (kib, tahun, last_no)
SELECT
    'F',
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '/', 2), '/', -1) AS UNSIGNED) AS tahun,
    MAX(CAST(SUBSTRING_INDEX(kode, '/', -1) AS UNSIGNED)) AS last_no
FROM kib_f
WHERE kode REGEXP '^KIB-F/[0-9]{4}/[0-9]+$'
GROUP BY CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '/', 2), '/', -1) AS UNSIGNED)
ON DUPLICATE KEY UPDATE last_no = GREATEST(kode_counter.last_no, VALUES(last_no));

-- Inisialisasi registrasi_counter dari data aset yang sudah ada (MAX nomor registrasi per jenis KIB)
INSERT INTO registrasi_counter (kib, last_no)
SELECT 'A', COALESCE(MAX(CAST(no_reg AS UNSIGNED)), 0) FROM kib_a WHERE no_reg REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE last_no = GREATEST(registrasi_counter.last_no, VALUES(last_no));

INSERT INTO registrasi_counter (kib, last_no)
SELECT 'B', COALESCE(MAX(CAST(no_reg AS UNSIGNED)), 0) FROM kib_b WHERE no_reg REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE last_no = GREATEST(registrasi_counter.last_no, VALUES(last_no));

INSERT INTO registrasi_counter (kib, last_no)
SELECT 'C', COALESCE(MAX(CAST(no_reg AS UNSIGNED)), 0) FROM kib_c WHERE no_reg REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE last_no = GREATEST(registrasi_counter.last_no, VALUES(last_no));

INSERT INTO registrasi_counter (kib, last_no)
SELECT 'D', COALESCE(MAX(CAST(no_reg AS UNSIGNED)), 0) FROM kib_d WHERE no_reg REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE last_no = GREATEST(registrasi_counter.last_no, VALUES(last_no));

INSERT INTO registrasi_counter (kib, last_no)
SELECT 'E', COALESCE(MAX(CAST(no_reg AS UNSIGNED)), 0) FROM kib_e WHERE no_reg REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE last_no = GREATEST(registrasi_counter.last_no, VALUES(last_no));

INSERT INTO registrasi_counter (kib, last_no)
SELECT 'F', COALESCE(MAX(CAST(no_reg AS UNSIGNED)), 0) FROM kib_f WHERE no_reg REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE last_no = GREATEST(registrasi_counter.last_no, VALUES(last_no));
