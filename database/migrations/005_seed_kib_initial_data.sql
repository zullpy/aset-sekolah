-- Migrasi 005: Memasukkan dataset awal KIB A s/d KIB F dan aktivitas awal ke database
INSERT INTO kib_a (kode, nama, no_reg, luas, tahun, lokasi, status_tanah, sertifikat, penggunaan, sumber, harga, lampiran)
VALUES
('KIB-A/1997/001', 'Tanah Bangunan Sekolah', '000001', '1.500', '1997', 'Kp. Cilaku 012/004 Ds. Nangerang Kec. Cigalontang', 'Hak Pakai', '00021/Nangerang/1997', 'Bangunan Sekolah SDN Budi Karya', 'APBD', 'Rp 250.000.000', 'Sertifikat_Tanah_1997.pdf'),
('KIB-A/1997/002', 'Tanah Lapangan Olahraga & Upacara', '000002', '350', '1997', 'Kp. Cilaku 012/004 Ds. Nangerang Kec. Cigalontang', 'Hak Pakai', '00022/Nangerang/1997', 'Lapangan Olahraga & Upacara', 'APBD', 'Rp 70.000.000', 'Sertifikat_Lapangan.pdf'),
('KIB-A/2005/003', 'Tanah Kebun Sekolah & Penghijauan', '000003', '120', '2005', 'Sisi Barat Lingkungan SDN Budi Karya', 'Hak Pakai', '00035/Nangerang/2005', 'Kebun Praktik & Penghijauan Siswa', 'APBD', 'Rp 24.000.000', 'Sertifikat_Kebun_2005.pdf')
ON DUPLICATE KEY UPDATE
    nama = VALUES(nama), luas = VALUES(luas), tahun = VALUES(tahun), lokasi = VALUES(lokasi), harga = VALUES(harga);

INSERT INTO kib_b (kode, jenis, nama, no_reg, merk, spesifikasi, bahan, no_pabrik, tahun, jumlah, harga, sumber, lampiran)
VALUES
('KIB-B/2024/001', 'Elektronik', 'Laptop ASUS Expertbook', '000001', 'ASUS B1400', '14 Inchi / Core i5', 'Plastik & Logam', 'SN-ASUS-98124', '2024', '10 Unit', 'Rp 8.500.000', 'BOS', 'Faktur_Asus_2024.pdf'),
('KIB-B/2023/001', 'Elektronik', 'Printer Epson L3210', '000001', 'EcoTank L3210', 'A4 / Inkjet', 'Plastik & Komponen', 'EP-L3210-4491', '2023', '2 Unit', 'Rp 2.450.000', 'BOS', 'Kuitansi_Epson.pdf'),
('KIB-B/2022/002', 'Meubelair', 'Meja Belajar Siswa', '000001', 'Olympic Wooden', '60 × 50 × 75 cm', 'Kayu Jati & MDF', '-', '2022', '45 Buah', 'Rp 350.000', 'APBD', 'BAST_Meubelair_2022.pdf'),
('KIB-B/2022/003', 'Meubelair', 'Kursi Belajar Siswa', '000002', 'Chitose Student', 'Standard SD', 'Pipa Besi & Kayu', '-', '2022', '78 Buah', 'Rp 220.000', 'APBD', 'BAST_Meubelair_2022.pdf'),
('KIB-B/2021/004', 'Alat Peraga', 'Meja Tenis Meja', '000001', 'Double Fish', '274 × 152 × 76 cm', 'Partikel Board & Besi', 'DF-TM-2021-08', '2021', '1 Set', 'Rp 4.200.000', 'BOS', 'Nota_Tenis_Meja.pdf'),
('KIB-B/2020/005', 'Elektronik', 'Proyektor InFocus IN112x', '000001', 'InFocus', '3800 Lumens SVGA', 'Komposit / Logam', 'INF-3800-221', '2020', '2 Unit', 'Rp 5.100.000', 'BOS', 'Faktur_InFocus.pdf'),
('KIB-B/2019/006', 'Elektronik', 'Komputer Desktop Guru', '000001', 'Lenovo ThinkCentre', 'Core i3 / 8GB RAM / 500GB HDD', 'Logam & Sirkuit', 'LNV-TC-5520', '2019', '3 Unit', 'Rp 6.000.000', 'BOS', 'Nota_Lenovo.pdf')
ON DUPLICATE KEY UPDATE
    nama = VALUES(nama), merk = VALUES(merk), jumlah = VALUES(jumlah), harga = VALUES(harga);

