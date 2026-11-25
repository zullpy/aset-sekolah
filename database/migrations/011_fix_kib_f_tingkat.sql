-- Migrasi 011: Menyeragamkan nilai kolom tingkat pada kib_f dari 'Tidak' menjadi 'Tidak Bertingkat'
UPDATE kib_f
SET tingkat = 'Tidak Bertingkat'
WHERE tingkat = 'Tidak';
