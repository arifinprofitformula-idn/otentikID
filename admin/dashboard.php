<?php
declare(strict_types=1);

require __DIR__ . '/auth_check.php';

$brandList = ['GOLDGRAM', 'MEEZAN GOLD', 'SILVERGRAM', 'Katalisis', 'Umum', 'Personal'];

$filterStatus = trim((string) ($_GET['status'] ?? ''));
$filterBrand = trim((string) ($_GET['brand'] ?? ''));
$pencarian = trim((string) ($_GET['q'] ?? ''));

$kondisi = [];
$parameter = [];

if ($filterStatus === 'aktif' || $filterStatus === 'revoked') {
    $kondisi[] = 'status = :status';
    $parameter['status'] = $filterStatus;
}

if (in_array($filterBrand, $brandList, true)) {
    $kondisi[] = 'brand_penerbit = :brand';
    $parameter['brand'] = $filterBrand;
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

$pageTitle = 'Dashboard';
$basePath = '../';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Dashboard</h1>
    <a href="issue.php" class="btn btn-primary">+ Terbitkan Dokumen Baru</a>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <span class="stat-value"><?php echo $totalAktif; ?></span>
        <span class="stat-label">Total Dokumen Aktif</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?php echo $totalRevoked; ?></span>
        <span class="stat-label">Total Direvoke</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?php echo $totalVerifikasi30Hari; ?></span>
        <span class="stat-label">Total Verifikasi 30 Hari Terakhir</span>
    </div>
</div>

<div class="card">
    <form method="get" class="filter-bar">
        <div class="form-group">
            <label for="q">Cari kode / penerima / nomor surat</label>
            <input type="text" id="q" name="q" value="<?php echo e($pencarian); ?>" placeholder="EPI-2026-XXXXXX, nama, atau nomor surat">
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Semua</option>
                <option value="aktif" <?php echo $filterStatus === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                <option value="revoked" <?php echo $filterStatus === 'revoked' ? 'selected' : ''; ?>>Revoked</option>
            </select>
        </div>
        <div class="form-group">
            <label for="brand">Brand</label>
            <select id="brand" name="brand">
                <option value="">Semua</option>
                <?php foreach ($brandList as $brand) : ?>
                    <option value="<?php echo e($brand); ?>" <?php echo $filterBrand === $brand ? 'selected' : ''; ?>><?php echo e($brand); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group form-group-btn">
            <button type="submit" class="btn btn-secondary">Filter</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kode Unik</th>
                    <th>Nama Dokumen</th>
                    <th>Penerima</th>
                    <th>Brand</th>
                    <th>Status</th>
                    <th>Tanggal Terbit</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$dokumenList) : ?>
                    <tr>
                        <td colspan="7" class="text-muted text-center">Tidak ada dokumen ditemukan.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($dokumenList as $dok) : ?>
                    <tr>
                        <td><code><?php echo e($dok['kode_unik']); ?></code></td>
                        <td><?php echo e($dok['nama_dokumen']); ?></td>
                        <td><?php echo e($dok['nama_penerima']); ?></td>
                        <td><?php echo e($dok['brand_penerbit']); ?></td>
                        <td>
                            <?php if ($dok['status'] === 'aktif') : ?>
                                <span class="badge badge-success">Aktif</span>
                            <?php else : ?>
                                <span class="badge badge-danger">Revoked</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatTanggalIndonesia($dok['tanggal_terbit']); ?></td>
                        <td><a href="detail.php?id=<?php echo (int) $dok['id']; ?>">Detail</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
