<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/layout.php';

session_start();
if (!isset($_SESSION['staff_id'])) {
    header('Location: /staff/login.php');
    exit;
}

$pdo = teknobantu_db();
$tickets = $pdo->query('SELECT id, nama, subjek, status, created_at FROM tickets ORDER BY id DESC')->fetchAll();

teknobantu_header('Dashboard Staff');
?>
<h1>Daftar Tiket</h1>
<p>Login sebagai <strong><?= htmlspecialchars($_SESSION['staff_nama']) ?></strong>
    - <a href="/staff/logout.php">Logout</a></p>

<table class="ticket-table">
    <thead>
        <tr>
            <th>No</th>
            <th>Pengirim</th>
            <th>Subjek</th>
            <th>Status</th>
            <th>Tanggal</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($tickets as $ticket): ?>
        <tr>
            <td>#<?= (int) $ticket['id'] ?></td>
            <td><?= htmlspecialchars($ticket['nama']) ?></td>
            <td><a href="/staff/ticket.php?id=<?= (int) $ticket['id'] ?>"><?= htmlspecialchars($ticket['subjek']) ?></a></td>
            <td><?= htmlspecialchars($ticket['status']) ?></td>
            <td><?= htmlspecialchars($ticket['created_at']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php
teknobantu_footer();
