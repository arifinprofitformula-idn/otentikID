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
    <?php if (!empty($settings['logo_path'])) : ?>
        <img class="stamp-logo-img" src="<?php echo e($basePath . $settings['logo_path']); ?>" alt="<?php echo e($settings['nama_perusahaan']); ?>">
    <?php else : ?>
        <div class="stamp-logo"><?php echo e($settings['nama_perusahaan']); ?></div>
    <?php endif; ?>
    <div class="stamp-kode"><?php echo e($dokumen['kode_unik']); ?></div>

    <div id="qrcode-holder" class="qrcode-holder"></div>

    <dl class="stamp-detail">
        <dt>Nama Dokumen</dt>
        <dd><?php echo e($dokumen['nama_dokumen']); ?></dd>
        <dt>Penerima</dt>
        <dd><?php echo e($dokumen['nama_penerima']); ?></dd>
        <?php if (!empty($dokumen['nomor_surat'])) : ?>
        <dt>Nomor Surat</dt>
        <dd><?php echo e($dokumen['nomor_surat']); ?></dd>
        <?php endif; ?>
        <dt>Tanggal Terbit</dt>
        <dd><?php echo formatTanggalIndonesia($dokumen['tanggal_terbit']); ?></dd>
        <dt>Ditandatangani Oleh</dt>
        <dd><?php echo e($dokumen['nama_penandatangan']); ?><br><span class="text-muted"><?php echo e($dokumen['jabatan_penandatangan']); ?></span></dd>
    </dl>

    <p class="text-muted stamp-url"><?php echo e($urlVerifikasi); ?></p>
</div>

<div class="actions-row">
    <button type="button" id="btn-download" class="btn btn-primary">Download sebagai PNG</button>
    <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
</div>

<canvas id="stamp-canvas" width="640" height="960" style="display:none;"></canvas>

<script src="<?php echo e($basePath); ?>assets/js/qrcode.min.js"></script>
<script>
(function () {
    var url = <?php echo json_encode($urlVerifikasi, JSON_UNESCAPED_SLASHES); ?>;
    var kode = <?php echo json_encode($dokumen['kode_unik']); ?>;
    var namaDokumen = <?php echo json_encode($dokumen['nama_dokumen']); ?>;
    var namaPenerima = <?php echo json_encode($dokumen['nama_penerima']); ?>;
    var nomorSurat = <?php echo json_encode($dokumen['nomor_surat'] ?? ''); ?>;
    var namaPenandatangan = <?php echo json_encode($dokumen['nama_penandatangan']); ?>;
    var jabatanPenandatangan = <?php echo json_encode($dokumen['jabatan_penandatangan']); ?>;
    var tanggalTerbit = <?php echo json_encode(formatTanggalIndonesia($dokumen['tanggal_terbit'])); ?>;
    var namaPerusahaan = <?php echo json_encode($settings['nama_perusahaan']); ?>;
    var warnaAksen = <?php echo json_encode($settings['warna_aksen']); ?>;
    var logoUrl = <?php echo json_encode(!empty($settings['logo_path']) ? $basePath . $settings['logo_path'] : ''); ?>;

    var qr = qrcode(0, 'M');
    qr.addData(url);
    qr.make();

    document.getElementById('qrcode-holder').innerHTML = qr.createSvgTag(6, 0);

    var logoImg = null;
    if (logoUrl) {
        logoImg = new Image();
        logoImg.src = logoUrl;
    }

    document.getElementById('btn-download').addEventListener('click', function () {
        if (logoImg && !logoImg.complete) {
            logoImg.onload = gambarStempel;
            logoImg.onerror = function () { logoImg = null; gambarStempel(); };
            return;
        }
        gambarStempel();
    });

    function gambarStempel() {
        var canvas = document.getElementById('stamp-canvas');
        var ctx = canvas.getContext('2d');
        var W = canvas.width, H = canvas.height;

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, W, H);

        ctx.strokeStyle = warnaAksen;
        ctx.lineWidth = 6;
        ctx.strokeRect(12, 12, W - 24, H - 24);

        ctx.fillStyle = warnaAksen;
        ctx.textAlign = 'center';

        if (logoImg) {
            var logoH = 60;
            var logoW = Math.min(260, logoImg.width * (logoH / logoImg.height));
            ctx.drawImage(logoImg, (W - logoW) / 2, 20, logoW, logoH);
        } else {
            ctx.font = 'bold 28px sans-serif';
            ctx.fillText(namaPerusahaan, W / 2, 70);
        }

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
        var barisBerikut = textY + 140;
        if (nomorSurat) {
            wrapAndDraw('NOMOR SURAT', nomorSurat, barisBerikut);
            barisBerikut += 70;
        }
        wrapAndDraw('TANGGAL TERBIT', tanggalTerbit, barisBerikut);
        barisBerikut += 70;
        wrapAndDraw('DITANDATANGANI OLEH', namaPenandatangan + ' (' + jabatanPenandatangan + ')', barisBerikut);

        ctx.font = '14px sans-serif';
        ctx.fillStyle = '#888888';
        ctx.fillText(url, W / 2, H - 30);

        var link = document.createElement('a');
        link.download = 'stempel-' + kode + '.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    }
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
