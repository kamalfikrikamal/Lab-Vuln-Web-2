# Lab Web 2 - TeknoBantu

Lab web pentest kedua: portal helpdesk IT internal fiktif "TeknoBantu". Berbeda dari
Lab Web 1 (OVA VirtualBox), lab ini didistribusikan sebagai **Docker Compose**.

## Menjalankan Lab

```bash
docker compose up --build
```

Aplikasi tersedia di `http://localhost:8083`.

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
