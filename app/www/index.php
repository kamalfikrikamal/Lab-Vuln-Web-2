<?php
require __DIR__ . '/includes/layout.php';

teknobantu_header('Ajukan Tiket Bantuan');
?>
<h1>Ajukan Tiket Bantuan IT</h1>
<p>Alami kendala perangkat, akses, atau aplikasi kantor? Isi form di bawah dan tim IT
kami akan menindaklanjuti secepatnya.</p>

<?php if (isset($_GET['error'])): ?>
    <p class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></p>
<?php endif; ?>

<form action="/submit.php" method="post" enctype="multipart/form-data" class="ticket-form">
    <label for="nama">Nama</label>
    <input type="text" id="nama" name="nama" required>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" required>

    <label for="subjek">Subjek</label>
    <input type="text" id="subjek" name="subjek" maxlength="200" required>

    <label for="deskripsi">Deskripsi Masalah</label>
    <textarea id="deskripsi" name="deskripsi" rows="6" required></textarea>

    <label for="lampiran">Lampiran (screenshot, opsional)</label>
    <input type="file" id="lampiran" name="lampiran">

    <button type="submit">Kirim Tiket</button>
</form>

<p class="staff-link"><a href="/staff/login.php">Login Staff IT</a></p>
<?php
teknobantu_footer();
