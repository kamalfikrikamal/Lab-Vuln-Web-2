# Arsitektur Lab TeknoBantu

## Gambaran Umum

Lab kedua ini didistribusikan sebagai **Docker Compose** (bukan OVA/VM seperti Lab 1),
berisi portal helpdesk IT internal fiktif "TeknoBantu" milik perusahaan "PT Karya Digital
Nusantara". Sama seperti Lab 1, ini adalah lab bergaya assessment (peserta melaporkan
temuan), bukan CTF flag-based.

```
Peserta (laptop mana pun, Docker terpasang)
        |
        | docker compose up --build
        v
+-------------------------------------------+
|  Host peserta                              |
|                                             |
|  +----------------+     +----------------+ |
|  |  web (Apache +  |<--->|  db (MySQL 8)  | |
|  |  mod_php,       |     |  tidak pernah  | |
|  |  Debian based)  |     |  expose port   | |
|  +--------+---------+     +----------------+ |
|           |                                 |
|  8083/tcp | (host -> container:80)          |
+-----------|---------------------------------+
            v
      http://localhost:8083
```

Berbeda dari Lab 1 (recon dulu untuk menemukan port non-standar via `nmap -p-`), di lab
ini port aplikasi (`8083`) memang dipublikasikan eksplisit oleh Docker Compose ke peserta -
fokus lab ini bukan pada recon jaringan, melainkan pada kualitas validasi input di level
aplikasi dan pada privesc berbasis Linux capabilities di level OS.

## Alur Temuan

Lihat `VULNERABILITIES.md` untuk detail & PoC. Berbeda dari Lab 1, di lab ini **tidak
ada rantai wajib** antar kerentanan web - satu-satunya keharusan adalah salah satu
kerentanan (File Upload) memang berujung ke shell `www-data`; XSS berdiri sendiri sebagai
temuan dengan dampak yang dibuktikan terpisah.

1. **Stored XSS** (`staff/ticket.php`, field `deskripsi`) - dibuktikan lewat akun demo
   staff yang disediakan khusus untuk verifikasi dampak, bukan bagian dari jalur RCE.
2. **Unrestricted File Upload** (`includes/upload.php`, field `lampiran`) - blacklist
   ekstensi hanya menolak `.php` persis (`.phtml`/`.phar` terlewat, dan keduanya
   tereksekusi sebagai PHP di stack ini) + kepercayaan pada Content-Type dari klien
   -> upload webshell -> RCE -> shell `www-data`.
3. **Privilege Escalation** - misconfigurasi Linux capabilities pada `python3`
   (`cap_setuid+ep`) -> root (di dalam container).

## Struktur Repo

```
docker-compose.yml    Definisi service web + db
docker/web/            Dockerfile (Debian + Apache + mod_php + setcap) & vhost Apache
app/www/                Source PHP yang di-COPY ke image (docroot /var/www/html)
app/sql/                Skema + seed data MySQL
docs/                   Dokumentasi (arsitektur, kunci jawaban, checklist pengujian)
```

## Keputusan Desain Kunci

- **`debian:bookworm-slim` + `apt-get install apache2 libapache2-mod-php`, bukan image
  resmi `php:apache`.** Image resmi Docker Hub `php:*-apache` dikompilasi dari source dan
  hanya memetakan `.php` ke handler PHP. Paket Debian asli (`php8.2.conf`) memetakan
  `.+\.ph(?:ar|p|tml)$` - yaitu `.phar` dan `.phtml` selain `.php` sendiri - sebagai
  bagian dari konfigurasi default `libapache2-mod-php`. Ini yang membuat celah upload
  di lab ini realistis dan bukan konfigurasi buatan kita. Ekstensi legacy seperti
  `.php3`/`.php4`/`.pht` (dari config PHP versi lama) **tidak** ikut dieksekusi di
  regex `php8.2.conf` ini - jangan sampai salah asumsi saat menulis kunci jawaban.
- **MySQL tidak pernah expose port ke host** - konsisten dengan Lab 1, mencegah DB jadi
  target langsung.
- **Tidak ada `cap_drop`/`security_opt: no-new-privileges` pada service `web`.** Ini
  krusial: kedua opsi tersebut (kalau ditambahkan sebagai "hardening" yang wajar) akan
  mematikan jalur privesc capabilities yang menjadi inti pembelajaran lab ini. Docker
  secara default sudah menyertakan `CAP_SETUID`/`CAP_SETPCAP` di bounding set container,
  cukup untuk membuat file capability `cap_setuid+ep` pada `python3` berfungsi saat
  dieksekusi oleh proses non-root (`www-data`).
- **"Root" pada lab ini adalah root di dalam container `web`, bukan root host.** Tidak
  ada langkah container escape di lab ini - container tidak berjalan `privileged`, tidak
  mount Docker socket, tidak pakai `--pid=host`/`--net=host`. Instruktur perlu menekankan
  batas ini ke peserta supaya laporan mereka tidak overclaim dampak (host compromise)
  padahal yang tercapai adalah root-in-container.
- **Login staff memakai prepared statement + `password_verify()` yang benar** - kontras
  yang disengaja dengan Lab 1, di mana staff login justru jadi target SQLi. Di lab ini,
  akses awal ke aplikasi (form tiket publik) sudah tidak perlu autentikasi sama sekali,
  jadi tidak ada kebutuhan untuk membobol login staff demi mendapatkan RCE.
