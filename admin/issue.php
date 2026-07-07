<?php
declare(strict_types=1);

require __DIR__ . '/auth_check.php';
require __DIR__ . '/../includes/admin_layout.php';
requireRole(['superadmin', 'admin']);

$brandList = getBrands($pdo, true);
$stmtPemilik = $pdo->query("SELECT id, nama_lengkap, username, email, role FROM admins WHERE status = 'approved' ORDER BY nama_lengkap ASC");
$pemilikList = $stmtPemilik->fetchAll();

$errors = [];
$form = [
    'nama_dokumen' => '',
    'jenis_dokumen' => '',
    'brand_id' => '',
    'nama_penerima' => '',
    'pemilik_id' => (string) ($_SESSION['admin_id'] ?? ''),
    'nomor_surat' => '',
    'nama_penandatangan' => '',
    'jabatan_penandatangan' => '',
    'tanggal_terbit' => date('Y-m-d'),
    'catatan' => '',
];

if ($brandList) {
    foreach ($brandList as $brandDefault) {
        if ($brandDefault['nama_brand'] === 'Umum') {
            $form['brand_id'] = (string) $brandDefault['id'];
            break;
        }
    }
    if ($form['brand_id'] === '') {
        $form['brand_id'] = (string) $brandList[0]['id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['nama_dokumen'] = trim((string) ($_POST['nama_dokumen'] ?? ''));
    $form['jenis_dokumen'] = trim((string) ($_POST['jenis_dokumen'] ?? ''));
    $form['brand_id'] = trim((string) ($_POST['brand_id'] ?? ''));
    $form['nama_penerima'] = trim((string) ($_POST['nama_penerima'] ?? ''));
    $form['pemilik_id'] = trim((string) ($_POST['pemilik_id'] ?? ''));
    $form['nomor_surat'] = trim((string) ($_POST['nomor_surat'] ?? ''));
    $form['nama_penandatangan'] = trim((string) ($_POST['nama_penandatangan'] ?? ''));
    $form['jabatan_penandatangan'] = trim((string) ($_POST['jabatan_penandatangan'] ?? ''));
    $form['tanggal_terbit'] = trim((string) ($_POST['tanggal_terbit'] ?? ''));
    $form['catatan'] = trim((string) ($_POST['catatan'] ?? ''));

    if ($form['nama_dokumen'] === '') {
        $errors[] = 'Nama dokumen wajib diisi.';
    }
    if ($form['jenis_dokumen'] === '') {
        $errors[] = 'Jenis dokumen wajib diisi.';
    }
    $brandTerpilih = getBrandById($pdo, (int) $form['brand_id'], true);
    if (!$brandTerpilih) {
        $errors[] = 'Brand penerbit tidak valid.';
    }
    if ($form['nama_penerima'] === '') {
        $errors[] = 'Nama penerima wajib diisi.';
    }
    $pemilikTerpilih = null;
    foreach ($pemilikList as $pemilik) {
        if ((int) $pemilik['id'] === (int) $form['pemilik_id']) {
            $pemilikTerpilih = $pemilik;
            break;
        }
    }
    if (!$pemilikTerpilih) {
        $errors[] = 'Pemilik dokumen tidak valid.';
    }
    if ($form['nama_penandatangan'] === '') {
        $errors[] = 'Nama penandatangan wajib diisi.';
    }
    if ($form['jabatan_penandatangan'] === '') {
        $errors[] = 'Jabatan penandatangan wajib diisi.';
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
            'brand_penerbit' => $brandTerpilih['nama_brand'],
            'nama_penerima' => $form['nama_penerima'],
            'nomor_surat' => $form['nomor_surat'],
            'nama_penandatangan' => $form['nama_penandatangan'],
            'jabatan_penandatangan' => $form['jabatan_penandatangan'],
            'tanggal_terbit' => $form['tanggal_terbit'],
        ], SALT_RAHASIA);

        $stmt = $pdo->prepare(
            'INSERT INTO documents
                (kode_unik, nama_dokumen, jenis_dokumen, brand_penerbit, brand_id, nama_penerima, nomor_surat, nama_penandatangan, jabatan_penandatangan, catatan, tanggal_terbit, hash_dokumen, diterbitkan_oleh, pemilik_id)
             VALUES
                (:kode_unik, :nama_dokumen, :jenis_dokumen, :brand_penerbit, :brand_id, :nama_penerima, :nomor_surat, :nama_penandatangan, :jabatan_penandatangan, :catatan, :tanggal_terbit, :hash_dokumen, :diterbitkan_oleh, :pemilik_id)'
        );
        $stmt->execute([
            'kode_unik' => $kodeUnik,
            'nama_dokumen' => $form['nama_dokumen'],
            'jenis_dokumen' => $form['jenis_dokumen'],
            'brand_penerbit' => $brandTerpilih['nama_brand'],
            'brand_id' => (int) $brandTerpilih['id'],
            'nama_penerima' => $form['nama_penerima'],
            'nomor_surat' => $form['nomor_surat'] !== '' ? $form['nomor_surat'] : null,
            'nama_penandatangan' => $form['nama_penandatangan'],
            'jabatan_penandatangan' => $form['jabatan_penandatangan'],
            'catatan' => $form['catatan'] !== '' ? $form['catatan'] : null,
            'tanggal_terbit' => $form['tanggal_terbit'],
            'hash_dokumen' => $hashDokumen,
            'diterbitkan_oleh' => (int) $_SESSION['admin_id'],
            'pemilik_id' => (int) $pemilikTerpilih['id'],
        ]);

        header('Location: issue_success.php?id=' . $pdo->lastInsertId());
        exit;
    }
}

