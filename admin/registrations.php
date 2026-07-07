<?php
declare(strict_types=1);

require __DIR__ . '/auth_check.php';
require __DIR__ . '/../includes/admin_layout.php';
requireRole(['superadmin']);

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = (string) ($_POST['aksi'] ?? '');
    $adminId = (int) ($_POST['admin_id'] ?? 0);

    $stmtUser = $pdo->prepare('SELECT id, username, nama_lengkap, status FROM admins WHERE id = :id');
    $stmtUser->execute(['id' => $adminId]);
    $target = $stmtUser->fetch();

    if ($target && (int) $target['id'] !== (int) $_SESSION['admin_id']) {
        if ($aksi === 'approve') {
            $roleApprove = (string) ($_POST['role_approve'] ?? 'user');
            if (!in_array($roleApprove, ['user', 'admin'], true)) {
                $roleApprove = 'user';
            }
            $stmt = $pdo->prepare(
                "UPDATE admins
                 SET status = 'approved', role = :role, disetujui_oleh = :oleh, disetujui_pada = NOW()
                 WHERE id = :id"
            );
            $stmt->execute([
                'role' => $roleApprove,
                'oleh' => (int) $_SESSION['admin_id'],
                'id' => $adminId,
            ]);
            auditLog($pdo, (int) $_SESSION['admin_id'], 'approve_user', 'Menyetujui akun ' . $target['username'] . ' sebagai ' . $roleApprove);
            $pesan = 'Akun berhasil disetujui.';
        } elseif ($aksi === 'reject') {
            $stmt = $pdo->prepare("UPDATE admins SET status = 'rejected' WHERE id = :id");
            $stmt->execute(['id' => $adminId]);
            auditLog($pdo, (int) $_SESSION['admin_id'], 'reject_user', 'Menolak akun ' . $target['username']);
            $pesan = 'Akun berhasil ditolak.';
        } elseif ($aksi === 'inactive') {
            $stmt = $pdo->prepare("UPDATE admins SET status = 'inactive' WHERE id = :id");
            $stmt->execute(['id' => $adminId]);
            auditLog($pdo, (int) $_SESSION['admin_id'], 'inactive_user', 'Menonaktifkan akun ' . $target['username']);
            $pesan = 'Akun berhasil dinonaktifkan.';
        }
    }
}

$statusFilter = trim((string) ($_GET['status'] ?? 'pending'));
$statusValid = ['pending', 'approved', 'rejected', 'inactive', 'all'];
if (!in_array($statusFilter, $statusValid, true)) {
    $statusFilter = 'pending';
}

$sql = 'SELECT id, username, email, nama_lengkap, status, role, organisasi, alasan_daftar, dibuat_pada, disetujui_pada FROM admins';
$params = [];
if ($statusFilter !== 'all') {
    $sql .= ' WHERE status = :status';
    $params['status'] = $statusFilter;
}
$sql .= ' ORDER BY dibuat_pada DESC LIMIT 200';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

renderAdminLayoutStart(
    $pdo,
    'Registrasi Akun',
    'registrations',
    '<a href="change_password.php" class="theme-button rounded-lg px-4 py-2.5 text-sm font-bold shadow-sm transition">Ubah Password Saya</a>'
);
?>

<?php if ($pesan !== '') : ?>
    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"><?php echo e($pesan); ?></div>
<?php endif; ?>

<form method="get" class="theme-surface border border-slate-200 bg-white p-4">
    <label for="status" class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Filter Status</label>
    <div class="flex flex-wrap gap-3">
        <select id="status" name="status" class="theme-focus rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none">
            <?php foreach ($statusValid as $status) : ?>
                <option value="<?php echo e($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo e(ucfirst($status)); ?></option>
            <?php endforeach; ?>
        </select>
        <button class="theme-button rounded-lg px-6 py-3 text-sm font-bold shadow-sm transition" type="submit">Terapkan</button>
    </div>
</form>

<div class="theme-surface overflow-hidden border border-slate-200 bg-white">
    <div class="border-b border-slate-200 px-5 py-4">
        <h3 class="text-base font-bold text-slate-900">Daftar Akun</h3>
        <p class="mt-1 text-sm text-slate-500">Akun pending harus disetujui sebelum bisa login.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Pengguna</th>
                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Organisasi</th>
                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Status / Role</th>
                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Tanggal Daftar</th>
                    <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php if (!$users) : ?>
                    <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">Tidak ada akun.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $user) : ?>
                    <tr class="align-top hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <p class="text-sm font-semibold text-slate-900"><?php echo e($user['nama_lengkap']); ?></p>
                            <p class="text-xs text-slate-500">@<?php echo e($user['username']); ?><?php echo $user['email'] ? ' · ' . e($user['email']) : ''; ?></p>
                            <?php if (!empty($user['alasan_daftar'])) : ?>
                                <p class="mt-2 max-w-md text-xs text-slate-500"><?php echo e($user['alasan_daftar']); ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4 text-sm text-slate-600"><?php echo e($user['organisasi'] ?? '-'); ?></td>
                        <td class="px-5 py-4">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide text-white <?php echo $user['status'] === 'approved' ? 'bg-emerald-600' : ($user['status'] === 'pending' ? 'bg-amber-500' : 'bg-slate-500'); ?>">
                                <?php echo e($user['status']); ?>
                            </span>
                            <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-slate-500"><?php echo e($user['role']); ?></p>
                        </td>
                        <td class="px-5 py-4 text-sm text-slate-600"><?php echo formatTanggalWaktuIndonesia($user['dibuat_pada']); ?></td>
                        <td class="px-5 py-4 text-right">
                            <?php if ((int) $user['id'] !== (int) $_SESSION['admin_id']) : ?>
                                <div class="flex justify-end gap-2">
                                    <?php if ($user['status'] !== 'approved') : ?>
                                        <form method="post">
                                            <input type="hidden" name="aksi" value="approve">
                                            <input type="hidden" name="admin_id" value="<?php echo (int) $user['id']; ?>">
                                            <select name="role_approve" class="rounded-lg border border-slate-200 px-2 py-2 text-xs font-semibold text-slate-700">
                                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                            <button class="rounded-lg border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50" type="submit">Approve</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($user['status'] === 'pending') : ?>
                                        <form method="post">
                                            <input type="hidden" name="aksi" value="reject">
                                            <input type="hidden" name="admin_id" value="<?php echo (int) $user['id']; ?>">
                                            <button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50" type="submit">Reject</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($user['status'] === 'approved') : ?>
                                        <form method="post">
                                            <input type="hidden" name="aksi" value="inactive">
                                            <input type="hidden" name="admin_id" value="<?php echo (int) $user['id']; ?>">
                                            <button class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50" type="submit">Nonaktifkan</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php else : ?>
                                <span class="text-xs text-slate-400">Akun Anda</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php renderAdminLayoutEnd(); ?>
