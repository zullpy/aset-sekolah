-- Migrasi 003: Menambahkan kolom status pada tabel users dan memasukkan data akun pengguna awal
ALTER TABLE users ADD COLUMN status ENUM('Aktif', 'Nonaktif') NOT NULL DEFAULT 'Aktif' AFTER role;

INSERT INTO users (username, nama_lengkap, email, password, role, status)
VALUES
(
    'operator.sekolah',
    'Didih Maulidah, S.Pd',
    'didih.maulidah@sdnbudikarya.sch.id',
    '$2y$12$hdviTUDcb5TYyFmIE2Fap..hznBM.bVWD6ajTnlIv9.09cOko5DKm',
    'administrator',
    'Aktif'
),
(
    'guru.kelas1',
    'Nunung Nuryanti, S.Pd',
    'nunung.nuryanti@sdnbudikarya.sch.id',
    '$2y$12$hdviTUDcb5TYyFmIE2Fap..hznBM.bVWD6ajTnlIv9.09cOko5DKm',
    'operator',
    'Aktif'
),
(
    'guru.kelas2',
    'Guru kelas 2',
    'guru.kelas2@sdnbudikarya.sch.id',
    '$2y$12$hdviTUDcb5TYyFmIE2Fap..hznBM.bVWD6ajTnlIv9.09cOko5DKm',
    'operator',
    'Aktif'
),
(
    'guru.kelas3',
    'Guru kelas 3',
    'guru.kelas3@sdnbudikarya.sch.id',
    '$2y$12$hdviTUDcb5TYyFmIE2Fap..hznBM.bVWD6ajTnlIv9.09cOko5DKm',
    'operator',
    'Aktif'
),
(
    'guru.kelas4',
    'Guru kelas 4',
    'guru.kelas4@sdnbudikarya.sch.id',
    '$2y$12$hdviTUDcb5TYyFmIE2Fap..hznBM.bVWD6ajTnlIv9.09cOko5DKm',
    'operator',
    'Aktif'
),
(
    'guru.kelas5',
    'Guru kelas 5',
    'guru.kelas5@sdnbudikarya.sch.id',
    '$2y$12$hdviTUDcb5TYyFmIE2Fap..hznBM.bVWD6ajTnlIv9.09cOko5DKm',
    'operator',
    'Aktif'
),
(
    'guru.kelas6',
    'Guru kelas 6',
    'guru.kelas6@sdnbudikarya.sch.id',
    '$2y$12$hdviTUDcb5TYyFmIE2Fap..hznBM.bVWD6ajTnlIv9.09cOko5DKm',
    'operator',
    'Aktif'
),
(
    'penjaga.sekolah',
    'Penjaga sekolah',
    'penjaga@sdnbudikarya.sch.id',
    '$2y$12$hdviTUDcb5TYyFmIE2Fap..hznBM.bVWD6ajTnlIv9.09cOko5DKm',
    'operator',
    'Aktif'
)
ON DUPLICATE KEY UPDATE
    nama_lengkap = VALUES(nama_lengkap),
    email = VALUES(email),
    role = VALUES(role),
    status = VALUES(status);
