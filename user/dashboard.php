<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/user_layout.php';

requireRole(['user']);

$stmt = $pdo->prepare(
    'SELECT id, kode_unik, nama_dokumen, jenis_dokumen, brand_penerbit, status, tanggal_terbit, dibuat_pada
     FROM documents
     WHERE pemilik_id = :pemilik_id
     ORDER BY dibuat_pada DESC'
);
$stmt->execute(['pemilik_id' => (int) $_SESSION['admin_id']]);
$dokumenList = $stmt->fetchAll();

renderUserLayoutStart($pdo, 'Dokumen Saya', 'dashboard');
?>

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4">
        <h3 class="text-base font-bold text-slate-900">Daftar Dokumen Milik Anda</h3>
        <p class="mt-1 text-sm text-slate-500">Hanya dokumen yang ditautkan ke akun Anda yang ditampilkan di sini.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Kode</th>
                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Dokumen</th>
                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Brand</th>
                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php if (!$dokumenList) : ?>
                    <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada dokumen yang ditautkan ke akun Anda.</td></tr>
                <?php endif; ?>
                <?php foreach ($dokumenList as $dok) : ?>
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap px-5 py-4"><code class="rounded bg-slate-100 px-2 py-1 text-sm font-semibold"><?php echo e($dok['kode_unik']); ?></code></td>
                        <td class="px-5 py-4">
                            <p class="text-sm font-semibold text-slate-900"><?php echo e($dok['nama_dokumen']); ?></p>
                            <p class="text-xs text-slate-500"><?php echo e($dok['jenis_dokumen']); ?> · <?php echo formatTanggalIndonesia($dok['tanggal_terbit']); ?></p>
                        </td>
                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600"><?php echo e($dok['brand_penerbit']); ?></td>
                        <td class="whitespace-nowrap px-5 py-4">
                            <?php if ($dok['status'] === 'aktif') : ?>
                                <span class="inline-flex rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">Aktif</span>
                            <?php else : ?>
                                <span class="inline-flex rounded-full bg-red-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">Revoked</span>
                            <?php endif; ?>
                        </td>
                        <td class="whitespace-nowrap px-5 py-4 text-right"><a href="detail.php?id=<?php echo (int) $dok['id']; ?>" class="font-semibold text-blue-800 hover:opacity-80">Detail</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php renderUserLayoutEnd(); ?>