renderAdminLayoutStart($pdo, 'Terbitkan Dokumen Baru', 'issue', '<a href="dashboard.php" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke Dashboard</a>');
?>

<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <?php if ($errors) : ?>
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $err) : ?>
                    <li><?php echo e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="grid grid-cols-1 gap-5 lg:grid-cols-2" novalidate>
        <div>
            <label for="nama_dokumen" class="mb-2 block text-sm font-semibold text-slate-700">Nama Dokumen</label>
            <input class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition theme-focus" type="text" id="nama_dokumen" name="nama_dokumen" value="<?php echo e($form['nama_dokumen']); ?>" required>
        </div>
        <div>
            <label for="jenis_dokumen" class="mb-2 block text-sm font-semibold text-slate-700">Jenis Dokumen</label>
            <input class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition theme-focus" type="text" id="jenis_dokumen" name="jenis_dokumen" value="<?php echo e($form['jenis_dokumen']); ?>" placeholder="Contoh: Sertifikat, Ijazah, Surat Keterangan" required>
        </div>
        <div>
            <label for="brand_id" class="mb-2 block text-sm font-semibold text-slate-700">Brand Penerbit</label>
            <select class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition theme-focus" id="brand_id" name="brand_id" required>
                <option value="">Pilih brand</option>
                <?php foreach ($brandList as $brand) : ?>
                    <option value="<?php echo (int) $brand['id']; ?>" <?php echo (string) $form['brand_id'] === (string) $brand['id'] ? 'selected' : ''; ?>><?php echo e($brand['nama_brand']); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!$brandList) : ?>
                <p class="mt-2 text-sm text-red-600">Belum ada brand aktif. Tambahkan brand di menu Brand Penerbit.</p>
            <?php endif; ?>
        </div>
        <div>
            <label for="nama_penerima" class="mb-2 block text-sm font-semibold text-slate-700">Nama Penerima</label>
            <input class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition theme-focus" type="text" id="nama_penerima" name="nama_penerima" value="<?php echo e($form['nama_penerima']); ?>" required>
        </div>
        <div>
            <label for="pemilik_id" class="mb-2 block text-sm font-semibold text-slate-700">Pemilik Akun Dokumen</label>
            <select class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition theme-focus" id="pemilik_id" name="pemilik_id" required>
                <?php foreach ($pemilikList as $pemilik) : ?>
                    <option value="<?php echo (int) $pemilik['id']; ?>" <?php echo (string) $form['pemilik_id'] === (string) $pemilik['id'] ? 'selected' : ''; ?>>
                        <?php echo e($pemilik['nama_lengkap']); ?> (@<?php echo e($pemilik['username']); ?> - <?php echo e($pemilik['role']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="nomor_surat" class="mb-2 block text-sm font-semibold text-slate-700">Nomor Surat (opsional)</label>
            <input class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition theme-focus" type="text" id="nomor_surat" name="nomor_surat" value="<?php echo e($form['nomor_surat']); ?>" placeholder="Contoh: 012/EPI/VII/2026">
        </div>
        <div>
            <label for="nama_penandatangan" class="mb-2 block text-sm font-semibold text-slate-700">Nama Penandatangan</label>
            <input class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition theme-focus" type="text" id="nama_penandatangan" name="nama_penandatangan" value="<?php echo e($form['nama_penandatangan']); ?>" placeholder="Nama jelas yang menandatangani dokumen" required>
        </div>
        <div>
            <label for="jabatan_penandatangan" class="mb-2 block text-sm font-semibold text-slate-700">Jabatan Penandatangan</label>
            <input class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition theme-focus" type="text" id="jabatan_penandatangan" name="jabatan_penandatangan" value="<?php echo e($form['jabatan_penandatangan']); ?>" placeholder="Contoh: Direktur Utama, Coach, Pribadi" required>
        </div>
        <div>
            <label for="tanggal_terbit" class="mb-2 block text-sm font-semibold text-slate-700">Tanggal Terbit</label>
            <input class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition theme-focus" type="date" id="tanggal_terbit" name="tanggal_terbit" value="<?php echo e($form['tanggal_terbit']); ?>" required>
        </div>
        <div class="lg:col-span-2">
            <label for="catatan" class="mb-2 block text-sm font-semibold text-slate-700">Catatan (opsional)</label>
            <textarea class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition theme-focus" id="catatan" name="catatan" rows="4"><?php echo e($form['catatan']); ?></textarea>
        </div>
        <div class="flex flex-wrap gap-3 lg:col-span-2">
            <button type="submit" class="theme-button rounded-lg px-6 py-3 text-sm font-bold shadow-sm transition">Terbitkan Dokumen</button>
            <a href="dashboard.php" class="rounded-lg border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Batal</a>
        </div>
    </form>
</div>

<?php renderAdminLayoutEnd(); ?>
