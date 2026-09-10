<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/layout.php';

session_start();

if (isset($_SESSION['staff_id'])) {
    header('Location: /staff/dashboard.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $pdo = teknobantu_db();
    $stmt = $pdo->prepare('SELECT id, username, password_hash, nama_lengkap FROM staff WHERE username = ?');
    $stmt->execute([$username]);
    $staff = $stmt->fetch();

    if ($staff && password_verify($password, $staff['password_hash'])) {
        $_SESSION['staff_id'] = $staff['id'];
        $_SESSION['staff_nama'] = $staff['nama_lengkap'];
        header('Location: /staff/dashboard.php');
        exit;
    }

    $error = 'Username atau password salah.';
}

teknobantu_header('Login Staff');
?>
<h1>Login Staff IT</h1>
<?php if ($error): ?>
    <p class="alert alert-error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>
<form action="/staff/login.php" method="post" class="ticket-form">
    <label for="username">Username</label>
    <input type="text" id="username" name="username" required>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>

    <button type="submit">Login</button>
</form>
<?php
teknobantu_footer();
