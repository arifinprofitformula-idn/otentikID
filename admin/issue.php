<?php
declare(strict_types=1);

require __DIR__ . '/auth_check.php';

$brandList = ['GOLDGRAM', 'MEEZAN GOLD', 'SILVERGRAM', 'Katalisis', 'Umum'];

$errors = [];
$form = [
    'nama_dokumen' => '',
    'jenis_dokumen' => '',
    'brand_penerbit' => 'Umum',
    'nama_penerima' => '',
    'tanggal_terbit' => date('Y-m-d'),
    'catatan' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['nama_dokumen'] = trim((string) ($_POST['nama_dokumen'] ?? ''));
    $form['jenis_dokumen'] = trim((string) ($_POST['jenis_dokumen'] ?? ''));
    $form['brand_penerbit'] = trim((string) ($_POST['brand_penerbit'] ?? ''));
    $form['nama_penerima'] = trim((string) ($_POST['nama_penerima'] ?? ''));
    $form['tanggal_terbit'] = trim((string) ($_POST['tanggal_terbit'] ?? ''));
    $form['catatan'] = trim((string) ($_POST['catatan'] ?? ''));

    if ($form['nama_dokumen'] === '') {
        $errors[] = 'Nama dokumen wajib diisi.';
    }
    if ($form['jenis_dokumen'] === '') {
        $errors[] = 'Jenis dokumen wajib diisi.';
    }
    if (!in_array($form['brand_penerbit'], $brandList, true)) {
        $errors[] = 'Brand penerbit tidak valid.';
    }
    if ($form['nama_penerima'] === '') {
        $errors[] = 'Nama penerima wajib diisi.';
    }
    if ($form['tanggal_terbit'] === '' || strtotime($form['tanggal_terbit']) === false) {
        $errors[] = 'Tanggal terbit tidak valid.';
    }

    if (!$errors) {
        $kodeUnik = generateKodeUnik($pdo);
        $hashDokumen = generateHashDokumen([
            'kode_unik' => $kodeUnik,
            'nama_dokumen' => $form['nama_dokumen'],
            'jenis_dokumen' => $form['jenis_dokumen'],
            'brand_penerbit' => $form['brand_penerbit'],
            'nama_penerima' => $form['nama_penerima'],
            'tanggal_terbit' => $form['tanggal_terbit'],
        ], SALT_RAHASIA);

        $stmt = $pdo->prepare(
            'INSERT INTO documents
                (kode_unik, nama_dokumen, jenis_dokumen, brand_penerbit, nama_penerima, catatan, tanggal_terbit, hash_dokumen, diterbitkan_oleh)
             VALUES
                (:kode_unik, :nama_dokumen, :jenis_dokumen, :brand_penerbit, :nama_penerima, :catatan, :tanggal_terbit, :hash_dokumen, :diterbitkan_oleh)'
        );
        $stmt->execute([
            'kode_unik' => $kodeUnik,
            'nama_dokumen' => $form['nama_dokumen'],
            'jenis_dokumen' => $form['jenis_dokumen'],
            'brand_penerbit' => $form['brand_penerbit'],
            'nama_penerima' => $form['nama_penerima'],
            'catatan' => $form['catatan'] !== '' ? $form['catatan'] : null,
            'tanggal_terbit' => $form['tanggal_terbit'],
            'hash_dokumen' => $hashDokumen,
            'diterbitkan_oleh' => (int) $_SESSION['admin_id'],
        ]);

        header('Location: issue_success.php?id=' . $pdo->lastInsertId());
        exit;
    }
}

$pageTitle = 'Terbitkan Dokumen';
$basePath = '../';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Terbitkan Dokumen Baru</h1>
</div>

<div class="card">
    <?php if ($errors) : ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $err) : ?>
                    <li><?php echo e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" novalidate>
        <div class="form-group">
            <label for="nama_dokumen">Nama Dokumen</label>
            <input type="text" id="nama_dokumen" name="nama_dokumen" value="<?php echo e($form['nama_dokumen']); ?>" required>
        </div>
        <div class="form-group">
            <label for="jenis_dokumen">Jenis Dokumen</label>
            <input type="text" id="jenis_dokumen" name="jenis_dokumen" value="<?php echo e($form['jenis_dokumen']); ?>" placeholder="Contoh: Sertifikat, Ijazah, Surat Keterangan" required>
        </div>
        <div class="form-group">
            <label for="brand_penerbit">Brand Penerbit</label>
            <select id="brand_penerbit" name="brand_penerbit" required>
                <?php foreach ($brandList as $brand) : ?>
                    <option value="<?php echo e($brand); ?>" <?php echo $form['brand_penerbit'] === $brand ? 'selected' : ''; ?>><?php echo e($brand); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="nama_penerima">Nama Penerima</label>
            <input type="text" id="nama_penerima" name="nama_penerima" value="<?php echo e($form['nama_penerima']); ?>" required>
        </div>
        <div class="form-group">
            <label for="tanggal_terbit">Tanggal Terbit</label>
            <input type="date" id="tanggal_terbit" name="tanggal_terbit" value="<?php echo e($form['tanggal_terbit']); ?>" required>
        </div>
        <div class="form-group">
            <label for="catatan">Catatan (opsional)</label>
            <textarea id="catatan" name="catatan" rows="3"><?php echo e($form['catatan']); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Terbitkan Dokumen</button>
        <a href="dashboard.php" class="btn btn-link">Batal</a>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