INSERT INTO kib_c (kode, nama, no_reg, konstruksi, luas, lokasi, tgl_izin, status_tanah, tahun, harga, sumber, lampiran)
VALUES
('KIB-C/1997/001', 'Ruang Kelas 1', '000001', 'Beton', '56', 'Ds. Nangerang Kec. Cigalontang', '12 Mei 1997', 'Hak Pakai', '1997', 'Rp 85.000.000', 'APBD', 'IMB_Kelas_1.pdf'),
('KIB-C/1997/002', 'Ruang Kelas 2', '000002', 'Beton', '56', 'Ds. Nangerang Kec. Cigalontang', '12 Mei 1997', 'Hak Pakai', '1997', 'Rp 85.000.000', 'APBD', 'IMB_Kelas_2.pdf'),
('KIB-C/1997/003', 'Ruang Kelas 3', '000003', 'Beton', '56', 'Ds. Nangerang Kec. Cigalontang', '12 Mei 1997', 'Hak Pakai', '1997', 'Rp 85.000.000', 'APBD', 'IMB_Kelas_3.pdf'),
('KIB-C/1997/004', 'Ruang Kelas 4', '000004', 'Beton', '56', 'Ds. Nangerang Kec. Cigalontang', '12 Mei 1997', 'Hak Pakai', '1997', 'Rp 85.000.000', 'APBD', 'IMB_Kelas_4.pdf'),
('KIB-C/1997/005', 'Ruang Kelas 5', '000005', 'Beton', '56', 'Ds. Nangerang Kec. Cigalontang', '12 Mei 1997', 'Hak Pakai', '1997', 'Rp 85.000.000', 'APBD', 'IMB_Kelas_5.pdf'),
('KIB-C/1997/006', 'Ruang Kelas 6', '000006', 'Beton', '56', 'Ds. Nangerang Kec. Cigalontang', '12 Mei 1997', 'Hak Pakai', '1997', 'Rp 85.000.000', 'APBD', 'IMB_Kelas_6.pdf')
ON DUPLICATE KEY UPDATE
    nama = VALUES(nama), luas = VALUES(luas), harga = VALUES(harga);

INSERT INTO kib_d (kode, nama, no_reg, konstruksi, ukuran, luas, tahun, dokumen, harga, sumber, lampiran)
VALUES
('KIB-D/2021/001', 'Jalan Paving Blok Halaman', '000001', 'Paving Blok', '45 × 2 m', '90', '2021', '600/012/Disdik/2021, 14 Juni 2021', 'Rp 28.500.000', 'APBD', 'SPK_Paving_2021.pdf'),
('KIB-D/2020/002', 'Jaringan Pipa Air Bersih & Sumur Bor', '000001', 'Pipa PVC & Pompa', '120 m', '-', '2020', '600/088/Sarpras/2020, 20 Des 2020', 'Rp 14.200.000', 'BOS', 'Dokumen_Sumur_2020.pdf'),
('KIB-D/2019/003', 'Parit / Drainase Terbuka', '000001', 'Pasangan Batu Kali', '60 × 0.4 m', '24', '2019', '600/005/Disdik/2019, 10 Mei 2019', 'Rp 9.800.000', 'APBD', 'BA_Parit_2019.pdf')
ON DUPLICATE KEY UPDATE
    nama = VALUES(nama), ukuran = VALUES(ukuran), harga = VALUES(harga);

