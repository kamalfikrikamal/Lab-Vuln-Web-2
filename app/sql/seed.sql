-- Akun demo staff untuk keperluan pembuktian dampak (mis. XSS), bukan bagian dari
-- jalur eksploitasi utama. Kredensial ini didokumentasikan untuk peserta di README.md.
-- username: demo.staff
-- password: DemoStaff123!
INSERT INTO staff (username, password_hash, nama_lengkap, role) VALUES
('demo.staff', '$2y$12$/TDyeaVw6Wg.ekVeuirTE.DAvVOsTaD9yWhFgLQCwtiorGjpxfJJ2', 'Rina Kartika (Demo Staff)', 'staff');

INSERT INTO tickets (nama, email, subjek, deskripsi, status) VALUES
('Budi Santoso', 'budi.santoso@example.com', 'Laptop tidak bisa connect WiFi', 'Halo tim IT, laptop saya (Dell Latitude) tidak bisa konek ke WiFi kantor sejak pagi ini. Sudah dicoba restart tapi tetap sama. Mohon bantuannya.', 'diproses'),
('Siti Marlina', 'siti.marlina@example.com', 'Request akses folder shared Finance', 'Mohon dibuatkan akses ke folder shared \\\\fileserver\\Finance untuk keperluan laporan bulanan. Terima kasih.', 'baru');
