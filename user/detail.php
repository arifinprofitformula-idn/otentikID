<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/user_layout.php';

requireRole(['user']);

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM documents WHERE id = :id AND pemilik_id = :pemilik_id');
$stmt->execute([
    'id' => $id,
    'pemilik_id' => (int) $_SESSION['admin_id'],
]);
$dokumen = $stmt->fetch();

if (!$dokumen) {
    header('Location: dashboard.php');
    exit;
}

renderUserLayoutStart($pdo, 'Detail Dokumen', 'dashboard');
?>

<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <dl class="grid grid-cols-1 gap-4 md:grid-cols-[220px_1fr]">
        <?php
        $items = [
            'Kode Unik' => '<code class="rounded bg-slate-100 px-2 py-1 text-sm font-semibold">' . e($dokumen['kode_unik']) . '</code>',
            'Status' => $dokumen['status'] === 'aktif'
                ? '<span class="inline-flex rounded-full bg-emerald-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">Aktif</span>'
                : '<span class="inline-flex rounded-full bg-red-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">Revoked</span>',
            'Nama Dokumen' => e($dokumen['nama_dokumen']),
            'Jenis Dokumen' => e($dokumen['jenis_dokumen']),
            'Brand Penerbit' => e($dokumen['brand_penerbit']),
            'Nama Penerima' => e($dokumen['nama_penerima']),
            'Nomor Surat' => $dokumen['nomor_surat'] ? e($dokumen['nomor_surat']) : '-',
            'Tanggal Terbit' => formatTanggalIndonesia($dokumen['tanggal_terbit']),
            'Penandatangan' => e($dokumen['nama_penandatangan']) . '<br><span class="text-sm text-slate-500">' . e($dokumen['jabatan_penandatangan']) . '</span>',
        ];
        foreach ($items as $label => $value) :
        ?>
            <dt class="text-sm font-bold text-slate-500"><?php echo e($label); ?></dt>
            <dd class="text-sm text-slate-900"><?php echo $value; ?></dd>
        <?php endforeach; ?>
    </dl>
    <div class="mt-6 border-t border-slate-200 pt-6">
        <a href="../verify/?kode=<?php echo rawurlencode($dokumen['kode_unik']); ?>" class="user-button rounded-lg px-5 py-3 text-sm font-bold shadow-sm transition">Buka Halaman Verifikasi</a>
        <a href="dashboard.php" class="ml-2 rounded-lg border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kembali</a>
    </div>
</div>

<?php renderUserLayoutEnd(); ?>
