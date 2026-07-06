<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$pesanError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $pesanError = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, password_hash, nama_lengkap FROM admins WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_nama'] = $admin['nama_lengkap'];

            header('Location: dashboard.php');
            exit;
        }

        $pesanError = 'Username atau password salah.';
    }
}

$pageTitle = 'Login Admin';
$basePath = '../';
require __DIR__ . '/../includes/header.php';
?>

<div class="card auth-card">
    <h1>Login Admin</h1>

    <?php if ($pesanError !== '') : ?>
        <div class="alert alert-error"><?php echo e($pesanError); ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Masuk</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
