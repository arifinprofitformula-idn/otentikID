<?php
declare(strict_types=1);

require __DIR__ . '/auth_check.php';
require __DIR__ . '/../includes/admin_layout.php';

$errors = [];
$sukses = '';
$editId = (int) ($_GET['edit'] ?? 0);
$brandEdit = $editId > 0 ? getBrandById($pdo, $editId, false) : null;

if ($editId > 0 && !$brandEdit) {
    header('Location: brands.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = (string) ($_POST['aksi'] ?? '');

    if ($aksi === 'simpan') {
        $brandId = (int) ($_POST['brand_id'] ?? 0);
        $namaBrand = trim((string) ($_POST['nama_brand'] ?? ''));
        $aktif = isset($_POST['aktif']) ? 1 : 0;

        if ($namaBrand === '') {
            $errors[] = 'Nama brand wajib diisi.';
        } elseif (mb_strlen($namaBrand) > 100) {
            $errors[] = 'Nama brand maksimal 100 karakter.';
        }

        if (!$errors) {
            $stmtCek = $pdo->prepare('SELECT id FROM brands WHERE nama_brand = :nama_brand AND id <> :id LIMIT 1');
            $stmtCek->execute([
                'nama_brand' => $namaBrand,
                'id' => $brandId,
            ]);

            if ($stmtCek->fetch()) {
                $errors[] = 'Nama brand sudah digunakan.';
            }
        }

        if (!$errors) {
            if ($brandId > 0) {
                $slug = generateUniqueBrandSlug($pdo, $namaBrand, $brandId);
                $stmt = $pdo->prepare(
                    'UPDATE brands
                     SET nama_brand = :nama_brand, slug = :slug, aktif = :aktif
                     WHERE id = :id'
                );
                $stmt->execute([
                    'nama_brand' => $namaBrand,
                    'slug' => $slug,
                    'aktif' => $aktif,
                    'id' => $brandId,
                ]);
                $sukses = 'Brand berhasil diperbarui.';
            } else {
                $slug = generateUniqueBrandSlug($pdo, $namaBrand);
                $stmt = $pdo->prepare(
                    'INSERT INTO brands (nama_brand, slug, aktif)
                     VALUES (:nama_brand, :slug, :aktif)'
                );
                $stmt->execute([
                    'nama_brand' => $namaBrand,
                    'slug' => $slug,
                    'aktif' => $aktif,
                ]);
                $sukses = 'Brand baru berhasil ditambahkan.';
            }
            $editId = 0;
            $brandEdit = null;
        }
    } elseif ($aksi === 'toggle') {
        $brandId = (int) ($_POST['brand_id'] ?? 0);
        $brand = getBrandById($pdo, $brandId, false);

        if ($brand) {
            $stmt = $pdo->prepare('UPDATE brands SET aktif = :aktif WHERE id = :id');
            $stmt->execute([
                'aktif' => (int) !$brand['aktif'],
                'id' => $brandId,
            ]);
            $sukses = $brand['aktif'] ? 'Brand berhasil dinonaktifkan.' : 'Brand berhasil diaktifkan.';
        }
    } elseif ($aksi === 'hapus') {
        $brandId = (int) ($_POST['brand_id'] ?? 0);
        $brand = getBrandById($pdo, $brandId, false);

        if ($brand) {
            $stmtHitung = $pdo->prepare(
                'SELECT COUNT(*) FROM documents WHERE brand_id = :brand_id OR brand_penerbit = :nama_brand'
            );
            $stmtHitung->execute([
                'brand_id' => $brandId,
                'nama_brand' => $brand['nama_brand'],
            ]);

            if ((int) $stmtHitung->fetchColumn() > 0) {
                $stmt = $pdo->prepare('UPDATE brands SET aktif = 0 WHERE id = :id');
                $stmt->execute(['id' => $brandId]);
                $sukses = 'Brand sudah pernah dipakai dokumen, jadi tidak dihapus permanen dan hanya dinonaktifkan.';
            } else {
                $stmt = $pdo->prepare('DELETE FROM brands WHERE id = :id');
                $stmt->execute(['id' => $brandId]);
                $sukses = 'Brand berhasil dihapus.';
            }
        }
    }
}

$brands = getBrands($pdo, false);
$formNama = $brandEdit['nama_brand'] ?? '';
$formAktif = $brandEdit ? (bool) $brandEdit['aktif'] : true;

renderAdminLayoutStart(
    $pdo,
    'Brand Penerbit',
    'brands',
    '<a href="issue.php" class="theme-button rounded-lg px-4 py-2.5 text-sm font-bold shadow-sm transition">Terbitkan Dokumen</a>'
);
?>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-[420px_1fr]">
    <div class="theme-surface border border-slate-200 bg-white p-6">
        <h3 class="text-lg font-bold text-slate-900"><?php echo $brandEdit ? 'Edit Brand' : 'Tambah Brand Baru'; ?></h3>
        <p class="mt-1 text-sm text-slate-500">Brand aktif akan muncul di dropdown penerbitan dokumen dan filter dashboard.</p>

        <?php if ($errors) : ?>
            <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                <ul class="list-disc pl-5">
                    <?php foreach ($errors as $err) : ?>
                        <li><?php echo e($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($sukses !== '') : ?>
            <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                <?php echo e($sukses); ?>
            </div>
        <?php endif; ?>

        <form method="post" class="mt-6 space-y-5">
            <input type="hidden" name="aksi" value="simpan">
            <input type="hidden" name="brand_id" value="<?php echo (int) ($brandEdit['id'] ?? 0); ?>">
            <div>
                <label for="nama_brand" class="mb-2 block text-sm font-semibold text-slate-700">Nama Brand</label>
                <input
                    type="text"
                    id="nama_brand"
                    name="nama_brand"
                    value="<?php echo e($formNama); ?>"
                    maxlength="100"
                    required
                    class="theme-focus w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition"
                    placeholder="Contoh: Brand Perusahaan Anda"
                >
            </div>
            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="checkbox" name="aktif" value="1" <?php echo $formAktif ? 'checked' : ''; ?> class="rounded border-slate-300">
                Aktifkan brand ini
            </label>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="theme-button rounded-lg px-6 py-3 text-sm font-bold shadow-sm transition">
                    <?php echo $brandEdit ? 'Simpan Perubahan' : 'Tambah Brand'; ?>
                </button>
                <?php if ($brandEdit) : ?>
                    <a href="brands.php" class="rounded-lg border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Batal Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="theme-surface overflow-hidden border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-base font-bold text-slate-900">Daftar Brand</h3>
            <p class="mt-1 text-sm text-slate-500">Brand yang sudah dipakai dokumen akan dinonaktifkan, bukan dihapus permanen.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Nama Brand</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Slug</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <?php if (!$brands) : ?>
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada brand.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($brands as $brand) : ?>
                        <tr class="transition hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-900"><?php echo e($brand['nama_brand']); ?></td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500"><?php echo e($brand['slug']); ?></td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <?php if ((int) $brand['aktif'] === 1) : ?>
                                    <span class="inline-flex rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">Aktif</span>
                                <?php else : ?>
                                    <span class="inline-flex rounded-full bg-slate-500 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="brands.php?edit=<?php echo (int) $brand['id']; ?>" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">Edit</a>
                                    <form method="post">
                                        <input type="hidden" name="aksi" value="toggle">
                                        <input type="hidden" name="brand_id" value="<?php echo (int) $brand['id']; ?>">
                                        <button type="submit" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                            <?php echo (int) $brand['aktif'] === 1 ? 'Nonaktifkan' : 'Aktifkan'; ?>
                                        </button>
                                    </form>
                                    <form method="post" onsubmit="return confirm('Hapus brand ini? Jika sudah pernah dipakai, brand hanya akan dinonaktifkan.');">
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="brand_id" value="<?php echo (int) $brand['id']; ?>">
                                        <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php renderAdminLayoutEnd(); ?>
