# Lab Web 2 - TeknoBantu

Lab web pentest kedua: portal helpdesk IT internal fiktif "TeknoBantu". Lab ini
didistribusikan sebagai **Docker Compose** (jalankan langsung di laptop peserta), dan
juga tersedia sebagai **OVA (VirtualBox)** untuk skenario yang butuh VM mandiri dengan
IP sendiri tanpa peserta perlu install Docker.

## Menjalankan Lab (Docker Compose)

```bash
docker compose up --build
```

Aplikasi tersedia di `http://localhost:8083`.

## Menjalankan Lab (OVA / VirtualBox)

1. Buka VirtualBox > **File > Import Appliance**, pilih file `teknobantu.ova` yang
   diterima dari instruktur.
2. Saat proses import, VirtualBox akan meminta memilih interface jaringan fisik untuk
   adapter kedua (bridged) - pilih interface yang terhubung ke jaringan yang sama dengan
   laptop Anda.
3. Nyalakan VM. Begitu boot selesai, layar console VM (**tanpa perlu login**) akan
   menampilkan IP dan URL lab, contoh:
   ```
   === TeknoBantu Lab ===
   Lab URL: http://192.168.1.50:8083
   =======================
   ```
4. Buka URL tersebut di browser pada laptop Anda.

Jika adapter jaringan VM menampilkan "Not attached" setelah import, buka VM Settings >
Network > Adapter 2 dan pilih ulang jenis adapter (bridged) secara manual, lalu nyalakan
ulang VM. Panduan build OVA untuk maintainer ada di `docs/OVA_BUILD.md`.

## Akun Demo Staff

Disediakan khusus untuk peserta membuktikan dampak salah satu temuan (bukan bagian dari
jalur eksploitasi utama):

- URL: `http://localhost:8083/staff/login.php`
- Username: `demo.staff`
- Password: `DemoStaff123!`

## Lingkup Pengujian

Peserta diminta menemukan dan melaporkan kerentanan pada aplikasi web ini, termasuk
kemungkinan eskalasi hak akses di dalam container `web` (bukan host). Dokumentasi
lengkap (kunci jawaban) ada di `docs/` - hanya untuk instruktur.

Sebagai bukti bahwa peserta benar-benar mencapai akses root, ada file flag di home
root (`/root/flag.txt`) - langsung terlihat lewat `ls /root/`, tidak perlu dicari.

## Reset Lab

```bash
docker compose down -v   # hapus container + volume database, mulai dari state awal
```
