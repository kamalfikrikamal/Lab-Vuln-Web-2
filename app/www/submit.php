<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/upload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php');
    exit;
}

$nama = trim($_POST['nama'] ?? '');
$email = trim($_POST['email'] ?? '');
$subjek = trim($_POST['subjek'] ?? '');
$deskripsi = trim($_POST['deskripsi'] ?? '');

if ($nama === '' || $email === '' || $subjek === '' || $deskripsi === '') {
    header('Location: /index.php?error=' . urlencode('Semua field wajib diisi.'));
    exit;
}

$pdo = teknobantu_db();

try {
    $pdo->beginTransaction();

    $insert = $pdo->prepare(
        'INSERT INTO tickets (nama, email, subjek, deskripsi) VALUES (?, ?, ?, ?)'
    );
    $insert->execute([$nama, $email, $subjek, $deskripsi]);
    $ticketId = (int) $pdo->lastInsertId();

    if (!empty($_FILES['lampiran']['name'])) {
        $lampiranPath = teknobantu_handle_upload($_FILES['lampiran'], $ticketId);
        $update = $pdo->prepare('UPDATE tickets SET lampiran_path = ? WHERE id = ?');
        $update->execute([$lampiranPath, $ticketId]);
    }

    $pdo->commit();
} catch (RuntimeException $e) {
    $pdo->rollBack();
    header('Location: /index.php?error=' . urlencode($e->getMessage()));
    exit;
}

header('Location: /confirmation.php?id=' . $ticketId);
exit;
