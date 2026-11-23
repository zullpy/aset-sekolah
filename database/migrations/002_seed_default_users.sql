-- Migrasi 002: Menambahkan akun default Administrator dan Operator

INSERT INTO users (username, nama_lengkap, email, password, role)
VALUES 
(
    'admin', 
    'Administrator Aset', 
    'mzulfahmi008@gmail.com', 
    '$2y$12$1.7KUB4cYqgrikqwmwtQSO589ZhSmhouDYMAxNUhcDImM7sggwvw.', 
    'administrator'
),
(
    'operator', 
    'Operator Inventaris', 
    'operator@sdnbudikarya.sch.id', 
    '$2y$12$9f.wUK0L81sNlRsOV3RNW.m.IEPQc6G8BbTpbAlN2wLt4s30MF/y6', 
    'operator'
)
ON DUPLICATE KEY UPDATE 
    nama_lengkap = VALUES(nama_lengkap),
    email = VALUES(email),
    role = VALUES(role);
