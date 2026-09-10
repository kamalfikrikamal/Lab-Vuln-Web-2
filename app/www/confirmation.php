<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/layout.php';

$ticketId = (int) ($_GET['id'] ?? 0);

$pdo = teknobantu_db();
$stmt = $pdo->prepare('SELECT id, subjek, created_at FROM tickets WHERE id = ?');
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

teknobantu_header('Tiket Terkirim');
?>
<h1>Tiket Berhasil Dikirim</h1>
<?php if ($ticket): ?>
    <p>Terima kasih, tiket Anda sudah kami terima dan akan segera ditindaklanjuti
    oleh tim IT.</p>
    <div class="ticket-summary">
        <p><strong>Nomor Tiket:</strong> #<?= (int) $ticket['id'] ?></p>
        <p><strong>Subjek:</strong> <?= htmlspecialchars($ticket['subjek']) ?></p>
        <p><strong>Waktu:</strong> <?= htmlspecialchars($ticket['created_at']) ?></p>
    </div>
    <p>Simpan nomor tiket ini untuk keperluan referensi.</p>
<?php else: ?>
    <p>Tiket tidak ditemukan.</p>
<?php endif; ?>
<p><a href="/index.php">Kembali ke Form</a></p>
<?php
teknobantu_footer();
