<?php
declare(strict_types=1);

require __DIR__ . '/auth_check.php';

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

$pageTitle = 'Detail Dokumen';
$basePath = '../';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Detail Dokumen</h1>
    <a href="dashboard.php" class="btn btn-link">&larr; Kembali ke Dashboard</a>
</div>

<?php if ($pesanSukses !== '') : ?>
    <div class="alert alert-success"><?php echo e($pesanSukses); ?></div>
<?php endif; ?>

<?php if ($dokumen['status'] === 'revoked') : ?>
    <div class="alert alert-warning">
        <strong>Dokumen ini telah dibatalkan.</strong><br>
        Alasan: <?php echo e($dokumen['alasan_revoke'] ?? '-'); ?><br>
        Tanggal dibatalkan: <?php echo formatTanggalWaktuIndonesia($dokumen['direvoke_pada']); ?>
    </div>
<?php endif; ?>

<div class="card">
    <dl class="detail-grid">
        <dt>Kode Unik</dt>
        <dd><code><?php echo e($dokumen['kode_unik']); ?></code></dd>

        <dt>Status</dt>
        <dd>
            <?php if ($dokumen['status'] === 'aktif') : ?>
                <span class="badge badge-success">Aktif</span>
            <?php else : ?>
                <span class="badge badge-danger">Revoked</span>
            <?php endif; ?>
        </dd>

        <dt>Nama Dokumen</dt>
        <dd><?php echo e($dokumen['nama_dokumen']); ?></dd>

        <dt>Jenis Dokumen</dt>
        <dd><?php echo e($dokumen['jenis_dokumen']); ?></dd>

        <dt>Brand Penerbit</dt>
        <dd><?php echo e($dokumen['brand_penerbit']); ?></dd>

        <dt>Nama Penerima</dt>
        <dd><?php echo e($dokumen['nama_penerima']); ?></dd>

        <dt>Nomor Surat</dt>
        <dd><?php echo $dokumen['nomor_surat'] !== null && $dokumen['nomor_surat'] !== '' ? e($dokumen['nomor_surat']) : '-'; ?></dd>

        <dt>Nama Penandatangan</dt>
        <dd><?php echo e($dokumen['nama_penandatangan']); ?></dd>

        <dt>Jabatan Penandatangan</dt>
        <dd><?php echo e($dokumen['jabatan_penandatangan']); ?></dd>

        <dt>Tanggal Terbit</dt>
        <dd><?php echo formatTanggalIndonesia($dokumen['tanggal_terbit']); ?></dd>

        <dt>Catatan</dt>
        <dd><?php echo $dokumen['catatan'] !== null && $dokumen['catatan'] !== '' ? nl2br(e($dokumen['catatan'])) : '-'; ?></dd>

        <dt>Hash Dokumen</dt>
        <dd><code class="text-small"><?php echo e($dokumen['hash_dokumen']); ?></code></dd>

        <dt>Diterbitkan Oleh</dt>
        <dd><?php echo e($dokumen['diterbitkan_oleh_nama'] ?? '-'); ?></dd>

        <dt>Dibuat Pada</dt>
        <dd><?php echo formatTanggalWaktuIndonesia($dokumen['dibuat_pada']); ?></dd>
    </dl>

    <?php if ($dokumen['status'] === 'aktif') : ?>
        <a href="revoke.php?id=<?php echo (int) $dokumen['id']; ?>" class="btn btn-danger">Batalkan Dokumen Ini</a>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Riwayat Verifikasi</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tanggal Cek</th>
                    <th>Hasil</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$riwayat) : ?>
                    <tr>
                        <td colspan="3" class="text-muted text-center">Belum ada riwayat verifikasi.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($riwayat as $log) : ?>
                    <tr>
                        <td><?php echo formatTanggalWaktuIndonesia($log['dicek_pada']); ?></td>
                        <td>
                            <?php
                            $labelHasil = [
                                'valid' => '<span class="badge badge-success">Valid</span>',
                                'revoked' => '<span class="badge badge-danger">Revoked</span>',
                                'tidak_ditemukan' => '<span class="badge badge-muted">Tidak Ditemukan</span>',
                            ];
                            echo $labelHasil[$log['hasil']] ?? e($log['hasil']);
                            ?>
                        </td>
                        <td><?php echo e(samarkanIp($log['ip_address'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
