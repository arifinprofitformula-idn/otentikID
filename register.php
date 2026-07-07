<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/includes/functions.php';

$settings = getSettings($pdo);
$errors = [];
$sukses = false;
$form = [
    'username' => '',
    'email' => '',
    'nama_lengkap' => '',
    'organisasi' => '',
    'alasan_daftar' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['username'] = trim((string) ($_POST['username'] ?? ''));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['nama_lengkap'] = trim((string) ($_POST['nama_lengkap'] ?? ''));
    $form['organisasi'] = trim((string) ($_POST['organisasi'] ?? ''));
    $form['alasan_daftar'] = trim((string) ($_POST['alasan_daftar'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $konfirmasi = (string) ($_POST['konfirmasi_password'] ?? '');

    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $form['username'])) {
        $errors[] = 'Username harus 3-50 karakter dan hanya boleh huruf, angka, titik, underscore, atau strip.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid.';
    }
    if ($form['nama_lengkap'] === '') {
        $errors[] = 'Nama lengkap wajib diisi.';
    }
    if (!passwordKuat($password)) {
        $errors[] = 'Password minimal 10 karakter dan mengandung huruf besar, huruf kecil, dan angka.';
    }
    if ($password !== $konfirmasi) {
        $errors[] = 'Konfirmasi password tidak sama.';
    }

    if (!$errors) {
        $stmtCek = $pdo->prepare('SELECT COUNT(*) FROM admins WHERE username = :username OR email = :email');
        $stmtCek->execute([
            'username' => $form['username'],
            'email' => $form['email'],
        ]);

        if ((int) $stmtCek->fetchColumn() > 0) {
            $errors[] = 'Username atau email sudah digunakan.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO admins
                (username, email, password_hash, nama_lengkap, status, role, organisasi, alasan_daftar)
             VALUES
                (:username, :email, :password_hash, :nama_lengkap, "pending", "user", :organisasi, :alasan_daftar)'
        );
        $stmt->execute([
            'username' => $form['username'],
            'email' => $form['email'],
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'nama_lengkap' => $form['nama_lengkap'],
            'organisasi' => $form['organisasi'] !== '' ? $form['organisasi'] : null,
            'alasan_daftar' => $form['alasan_daftar'] !== '' ? $form['alasan_daftar'] : null,
        ]);
        $sukses = true;
        $form = array_fill_keys(array_keys($form), '');
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrasi Akun - <?php echo e($settings['nama_perusahaan']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <section class="w-full max-w-2xl rounded-2xl bg-white p-8 shadow-xl">
            <div class="mb-8 text-center">
                <?php if (!empty($settings['logo_path'])) : ?>
                    <img src="<?php echo e($settings['logo_path']); ?>" alt="<?php echo e($settings['nama_perusahaan']); ?>" class="mx-auto mb-4 max-h-16 max-w-xs object-contain">
                <?php endif; ?>
                <h1 class="text-3xl font-black text-slate-900">Registrasi Akun</h1>
                <p class="mt-2 text-sm text-slate-500">Akun baru akan aktif setelah diverifikasi oleh admin.</p>
            </div>

            <?php if ($sukses) : ?>
                <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    Registrasi berhasil dikirim. Silakan tunggu approval admin sebelum login.
                </div>
            <?php endif; ?>

            <?php if ($errors) : ?>
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                    <ul class="list-disc pl-5">
                        <?php foreach ($errors as $err) : ?>
                            <li><?php echo e($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" class="grid grid-cols-1 gap-5 md:grid-cols-2" novalidate>
                <div>
                    <label for="username" class="mb-2 block text-sm font-semibold text-slate-700">Username</label>
                    <input class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-slate-700 focus:ring-4 focus:ring-slate-100" type="text" id="username" name="username" value="<?php echo e($form['username']); ?>" required>
                </div>
                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                    <input class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-slate-700 focus:ring-4 focus:ring-slate-100" type="email" id="email" name="email" value="<?php echo e($form['email']); ?>" required>
                </div>
                <div class="md:col-span-2">
                    <label for="nama_lengkap" class="mb-2 block text-sm font-semibold text-slate-700">Nama Lengkap</label>
                    <input class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-slate-700 focus:ring-4 focus:ring-slate-100" type="text" id="nama_lengkap" name="nama_lengkap" value="<?php echo e($form['nama_lengkap']); ?>" required>
                </div>
                <div class="md:col-span-2">
                    <label for="organisasi" class="mb-2 block text-sm font-semibold text-slate-700">Organisasi / Perusahaan</label>
                    <input class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-slate-700 focus:ring-4 focus:ring-slate-100" type="text" id="organisasi" name="organisasi" value="<?php echo e($form['organisasi']); ?>">
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
                    <input class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-slate-700 focus:ring-4 focus:ring-slate-100" type="password" id="password" name="password" required>
                </div>
                <div>
                    <label for="konfirmasi_password" class="mb-2 block text-sm font-semibold text-slate-700">Konfirmasi Password</label>
                    <input class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-slate-700 focus:ring-4 focus:ring-slate-100" type="password" id="konfirmasi_password" name="konfirmasi_password" required>
                </div>
                <div class="md:col-span-2">
                    <label for="alasan_daftar" class="mb-2 block text-sm font-semibold text-slate-700">Keperluan Penggunaan</label>
                    <textarea class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-slate-700 focus:ring-4 focus:ring-slate-100" id="alasan_daftar" name="alasan_daftar" rows="4"><?php echo e($form['alasan_daftar']); ?></textarea>
                </div>
                <div class="flex flex-wrap items-center gap-3 md:col-span-2">
                    <button class="rounded-lg bg-slate-900 px-6 py-3 text-sm font-bold text-white transition hover:bg-slate-800" type="submit">Kirim Registrasi</button>
                    <a href="admin/login.php" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Sudah punya akun? Login</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
