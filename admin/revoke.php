<?php
declare(strict_types=1);

require __DIR__ . '/auth_check.php';

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

$pageTitle = 'Batalkan Dokumen';
$basePath = '../';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Batalkan Dokumen</h1>
</div>

<div class="card">
    <p>Anda akan membatalkan dokumen <strong><?php echo e($dokumen['kode_unik']); ?></strong> — <?php echo e($dokumen['nama_dokumen']); ?> a.n. <?php echo e($dokumen['nama_penerima']); ?>.</p>
    <p class="text-muted">Tindakan ini akan membuat dokumen berstatus <strong>revoked</strong> dan tidak lagi valid saat diverifikasi publik. Tindakan ini tidak dapat dibatalkan.</p>

    <?php if ($errors) : ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $err) : ?>
                    <li><?php echo e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="id" value="<?php echo (int) $dokumen['id']; ?>">
        <div class="form-group">
            <label for="alasan_revoke">Alasan Pembatalan</label>
            <textarea id="alasan_revoke" name="alasan_revoke" rows="4" required><?php echo isset($_POST['alasan_revoke']) ? e($_POST['alasan_revoke']) : ''; ?></textarea>
        </div>
        <button type="submit" class="btn btn-danger">Konfirmasi Batalkan Dokumen</button>
        <a href="detail.php?id=<?php echo (int) $dokumen['id']; ?>" class="btn btn-link">Batal</a>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