INSERT INTO kib_e (kode, jenis, nama, no_reg, spesifikasi_judul, spesifikasi_pengarang, spesifikasi_penerbit, spesifikasi_tahun, tahun, jumlah, harga_satuan, harga_total, sumber, lampiran)
VALUES
('KIB-E/2023/001', 'Buku Teks Utama', 'Bahasa Indonesia: Aku Bisa!', '000001', 'Bahasa Indonesia: Aku Bisa! Kelas I', 'Sofie Dewayani', 'Pusat Perbukuan Kemendikbud', '2023', '2023', '50 Eks', 'Rp 22.000', 'Rp 1.100.000', 'BOS', 'Faktur_Buku_Indo1.pdf'),
('KIB-E/2023/002', 'Buku Teks Utama', 'IPAS: Belajar Bersama Temanmu', '000001', 'Ilmu Pengetahuan Alam dan Sosial SD Kelas IV', 'Amalia Fitri dkk.', 'Pusat Perbukuan Kemendikbudristek', '2023', '2023', '45 Eks', 'Rp 25.000', 'Rp 1.125.000', 'BOS', 'Faktur_Buku_IPAS4.pdf'),
('KIB-E/2022/003', 'Buku Pengayaan', 'Pengembaraan Timun Mas', '000001', 'Cerita Rakyat Nusantara: Timun Mas', 'Rini Kurniasih', 'Balai Pustaka', '2022', '2022', '25 Eks', 'Rp 18.000', 'Rp 450.000', 'Hibah', 'BAST_Hibah_Buku.pdf'),
('KIB-E/2022/004', 'Buku Panduan Pendidik', 'Panduan Guru Matematika SD', '000001', 'Buku Panduan Guru Matematika SD Kelas I', 'Tim Gakko Tosho', 'Kemendikbudristek', '2022', '2022', '12 Eks', 'Rp 35.000', 'Rp 420.000', 'BOS', 'Kuitansi_Buku_Guru.pdf'),
('KIB-E/2021/005', 'Buku Referensi', 'Kamus Besar Bahasa Indonesia', '000001', 'KBBI Edisi Kelima', 'Badan Pengembangan Bahasa', 'Balai Pustaka', '2021', '2021', '4 Eks', 'Rp 450.000', 'Rp 1.800.000', 'APBD', 'SPK_Kamus_2021.pdf'),
('KIB-E/2020/006', 'Buku Pengayaan', 'Ensiklopedia Cilik Sains', '000001', 'Mengenal Tata Surya dan Antariksa', 'Drs. Hendra Wardhana', 'Erlangga for Kids', '2020', '2020', '28 Eks', 'Rp 120.000', 'Rp 3.360.000', 'BOS', 'Faktur_Ensiklopedia.pdf')
ON DUPLICATE KEY UPDATE
    nama = VALUES(nama), jumlah = VALUES(jumlah), harga_total = VALUES(harga_total);

INSERT INTO kib_f (kode, jenis, nama, tingkat, beton, luas, letak_bangunan, dokumen, tgl_mulai, status_tanah, biaya, persen_fisik, status_pengerjaan, sumber, keterangan, lampiran)
VALUES
('KIB-F/2024/001', 'Pembangunan', 'Pembangunan Ruang Perpustakaan', 'Tidak', 'Beton', '56', 'Sebelah Barat Ruang Kelas 6', '600/045/Disdik/2024, 10 Maret 2024', '15 Maret 2024', 'Hak Pakai', 'Rp 120.000.000', '65%', 'Dalam Pengerjaan', 'APBD', 'Pekerjaan pasangan dinding & atap', 'Kontrak_Perpus_2024.pdf'),
('KIB-F/2023/002', 'Pembangunan', 'Pembangunan Toilet Siswa (4 Pintu)', 'Tidak', 'Beton', '24', 'Belakang Ruang Guru', '600/112/DAK/2023, 05 Juli 2023', '12 Juli 2023', 'Hak Pakai', 'Rp 45.000.000', '100%', 'Selesai', 'DAK Fisik', 'Pembangunan sanitasi sekolah telah rampung 100%', 'BAST_Toilet_2023.pdf')
ON DUPLICATE KEY UPDATE
    nama = VALUES(nama), persen_fisik = VALUES(persen_fisik), status_pengerjaan = VALUES(status_pengerjaan), biaya = VALUES(biaya);

INSERT INTO kib_activities (teks, pelaku, waktu, warna)
VALUES
('Sistem terintegrasi dengan database KIB A s/d KIB F', 'Sistem', 'Aktif', '#22c55e'),
('Data aset KIB B/2024/001 dimuat ke database', 'Administrator', 'Hari ini', '#3b82f6'),
('Data buku KIB E/2023/001 tersinkronisasi ke database', 'Administrator', 'Hari ini', '#14b8a6');
