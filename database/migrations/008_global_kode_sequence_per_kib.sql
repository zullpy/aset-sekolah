-- Migrasi 008: Gunakan nomor urut global per jenis KIB untuk Kode Aset (KIB-X/YYYY/NNN)
-- Agar nomor urut tidak kembali ke 001 saat berganti tahun perolehan.

INSERT INTO kode_counter (kib, tahun, last_no)
SELECT 'A', 0, GREATEST(
    COALESCE((SELECT MAX(last_no) FROM (SELECT last_no FROM kode_counter WHERE kib = 'A') kc), 0),
    COALESCE((SELECT MAX(CAST(SUBSTRING_INDEX(kode, '/', -1) AS UNSIGNED)) FROM kib_a WHERE kode REGEXP '^KIB-A/[0-9]{4}/[0-9]+$'), 0)
)
ON DUPLICATE KEY UPDATE last_no = GREATEST(kode_counter.last_no, VALUES(last_no));

INSERT INTO kode_counter (kib, tahun, last_no)
SELECT 'B', 0, GREATEST(
    COALESCE((SELECT MAX(last_no) FROM (SELECT last_no FROM kode_counter WHERE kib = 'B') kc), 0),
    COALESCE((SELECT MAX(CAST(SUBSTRING_INDEX(kode, '/', -1) AS UNSIGNED)) FROM kib_b WHERE kode REGEXP '^KIB-B/[0-9]{4}/[0-9]+$'), 0)
)
ON DUPLICATE KEY UPDATE last_no = GREATEST(kode_counter.last_no, VALUES(last_no));

INSERT INTO kode_counter (kib, tahun, last_no)
SELECT 'C', 0, GREATEST(
    COALESCE((SELECT MAX(last_no) FROM (SELECT last_no FROM kode_counter WHERE kib = 'C') kc), 0),
    COALESCE((SELECT MAX(CAST(SUBSTRING_INDEX(kode, '/', -1) AS UNSIGNED)) FROM kib_c WHERE kode REGEXP '^KIB-C/[0-9]{4}/[0-9]+$'), 0)
)
ON DUPLICATE KEY UPDATE last_no = GREATEST(kode_counter.last_no, VALUES(last_no));

INSERT INTO kode_counter (kib, tahun, last_no)
SELECT 'D', 0, GREATEST(
    COALESCE((SELECT MAX(last_no) FROM (SELECT last_no FROM kode_counter WHERE kib = 'D') kc), 0),
    COALESCE((SELECT MAX(CAST(SUBSTRING_INDEX(kode, '/', -1) AS UNSIGNED)) FROM kib_d WHERE kode REGEXP '^KIB-D/[0-9]{4}/[0-9]+$'), 0)
)
ON DUPLICATE KEY UPDATE last_no = GREATEST(kode_counter.last_no, VALUES(last_no));

INSERT INTO kode_counter (kib, tahun, last_no)
SELECT 'E', 0, GREATEST(
    COALESCE((SELECT MAX(last_no) FROM (SELECT last_no FROM kode_counter WHERE kib = 'E') kc), 0),
    COALESCE((SELECT MAX(CAST(SUBSTRING_INDEX(kode, '/', -1) AS UNSIGNED)) FROM kib_e WHERE kode REGEXP '^KIB-E/[0-9]{4}/[0-9]+$'), 0)
)
ON DUPLICATE KEY UPDATE last_no = GREATEST(kode_counter.last_no, VALUES(last_no));

INSERT INTO kode_counter (kib, tahun, last_no)
SELECT 'F', 0, GREATEST(
    COALESCE((SELECT MAX(last_no) FROM (SELECT last_no FROM kode_counter WHERE kib = 'F') kc), 0),
    COALESCE((SELECT MAX(CAST(SUBSTRING_INDEX(kode, '/', -1) AS UNSIGNED)) FROM kib_f WHERE kode REGEXP '^KIB-F/[0-9]{4}/[0-9]+$'), 0)
)
ON DUPLICATE KEY UPDATE last_no = GREATEST(kode_counter.last_no, VALUES(last_no));
