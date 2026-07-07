<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/user_layout.php';

requireRole(['user']);

$errors = [];
$sukses = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passwordLama = (string) ($_POST['password_lama'] ?? '');
    $passwordBaru = (string) ($_POST['password_baru'] ?? '');
    $konfirmasi = (string) ($_POST['konfirmasi_password'] ?? '');

    $stmt = $pdo->prepare('SELECT password_hash FROM admins WHERE id = :id');
    $stmt->execute(['id' => (int) $_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($passwordLama, $admin['password_hash'])) {
        $errors[] = 'Password lama tidak sesuai.';
    }
    if (!passwordKuat($passwordBaru)) {
        $errors[] = 'Password baru minimal 10 karakter dan mengandung huruf besar, huruf kecil, dan angka.';
    }
    if ($passwordBaru !== $konfirmasi) {
        $errors[] = 'Konfirmasi password baru tidak sama.';
    }

    if (!$errors) {
        $stmtUpdate = $pdo->prepare('UPDATE admins SET password_hash = :hash WHERE id = :id');
        $stmtUpdate->execute([
            'hash' => password_hash($passwordBaru, PASSWORD_DEFAULT),
            'id' => (int) $_SESSION['admin_id'],
        ]);
        session_regenerate_id(true);
        auditLog($pdo, (int) $_SESSION['admin_id'], 'change_password_user', 'User mengubah password akun sendiri.');
        $sukses = 'Password berhasil diperbarui.';
    }
}

renderUserLayoutStart($pdo, 'Ubah Password', 'password');
?>

<div class="max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <?php if ($sukses !== '') : ?>
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"><?php echo e($sukses); ?></div>
    <?php endif; ?>
    <?php if ($errors) : ?>
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $err) : ?><li><?php echo e($err); ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post" class="space-y-5">
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700" for="password_lama">Password Lama</label>
            <input class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-slate-700 focus:ring-4 focus:ring-slate-100" type="password" id="password_lama" name="password_lama" required>
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700" for="password_baru">Password Baru</label>
            <input class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-slate-700 focus:ring-4 focus:ring-slate-100" type="password" id="password_baru" name="password_baru" required>
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700" for="konfirmasi_password">Konfirmasi Password Baru</label>
            <input class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-slate-700 focus:ring-4 focus:ring-slate-100" type="password" id="konfirmasi_password" name="konfirmasi_password" required>
        </div>
        <button class="user-button rounded-lg px-6 py-3 text-sm font-bold shadow-sm transition" type="submit">Simpan Password Baru</button>
    </form>
</div>

<?php renderUserLayoutEnd(); ?>
