FROM debian:bookworm-slim

# Stock Debian package (bukan image resmi php:apache) supaya handler PHP bawaan
# Apache (termasuk pemetaan .phar) apa adanya, bukan konfigurasi khusus kita.
# libapache2-mod-php mengaktifkan modul PHP-nya sendiri lewat postinst (a2enmod),
# termasuk FilesMatch bawaan yang memetakan .php/.phtml/.phar ke handler PHP -
# tidak ada konfigurasi tambahan yang kita suntikkan di sini.
RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        apache2 \
        libapache2-mod-php \
        php-mysql \
        python3 \
        libcap2-bin \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# "Solusi cepat" sysadmin: python3 diberi cap_setuid alih-alih systemd timer
# yang berjalan sebagai root dengan benar. Ini adalah misconfig yang disengaja
# untuk lab, bukan hardening tambahan.
RUN setcap cap_setuid+ep "$(readlink -f "$(command -v python3)")"

# Bukti bahwa peserta benar-benar mencapai root (bukan sekadar www-data) -
# langsung ada di home root, tidak disembunyikan/perlu dicari.
RUN echo "TEKNOBANTU{c4p_s3tu1d_1s_n0t_sud0}" > /root/flag.txt \
    && chmod 600 /root/flag.txt

COPY app/www/ /var/www/html/
COPY docker/web/000-teknobantu.conf /etc/apache2/sites-available/000-teknobantu.conf

RUN a2dissite 000-default \
    && a2ensite 000-teknobantu \
    && rm -f /var/www/html/index.html \
    && mkdir -p /var/www/html/uploads/tickets \
    && chown -R www-data:www-data /var/www/html/uploads

EXPOSE 80

CMD ["apache2ctl", "-D", "FOREGROUND"]
