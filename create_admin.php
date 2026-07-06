<?php
// !!! HAPUS FILE INI SETELAH ADMIN PERTAMA DIBUAT! JANGAN TINGGALKAN DI SERVER PRODUKSI. !!!
declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/includes/functions.php';

$pesanSukses = '';
$pesanError = '';

$stmtCek = $pdo->query('SELECT COUNT(*) FROM admins');
$sudahAdaAdmin = ((int) $stmtCek->fetchColumn()) > 0;

if ($sudahAdaAdmin) {
    $pesanError = 'Admin sudah ada di sistem. Demi keamanan, hapus file create_admin.php ini sekarang.';
}

if (!$sudahAdaAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $namaLengkap = trim((string) ($_POST['nama_lengkap'] ?? ''));

    if ($username === '' || $password === '' || $namaLengkap === '') {
        $pesanError = 'Semua field wajib diisi.';
    } elseif (strlen($password) < 8) {
        $pesanError = 'Password minimal 8 karakter.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO admins (username, password_hash, nama_lengkap) VALUES (:username, :password_hash, :nama_lengkap)'
        );
        $stmt->execute([
            'username' => $username,
            'password_hash' => $hash,
            'nama_lengkap' => $namaLengkap,
        ]);

        $pesanSukses = 'Admin berhasil dibuat. Segera hapus file create_admin.php ini dan login melalui admin/login.php.';
        $sudahAdaAdmin = true;
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buat Admin Pertama - Otentik ID</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <main class="container page-content" style="max-width:480px;margin:0 auto;padding-top:48px;">
        <div class="card">
            <h1>Buat Admin Pertama</h1>
            <p class="text-muted">Script sekali-pakai untuk inisialisasi akun admin. <strong>Hapus file ini setelah selesai.</strong></p>

            <?php if ($pesanSukses !== '') : ?>
                <div class="alert alert-success"><?php echo e($pesanSukses); ?></div>
            <?php endif; ?>

            <?php if ($pesanError !== '') : ?>
                <div class="alert alert-error"><?php echo e($pesanError); ?></div>
            <?php endif; ?>

            <?php if (!$sudahAdaAdmin) : ?>
                <form method="post" novalidate>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password (minimal 8 karakter)</label>
                        <input type="password" id="password" name="password" minlength="8" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Buat Admin</button>
                </form>
            <?php else : ?>
                <p><a href="admin/login.php">Lanjut ke halaman login &rarr;</a></p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
