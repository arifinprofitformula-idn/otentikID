<?php
declare(strict_types=1);

require __DIR__ . '/auth_check.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM documents WHERE id = :id');
$stmt->execute(['id' => $id]);
$dokumen = $stmt->fetch();

if (!$dokumen) {
    header('Location: dashboard.php');
    exit;
}

$domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
$skema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$urlVerifikasi = $skema . '://' . $domain . '/verify/?kode=' . rawurlencode($dokumen['kode_unik']);

$pageTitle = 'Dokumen Berhasil Diterbitkan';
$basePath = '../';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Dokumen Berhasil Diterbitkan</h1>
</div>

<div class="card stamp-card" id="stamp-card">
    <div class="stamp-logo">EPI Verified</div>
    <div class="stamp-kode"><?php echo e($dokumen['kode_unik']); ?></div>

    <div id="qrcode-holder" class="qrcode-holder"></div>

    <dl class="stamp-detail">
        <dt>Nama Dokumen</dt>
        <dd><?php echo e($dokumen['nama_dokumen']); ?></dd>
        <dt>Penerima</dt>
        <dd><?php echo e($dokumen['nama_penerima']); ?></dd>
        <dt>Tanggal Terbit</dt>
        <dd><?php echo formatTanggalIndonesia($dokumen['tanggal_terbit']); ?></dd>
    </dl>

    <p class="text-muted stamp-url"><?php echo e($urlVerifikasi); ?></p>
</div>

<div class="actions-row">
    <button type="button" id="btn-download" class="btn btn-primary">Download sebagai PNG</button>
    <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
</div>

<canvas id="stamp-canvas" width="640" height="820" style="display:none;"></canvas>

<script src="<?php echo e($basePath); ?>assets/js/qrcode.min.js"></script>
<script>
(function () {
    var url = <?php echo json_encode($urlVerifikasi, JSON_UNESCAPED_SLASHES); ?>;
    var kode = <?php echo json_encode($dokumen['kode_unik']); ?>;
    var namaDokumen = <?php echo json_encode($dokumen['nama_dokumen']); ?>;
    var namaPenerima = <?php echo json_encode($dokumen['nama_penerima']); ?>;
    var tanggalTerbit = <?php echo json_encode(formatTanggalIndonesia($dokumen['tanggal_terbit'])); ?>;

    var qr = qrcode(0, 'M');
    qr.addData(url);
    qr.make();

    document.getElementById('qrcode-holder').innerHTML = qr.createSvgTag(6, 0);

    document.getElementById('btn-download').addEventListener('click', function () {
        var canvas = document.getElementById('stamp-canvas');
        var ctx = canvas.getContext('2d');
        var W = canvas.width, H = canvas.height;

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, W, H);

        ctx.strokeStyle = '#1e3a5f';
        ctx.lineWidth = 6;
        ctx.strokeRect(12, 12, W - 24, H - 24);

        ctx.fillStyle = '#1e3a5f';
        ctx.font = 'bold 28px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('EPI Verified', W / 2, 70);

        ctx.font = 'bold 32px monospace';
        ctx.fillText(kode, W / 2, 120);

        var qrModuleCount = qr.getModuleCount();
        var qrSize = 380;
        var cell = qrSize / qrModuleCount;
        var qrX = (W - qrSize) / 2;
        var qrY = 150;

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(qrX - 10, qrY - 10, qrSize + 20, qrSize + 20);

        for (var row = 0; row < qrModuleCount; row++) {
            for (var col = 0; col < qrModuleCount; col++) {
                ctx.fillStyle = qr.isDark(row, col) ? '#000000' : '#ffffff';
                ctx.fillRect(qrX + col * cell, qrY + row * cell, cell + 0.5, cell + 0.5);
            }
        }

        var textY = qrY + qrSize + 50;
        ctx.font = '18px sans-serif';
        ctx.fillStyle = '#333333';

        function wrapAndDraw(label, value, y) {
            ctx.font = 'bold 16px sans-serif';
            ctx.fillStyle = '#666666';
            ctx.fillText(label, W / 2, y);
            ctx.font = '20px sans-serif';
            ctx.fillStyle = '#111111';
            ctx.fillText(value, W / 2, y + 26);
        }

        wrapAndDraw('NAMA DOKUMEN', namaDokumen, textY);
        wrapAndDraw('PENERIMA', namaPenerima, textY + 70);
        wrapAndDraw('TANGGAL TERBIT', tanggalTerbit, textY + 140);

        ctx.font = '14px sans-serif';
        ctx.fillStyle = '#888888';
        ctx.fillText(url, W / 2, H - 30);

        var link = document.createElement('a');
        link.download = 'stempel-' + kode + '.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    });
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
