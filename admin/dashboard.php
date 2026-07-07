<?php
declare(strict_types=1);

require __DIR__ . '/auth_check.php';
require __DIR__ . '/../includes/admin_layout.php';
requireRole(['superadmin', 'admin']);

$brandList = getBrands($pdo, false);

$filterStatus = trim((string) ($_GET['status'] ?? ''));
$filterBrand = (int) ($_GET['brand'] ?? 0);
$pencarian = trim((string) ($_GET['q'] ?? ''));

$kondisi = [];
$parameter = [];

if ($filterStatus === 'aktif' || $filterStatus === 'revoked') {
    $kondisi[] = 'status = :status';
    $parameter['status'] = $filterStatus;
}

if ($filterBrand > 0) {
    $brandFilter = getBrandById($pdo, $filterBrand, false);
    if ($brandFilter) {
        $kondisi[] = '(brand_id = :brand_id OR brand_penerbit = :brand_nama)';
        $parameter['brand_id'] = $filterBrand;
        $parameter['brand_nama'] = $brandFilter['nama_brand'];
    }
}

if ($pencarian !== '') {
    $kondisi[] = '(kode_unik LIKE :cari OR nama_penerima LIKE :cari OR nomor_surat LIKE :cari)';
    $parameter['cari'] = '%' . $pencarian . '%';
}

$whereSql = $kondisi ? ('WHERE ' . implode(' AND ', $kondisi)) : '';

$stmt = $pdo->prepare(
    "SELECT id, kode_unik, nama_dokumen, nama_penerima, brand_penerbit, status, tanggal_terbit
     FROM documents
     $whereSql
     ORDER BY dibuat_pada DESC
     LIMIT 200"
);
$stmt->execute($parameter);
$dokumenList = $stmt->fetchAll();

$totalAktif = (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE status = 'aktif'")->fetchColumn();
$totalRevoked = (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE status = 'revoked'")->fetchColumn();
$totalVerifikasi30Hari = (int) $pdo->query(
    'SELECT COUNT(*) FROM verification_logs WHERE dicek_pada >= (NOW() - INTERVAL 30 DAY)'
)->fetchColumn();

renderAdminLayoutStart(
    $pdo,
    'Dashboard',
    'dashboard',
    (currentUserRole() === 'superadmin'
        ? '<a href="settings.php" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 md:hidden">Pengaturan</a> '
        : '')
    . '<a href="issue.php" class="theme-button rounded-lg px-4 py-2.5 text-sm font-bold shadow-sm transition">+ Terbitkan Dokumen Baru</a>'
);
?>

<div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
    <article class="theme-stat-card theme-surface p-6">
        <p class="text-sm font-semibold uppercase tracking-[0.18em]">Total Dokumen Aktif</p>
        <p class="mt-5 text-5xl font-black tracking-tight"><?php echo number_format($totalAktif, 0, ',', '.'); ?></p>
    </article>
    <article class="theme-stat-card theme-surface p-6">
        <p class="text-sm font-semibold uppercase tracking-[0.18em]">Total Direvoke</p>
        <p class="mt-5 text-5xl font-black tracking-tight"><?php echo number_format($totalRevoked, 0, ',', '.'); ?></p>
    </article>
    <article class="theme-stat-card theme-surface p-6">
        <p class="text-sm font-semibold uppercase tracking-[0.18em]">Total Verifikasi 30 Hari Terakhir</p>
        <p class="mt-5 text-5xl font-black tracking-tight"><?php echo number_format($totalVerifikasi30Hari, 0, ',', '.'); ?></p>
    </article>
</div>

<form method="get" class="theme-surface border border-slate-200 bg-white p-4">
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_180px_200px_auto] lg:items-end">
        <div>
            <label for="q" class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Pencarian</label>
            <input
                type="text"
                id="q"
                name="q"
                value="<?php echo e($pencarian); ?>"
                placeholder="Cari kode, nama..."
                class="theme-focus w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400"
            >
        </div>
        <div>
            <label for="status" class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Status</label>
            <select id="status" name="status" class="theme-focus w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition">
                <option value="">Semua</option>
                <option value="aktif" <?php echo $filterStatus === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                <option value="revoked" <?php echo $filterStatus === 'revoked' ? 'selected' : ''; ?>>Revoked</option>
            </select>
        </div>
        <div>
            <label for="brand" class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Brand</label>
            <select id="brand" name="brand" class="theme-focus w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition">
                <option value="">Semua</option>
                <?php foreach ($brandList as $brand) : ?>
                    <option value="<?php echo (int) $brand['id']; ?>" <?php echo $filterBrand === (int) $brand['id'] ? 'selected' : ''; ?>>
                        <?php echo e($brand['nama_brand']); ?><?php echo (int) $brand['aktif'] === 1 ? '' : ' (Nonaktif)'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="theme-button rounded-lg px-6 py-3 text-sm font-bold shadow-sm transition">
            Filter
        </button>
    </div>
</form>

<div class="theme-surface overflow-hidden border border-slate-200 bg-white">
    <div class="border-b border-slate-200 px-5 py-4">
        <h3 class="text-base font-bold text-slate-900">Daftar Dokumen</h3>
        <p class="mt-1 text-sm text-slate-500">Menampilkan maksimal 200 dokumen terbaru sesuai filter.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Kode Unik</th>
                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Nama Dokumen</th>
                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Penerima</th>
                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Brand</th>
                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Tanggal Terbit</th>
                    <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php if (!$dokumenList) : ?>
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">Tidak ada dokumen ditemukan.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($dokumenList as $dok) : ?>
                    <tr class="transition hover:bg-slate-50">
                        <td class="whitespace-nowrap px-5 py-4">
                            <code class="rounded bg-slate-100 px-2 py-1 text-sm font-semibold text-slate-700"><?php echo e($dok['kode_unik']); ?></code>
                        </td>
                        <td class="max-w-xs px-5 py-4 text-sm font-semibold text-slate-900">
                            <span class="block truncate"><?php echo e($dok['nama_dokumen']); ?></span>
                        </td>
                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600"><?php echo e($dok['nama_penerima']); ?></td>
                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600"><?php echo e($dok['brand_penerbit']); ?></td>
                        <td class="whitespace-nowrap px-5 py-4">
                            <?php if ($dok['status'] === 'aktif') : ?>
                                <span class="inline-flex rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">Aktif</span>
                            <?php else : ?>
                                <span class="inline-flex rounded-full bg-red-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">Revoked</span>
                            <?php endif; ?>
                        </td>
                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600"><?php echo formatTanggalIndonesia($dok['tanggal_terbit']); ?></td>
                        <td class="whitespace-nowrap px-5 py-4 text-right">
                            <a href="detail.php?id=<?php echo (int) $dok['id']; ?>" class="font-semibold text-blue-800 transition hover:opacity-80">Detail</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php renderAdminLayoutEnd(); ?>
