<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/layout.php';

session_start();
if (!isset($_SESSION['staff_id'])) {
    header('Location: /staff/login.php');
    exit;
}

$ticketId = (int) ($_GET['id'] ?? 0);

$pdo = teknobantu_db();
$stmt = $pdo->prepare('SELECT * FROM tickets WHERE id = ?');
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

teknobantu_header('Detail Tiket');
?>
<p><a href="/staff/dashboard.php">&larr; Kembali ke Daftar Tiket</a></p>

<?php if (!$ticket): ?>
    <p>Tiket tidak ditemukan.</p>
<?php else: ?>
    <h1>Tiket #<?= (int) $ticket['id'] ?>: <?= htmlspecialchars($ticket['subjek']) ?></h1>
    <p>
        <strong>Dari:</strong> <?= htmlspecialchars($ticket['nama']) ?>
        (<?= htmlspecialchars($ticket['email']) ?>)<br>
        <strong>Status:</strong> <?= htmlspecialchars($ticket['status']) ?><br>
        <strong>Tanggal:</strong> <?= htmlspecialchars($ticket['created_at']) ?>
    </p>

    <h2>Deskripsi Masalah</h2>
    <div class="ticket-description">
        <?= nl2br($ticket['deskripsi']) ?>
    </div>

    <?php if ($ticket['lampiran_path']): ?>
        <h2>Lampiran</h2>
        <p><a href="/<?= htmlspecialchars($ticket['lampiran_path']) ?>" target="_blank">Lihat lampiran</a></p>
    <?php endif; ?>
<?php endif; ?>
<?php
teknobantu_footer();
