<?php
declare(strict_types=1);

require __DIR__ . '/auth_check.php';
require __DIR__ . '/../includes/admin_layout.php';
requireRole(['superadmin', 'admin']);

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
    if ($passwordLama !== '' && $passwordLama === $passwordBaru) {
        $errors[] = 'Password baru tidak boleh sama dengan password lama.';
    }

    if (!$errors) {
        $stmtUpdate = $pdo->prepare('UPDATE admins SET password_hash = :password_hash WHERE id = :id');
        $stmtUpdate->execute([
            'password_hash' => password_hash($passwordBaru, PASSWORD_DEFAULT),
            'id' => (int) $_SESSION['admin_id'],
        ]);
        session_regenerate_id(true);
        auditLog($pdo, (int) $_SESSION['admin_id'], 'change_password', 'Admin mengubah password akun sendiri.');
        $sukses = 'Password berhasil diperbarui.';
    }
}

renderAdminLayoutStart($pdo, 'Ubah Password', 'password', '<a href="settings.php" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke Pengaturan</a>');
?>

<div class="theme-surface max-w-xl border border-slate-200 bg-white p-6">
    <h3 class="text-lg font-bold text-slate-900">Ubah Password</h3>
    <p class="mt-1 text-sm text-slate-500">Gunakan password kuat untuk menjaga keamanan panel admin.</p>

    <?php if ($sukses !== '') : ?>
        <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"><?php echo e($sukses); ?></div>
    <?php endif; ?>

    <?php if ($errors) : ?>
        <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $err) : ?>
                    <li><?php echo e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="mt-6 space-y-5" novalidate>
        <div>
            <label for="password_lama" class="mb-2 block text-sm font-semibold text-slate-700">Password Lama</label>
            <input class="theme-focus w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition" type="password" id="password_lama" name="password_lama" required>
        </div>
        <div>
            <label for="password_baru" class="mb-2 block text-sm font-semibold text-slate-700">Password Baru</label>
            <input class="theme-focus w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition" type="password" id="password_baru" name="password_baru" required>
            <p class="mt-2 text-xs text-slate-500">Minimal 10 karakter, huruf besar, huruf kecil, dan angka.</p>
        </div>
        <div>
            <label for="konfirmasi_password" class="mb-2 block text-sm font-semibold text-slate-700">Konfirmasi Password Baru</label>
            <input class="theme-focus w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition" type="password" id="konfirmasi_password" name="konfirmasi_password" required>
        </div>
        <button class="theme-button rounded-lg px-6 py-3 text-sm font-bold shadow-sm transition" type="submit">Simpan Password Baru</button>
    </form>
</div>

<?php renderAdminLayoutEnd(); ?>
