# Build OVA (VirtualBox Appliance)

Panduan ini untuk maintainer yang perlu membangun (atau membangun ulang) file `.ova` dari
lab TeknoBantu. Untuk peserta yang menerima file `.ova` jadi, lihat bagian "Distribusi
OVA" di `README.md` - dokumen ini murni tentang proses build-nya.

## Cara kerja

Build otomatis lewat [Packer](https://www.packer.io/), builder `virtualbox-iso`:

1. Boot ISO Debian 12 netinst, install otomatis lewat preseed (`packer/http/preseed.cfg`).
2. Provisioning (`packer/scripts/*.sh`, urut sesuai nomor prefix): install Docker Engine,
   copy `app/`, `docker/`, `docker-compose.yml` apa adanya ke `/opt/teknobantu`, jalankan
   `docker compose build` supaya image sudah jadi (tidak perlu build/network saat boot
   pertama penerima), seed database sekali, pasang systemd service yang menjalankan
   `docker compose up -d` di setiap boot, tulis banner IP+URL ke layar console, lalu
   bersihkan (log/cache/machine-id) dan matikan SSH untuk boot berikutnya.
3. VM dimatikan, Packer meng-export jadi satu file `.ova` (built-in `format = "ova"` pada
   builder `virtualbox-iso`, tidak perlu post-processor tambahan).

Stack di dalam VM **tidak berbeda sama sekali** dari jalur distribusi Docker Compose biasa -
`app/`, `docker/`, `docker-compose.yml` di-copy apa adanya, jadi skenario kerentanan
(termasuk privesc `cap_setuid` pada `python3` dan flag di `/root/flag.txt`, keduanya
**di dalam container**, bukan di VM/OS host) tidak berubah.

## Prasyarat

- **VirtualBox** dan **Packer** (>= 1.9) terinstall di mesin build.
- Koneksi internet (download ISO Debian ~700MB + image `mysql:8.0` + paket Docker saat
  provisioning).
- Ruang disk kosong yang cukup (ISO + VM disk 20GB + hasil OVA) - sediakan minimal ~30GB.

Tidak ada setup jaringan tambahan yang perlu disiapkan di mesin build untuk adapter lab
default (**Bridged** - lihat bagian "Pilihan adapter" di bawah).

## Build

Dari root repo, buat dulu tarball `app/` + `docker/` + `docker-compose.yml` yang akan
di-upload ke VM (satu file tunggal - lihat catatan di bawah kenapa ini perlu langkah
manual, bukan diupload sebagai direktori langsung oleh Packer):

```bash
mkdir -p packer/build-context
tar czf packer/build-context/teknobantu-src.tar.gz app docker docker-compose.yml
```

Lalu:

```bash
packer init packer/teknobantu.pkr.hcl
packer validate packer/teknobantu.pkr.hcl
packer build packer/teknobantu.pkr.hcl
```

Ulangi langkah `tar czf` di atas setiap kali `app/`, `docker/`, atau `docker-compose.yml`
berubah dan sebelum build ulang - tarball tidak dibuat otomatis oleh Packer.

**Kenapa tarball, bukan upload direktori langsung?** Provisioner `file` bawaan Packer,
saat dites di kombinasi VirtualBox + komunikator SSH di mesin ini, ternyata menghilangkan
satu level subfolder pada upload direktori berlapis (`docker/web/Dockerfile` jadi
`docker/Dockerfile`; isi `app/www/` dan `app/sql/` malah tercampur rata langsung di bawah
`app/`) - menyebabkan `docker compose build` gagal karena `docker/web/Dockerfile` tidak
ketemu. Upload satu file tarball lalu `tar xzf` di dalam VM (dilakukan oleh
`10-layout-app.sh`) sepenuhnya menghindari masalah ini.

Perkiraan waktu total: **20-40 menit** (mayoritas: install Debian unattended, install
Docker, pull image `mysql:8.0`, dan zero-fill free space sebelum export - build yang
terlihat "diam" selama beberapa menit di tahap-tahap ini adalah normal, bukan hang).

Hasil: `packer/output/teknobantu/teknobantu.ova` (di-`.gitignore`, jangan di-commit ke
repo - distribusikan terpisah, mis. lewat link download).

## Pilihan adapter jaringan lab

Selain NAT (adapter 1, dipakai Packer sendiri saat build untuk akses internet), VM punya
adapter kedua khusus untuk diakses peserta:

- **`bridged` (default).** Adapter ini reliable saat proses export-ke-OVA lalu import di
  mesin lain - VirtualBox akan minta peserta memilih interface fisik saat import (biasanya
  otomatis pilih yang pertama aktif). VM langsung dapat IP dari DHCP jaringan peserta,
  tanpa setup tambahan. Trade-off: lab jadi bisa diakses siapa pun di jaringan/LAN yang
  sama dengan peserta - pertimbangkan ini kalau training berjalan di jaringan bersama.
- **`hostonly`.** Terisolasi (hanya bisa diakses dari mesin peserta sendiri), tapi ada
  bug lama VirtualBox (Oracle #22158, fixed di VirtualBox 7.1.8) yang membuat adapter
  host-only hasil export OVA kadang muncul sebagai "Not attached" saat di-import di
  VirtualBox versi lebih lama - peserta perlu perbaikan manual (VM Settings > Network >
  Adapter 2 > pilih/buat ulang host-only network). Kalau tetap memilih opsi ini, mesin
  **build** juga perlu punya host-only network `vboxnet0` dengan DHCP aktif:
  ```bash
  VBoxManage hostonlyif create
  VBoxManage dhcpserver add --ifname vboxnet0 \
    --ip 192.168.56.1 --netmask 255.255.255.0 \
    --lowerip 192.168.56.10 --upperip 192.168.56.99 --enable
  ```

Untuk build dengan host-only:

```bash
packer build -var "lab_adapter_type=hostonly" packer/teknobantu.pkr.hcl
```

## Kapan perlu rebuild

Image Docker di dalam OVA di-*bake* saat build (`docker compose build` dijalankan sekali
di dalam VM saat provisioning), **bukan** saat VM pertama kali dinyalakan oleh penerima.
Artinya: setiap kali `app/`, `docker/`, atau `docker-compose.yml` berubah, `.ova` yang
sudah didistribusikan menjadi basi dan perlu di-build ulang dari awal (tidak ada mekanisme
update inkremental).

## Kredensial bawaan

- User VM (login console): `teknobantu` / `ChangeMe123!` (bisa diubah lewat variable
  `ssh_password` di `packer/teknobantu.pkr.hcl` sebelum build). User ini punya sudo
  `NOPASSWD` - sengaja dibiarkan meski SSH sudah dimatikan, karena kalau SSH diaktifkan
  lagi secara manual untuk maintenance, akses sudo tetap dibutuhkan; ini bukan bagian dari
  permukaan serangan yang dimaksudkan (SSH mati by default).
- Password MySQL (`teknobantu_root_pw`, `teknobantu_app_pw`) sama seperti di
  `docker-compose.yml` untuk jalur distribusi Docker Compose biasa - bukan sesuatu yang
  diperkenalkan khusus oleh pipeline OVA ini.
