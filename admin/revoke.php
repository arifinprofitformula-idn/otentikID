<?php
declare(strict_types=1);

require __DIR__ . '/auth_check.php';
require __DIR__ . '/../includes/admin_layout.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM documents WHERE id = :id');
$stmt->execute(['id' => $id]);
$dokumen = $stmt->fetch();

if (!$dokumen) {
    header('Location: dashboard.php');
    exit;
}

if ($dokumen['status'] !== 'aktif') {
    header('Location: detail.php?id=' . $id);
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alasan = trim((string) ($_POST['alasan_revoke'] ?? ''));

    if ($alasan === '') {
        $errors[] = 'Alasan pembatalan wajib diisi.';
    } else {
        $stmtUpdate = $pdo->prepare(
            "UPDATE documents
             SET status = 'revoked', alasan_revoke = :alasan, direvoke_pada = NOW()
             WHERE id = :id"
        );
        $stmtUpdate->execute([
            'alasan' => $alasan,
            'id' => $id,
        ]);

        header('Location: detail.php?id=' . $id . '&sukses=1');
        exit;
    }
}

renderAdminLayoutStart($pdo, 'Batalkan Dokumen', 'dashboard', '<a href="detail.php?id=' . (int) $dokumen['id'] . '" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Batal</a>');
?>

<div class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
        <p>Anda akan membatalkan dokumen <strong><?php echo e($dokumen['kode_unik']); ?></strong> — <?php echo e($dokumen['nama_dokumen']); ?> a.n. <?php echo e($dokumen['nama_penerima']); ?>.</p>
        <p class="mt-2">Tindakan ini akan membuat dokumen berstatus <strong>revoked</strong> dan tidak lagi valid saat diverifikasi publik. Tindakan ini tidak dapat dibatalkan.</p>
    </div>

    <?php if ($errors) : ?>
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $err) : ?>
                    <li><?php echo e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="id" value="<?php echo (int) $dokumen['id']; ?>">
        <div>
            <label for="alasan_revoke" class="mb-2 block text-sm font-semibold text-slate-700">Alasan Pembatalan</label>
            <textarea class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-red-500 focus:ring-4 focus:ring-red-100" id="alasan_revoke" name="alasan_revoke" rows="5" required><?php echo isset($_POST['alasan_revoke']) ? e($_POST['alasan_revoke']) : ''; ?></textarea>
        </div>
        <div class="mt-5 flex flex-wrap gap-3">
            <button type="submit" class="rounded-lg bg-red-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-red-700">Konfirmasi Batalkan Dokumen</button>
            <a href="detail.php?id=<?php echo (int) $dokumen['id']; ?>" class="rounded-lg border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Batal</a>
        </div>
    </form>
</div>

<?php renderAdminLayoutEnd(); ?>
