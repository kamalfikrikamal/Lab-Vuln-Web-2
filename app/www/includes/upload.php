<?php

/**
 * Simpan lampiran tiket (opsional). Mengembalikan path relatif yang disimpan
 * di DB, atau null jika tidak ada file yang diupload.
 *
 * Validasi di sini SENGAJA tidak lengkap untuk keperluan lab:
 * - blacklist ekstensi hanya menolak ".php" persis, padahal Apache (default
 *   libapache2-mod-php di Debian/Ubuntu) juga mengeksekusi ".phtml" dan ".phar"
 *   sebagai PHP lewat FilesMatch bawaan paket - varian lain (.php3/.php4/dst.,
 *   sisa dari config PHP versi lama) TIDAK dieksekusi di stack PHP 8.x ini
 * - MIME type diambil dari Content-Type yang dikirim klien, bukan dari isi file
 */
function teknobantu_handle_upload(array $file, int $ticketId): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload lampiran gagal.');
    }

    $originalName = basename($file['name']);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    $blockedExtensions = ['php'];
    if (in_array($extension, $blockedExtensions, true)) {
        throw new RuntimeException('Tipe file lampiran tidak diizinkan.');
    }

    $clientMimeType = $file['type'] ?? '';
    if (strpos($clientMimeType, 'image/') !== 0) {
        throw new RuntimeException('Lampiran harus berupa gambar (screenshot).');
    }

    $uploadDir = __DIR__ . '/../uploads/tickets/';
    $storedName = $ticketId . '_' . $originalName;
    $destination = $uploadDir . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Gagal menyimpan lampiran.');
    }

    return 'uploads/tickets/' . $storedName;
}
