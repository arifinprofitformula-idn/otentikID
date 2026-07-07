<?php
declare(strict_types=1);

require __DIR__ . '/auth_check.php';
require __DIR__ . '/../includes/admin_layout.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT d.*, a.nama_lengkap AS diterbitkan_oleh_nama
     FROM documents d
     LEFT JOIN admins a ON a.id = d.diterbitkan_oleh
     WHERE d.id = :id'
);
$stmt->execute(['id' => $id]);
$dokumen = $stmt->fetch();

if (!$dokumen) {
    header('Location: dashboard.php');
    exit;
}

$stmtLog = $pdo->prepare(
    'SELECT hasil, ip_address, dicek_pada
     FROM verification_logs
     WHERE document_id = :document_id
     ORDER BY dicek_pada DESC
     LIMIT 100'
);
$stmtLog->execute(['document_id' => $id]);
$riwayat = $stmtLog->fetchAll();

$pesanSukses = trim((string) ($_GET['sukses'] ?? '')) === '1' ? 'Dokumen berhasil dibatalkan.' : '';

renderAdminLayoutStart($pdo, 'Detail Dokumen', 'dashboard', '<a href="dashboard.php" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">&larr; Kembali ke Dashboard</a>');
?>

<?php if ($pesanSukses !== '') : ?>
    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"><?php echo e($pesanSukses); ?></div>
<?php endif; ?>

<?php if ($dokumen['status'] === 'revoked') : ?>
    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <strong>Dokumen ini telah dibatalkan.</strong><br>
        Alasan: <?php echo e($dokumen['alasan_revoke'] ?? '-'); ?><br>
        Tanggal dibatalkan: <?php echo formatTanggalWaktuIndonesia($dokumen['direvoke_pada']); ?>
    </div>
<?php endif; ?>

<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <dl class="grid grid-cols-1 gap-4 md:grid-cols-[220px_1fr]">
        <dt class="text-sm font-bold text-slate-500">Kode Unik</dt>
        <dd><code class="rounded bg-slate-100 px-2 py-1 text-sm font-semibold text-slate-700"><?php echo e($dokumen['kode_unik']); ?></code></dd>

        <dt class="text-sm font-bold text-slate-500">Status</dt>
        <dd>
            <?php if ($dokumen['status'] === 'aktif') : ?>
                <span class="inline-flex rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">Aktif</span>
            <?php else : ?>
                <span class="inline-flex rounded-full bg-red-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">Revoked</span>
            <?php endif; ?>
        </dd>

        <?php
        $details = [
            'Nama Dokumen' => e($dokumen['nama_dokumen']),
            'Jenis Dokumen' => e($dokumen['jenis_dokumen']),
            'Brand Penerbit' => e($dokumen['brand_penerbit']),
            'Nama Penerima' => e($dokumen['nama_penerima']),
            'Nomor Surat' => $dokumen['nomor_surat'] !== null && $dokumen['nomor_surat'] !== '' ? e($dokumen['nomor_surat']) : '-',
            'Nama Penandatangan' => e($dokumen['nama_penandatangan']),
            'Jabatan Penandatangan' => e($dokumen['jabatan_penandatangan']),
            'Tanggal Terbit' => formatTanggalIndonesia($dokumen['tanggal_terbit']),
            'Catatan' => $dokumen['catatan'] !== null && $dokumen['catatan'] !== '' ? nl2br(e($dokumen['catatan'])) : '-',
            'Hash Dokumen' => '<code class="break-all rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">' . e($dokumen['hash_dokumen']) . '</code>',
            'Diterbitkan Oleh' => e($dokumen['diterbitkan_oleh_nama'] ?? '-'),
            'Dibuat Pada' => formatTanggalWaktuIndonesia($dokumen['dibuat_pada']),
        ];
        foreach ($details as $label => $value) :
        ?>
            <dt class="text-sm font-bold text-slate-500"><?php echo e($label); ?></dt>
            <dd class="text-sm text-slate-900"><?php echo $value; ?></dd>
        <?php endforeach; ?>
    </dl>

    <?php if ($dokumen['status'] === 'aktif') : ?>
        <div class="mt-6 border-t border-slate-200 pt-6">
            <a href="revoke.php?id=<?php echo (int) $dokumen['id']; ?>" class="rounded-lg bg-red-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-red-700">Batalkan Dokumen Ini</a>
        </div>
    <?php endif; ?>
</div>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4">
        <h2 class="text-base font-bold text-slate-900">Riwayat Verifikasi</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Tanggal Cek</th>
                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Hasil</th>
                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php if (!$riwayat) : ?>
                    <tr>
                        <td colspan="3" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada riwayat verifikasi.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($riwayat as $log) : ?>
                    <tr class="transition hover:bg-slate-50">
                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600"><?php echo formatTanggalWaktuIndonesia($log['dicek_pada']); ?></td>
                        <td class="whitespace-nowrap px-5 py-4">
                            <?php
                            $labelHasil = [
                                'valid' => '<span class="inline-flex rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">Valid</span>',
                                'revoked' => '<span class="inline-flex rounded-full bg-red-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">Revoked</span>',
                                'tidak_ditemukan' => '<span class="inline-flex rounded-full bg-slate-500 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">Tidak Ditemukan</span>',
                            ];
                            echo $labelHasil[$log['hasil']] ?? e($log['hasil']);
                            ?>
                        </td>
                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600"><?php echo e(samarkanIp($log['ip_address'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php renderAdminLayoutEnd(); ?>
