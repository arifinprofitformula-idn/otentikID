<?php
declare(strict_types=1);

require __DIR__ . '/auth_check.php';
require __DIR__ . '/../includes/admin_layout.php';
requireRole(['superadmin', 'admin']);

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
$settings = getSettings($pdo);
$basePath = '../';

renderAdminLayoutStart($pdo, 'Dokumen Berhasil Diterbitkan', 'issue', '<a href="dashboard.php" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke Dashboard</a>');
?>

<div class="mx-auto max-w-xl rounded-xl border border-slate-200 bg-white p-6 text-center shadow-sm" id="stamp-card">
    <?php if (!empty($settings['logo_path'])) : ?>
        <img class="mx-auto mb-4 max-h-16 max-w-xs object-contain" src="<?php echo e($basePath . $settings['logo_path']); ?>" alt="<?php echo e($settings['nama_perusahaan']); ?>">
    <?php else : ?>
        <div class="mb-4 text-xl font-black text-[#d4af37]"><?php echo e($settings['nama_perusahaan']); ?></div>
    <?php endif; ?>
    <div class="mb-5 font-mono text-2xl font-black tracking-wide text-slate-900"><?php echo e($dokumen['kode_unik']); ?></div>

    <div id="qrcode-holder" class="mb-6 flex justify-center"></div>

    <dl class="grid grid-cols-1 gap-3 rounded-xl bg-slate-50 p-4 text-left sm:grid-cols-[160px_1fr]">
        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Nama Dokumen</dt>
        <dd class="text-sm font-semibold text-slate-900"><?php echo e($dokumen['nama_dokumen']); ?></dd>
        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Penerima</dt>
        <dd class="text-sm font-semibold text-slate-900"><?php echo e($dokumen['nama_penerima']); ?></dd>
        <?php if (!empty($dokumen['nomor_surat'])) : ?>
        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Nomor Surat</dt>
        <dd class="text-sm font-semibold text-slate-900"><?php echo e($dokumen['nomor_surat']); ?></dd>
        <?php endif; ?>
        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Tanggal Terbit</dt>
        <dd class="text-sm font-semibold text-slate-900"><?php echo formatTanggalIndonesia($dokumen['tanggal_terbit']); ?></dd>
        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Ditandatangani Oleh</dt>
        <dd class="text-sm font-semibold text-slate-900"><?php echo e($dokumen['nama_penandatangan']); ?><br><span class="font-normal text-slate-500"><?php echo e($dokumen['jabatan_penandatangan']); ?></span></dd>
    </dl>

    <p class="mt-4 break-all text-sm text-slate-500"><?php echo e($urlVerifikasi); ?></p>
</div>

<div class="flex flex-wrap justify-center gap-3">
    <button type="button" id="btn-download" class="theme-button rounded-lg px-6 py-3 text-sm font-bold shadow-sm transition">Download sebagai PNG</button>
    <a href="dashboard.php" class="rounded-lg border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke Dashboard</a>
</div>

<canvas id="qr-download-canvas" width="408" height="442" style="display:none;"></canvas>

<script src="<?php echo e($basePath); ?>assets/js/qrcode.min.js"></script>
<script>
(function () {
    var url = <?php echo json_encode($urlVerifikasi, JSON_UNESCAPED_SLASHES); ?>;
    var kode = <?php echo json_encode($dokumen['kode_unik']); ?>;
    var warnaAksen = <?php echo json_encode($settings['warna_aksen']); ?>;
    var logoUrl = <?php echo json_encode(!empty($settings['logo_path']) ? $basePath . $settings['logo_path'] : ''); ?>;

    var qr = qrcode(0, 'H');
    qr.addData(url);
    qr.make();

    var logoImg = null;
    if (logoUrl) {
        logoImg = new Image();
        logoImg.onload = gambarQrPreview;
        logoImg.onerror = function () { logoImg = null; gambarQrPreview(); };
        logoImg.src = logoUrl;
    }
    gambarQrPreview();

    document.getElementById('btn-download').addEventListener('click', function () {
        if (logoImg && !logoImg.complete) {
            logoImg.onload = function () { gambarQrPreview(); downloadQrCodeOnly(); };
            logoImg.onerror = function () { logoImg = null; downloadQrCodeOnly(); };
            return;
        }
        downloadQrCodeOnly();
    });

    function gambarQrPreview() {
        var holder = document.getElementById('qrcode-holder');
        var canvas = document.createElement('canvas');
        canvas.width = 300;
        canvas.height = 300;
        canvas.className = 'rounded-xl border border-slate-200 bg-white p-3 shadow-sm';
        holder.innerHTML = '';
        holder.appendChild(canvas);
        drawQrWithLogo(canvas.getContext('2d'), 12, 12, 276, logoImg);
    }

    function roundedRect(ctx, x, y, w, h, r) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + w - r, y);
        ctx.quadraticCurveTo(x + w, y, x + w, y + r);
        ctx.lineTo(x + w, y + h - r);
        ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
        ctx.lineTo(x + r, y + h);
        ctx.quadraticCurveTo(x, y + h, x, y + h - r);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);
        ctx.closePath();
    }

    function drawQrWithLogo(ctx, qrX, qrY, qrSize, logo) {
        var qrModuleCount = qr.getModuleCount();
        var cell = qrSize / qrModuleCount;

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(qrX - 10, qrY - 10, qrSize + 20, qrSize + 20);

        for (var row = 0; row < qrModuleCount; row++) {
            for (var col = 0; col < qrModuleCount; col++) {
                ctx.fillStyle = qr.isDark(row, col) ? '#000000' : '#ffffff';
                ctx.fillRect(qrX + col * cell, qrY + row * cell, cell + 0.5, cell + 0.5);
            }
        }

        var badgeSize = qrSize * 0.24;
        var badgeX = qrX + (qrSize - badgeSize) / 2;
        var badgeY = qrY + (qrSize - badgeSize) / 2;

        ctx.save();
        roundedRect(ctx, badgeX, badgeY, badgeSize, badgeSize, badgeSize * 0.18);
        ctx.fillStyle = '#ffffff';
        ctx.fill();
        ctx.lineWidth = Math.max(3, qrSize * 0.015);
        ctx.strokeStyle = '#ffffff';
        ctx.stroke();

        if (logo) {
            var padding = badgeSize * 0.16;
            var maxW = badgeSize - padding * 2;
            var maxH = badgeSize - padding * 2;
            var ratio = Math.min(maxW / logo.width, maxH / logo.height);
            var logoW = logo.width * ratio;
            var logoH = logo.height * ratio;
            ctx.drawImage(logo, badgeX + (badgeSize - logoW) / 2, badgeY + (badgeSize - logoH) / 2, logoW, logoH);
        } else {
            ctx.fillStyle = warnaAksen || '#d4af37';
            ctx.font = 'bold ' + Math.floor(badgeSize * 0.28) + 'px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('EPI', badgeX + badgeSize / 2, badgeY + badgeSize / 2);
        }
        ctx.restore();
    }

    function downloadQrCodeOnly() {
        var canvas = document.getElementById('qr-download-canvas');
        var ctx = canvas.getContext('2d');
        var qrSize = 360;
        var padding = 24;
        var codeGap = 14;
        var codeFontSize = 9;
        var codeLineHeight = 12;
        var W = qrSize + padding * 2;
        var H = qrSize + padding * 2 + codeGap + codeLineHeight;

        canvas.width = W;
        canvas.height = H;

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, W, H);

        drawQrWithLogo(ctx, padding, padding, qrSize, logoImg);

        ctx.fillStyle = '#111827';
        ctx.font = codeFontSize + 'px monospace';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';
        ctx.fillText(kode, W / 2, padding + qrSize + codeGap);

        var link = document.createElement('a');
        link.download = 'qrcode-' + kode + '.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    }
})();
</script>

<?php renderAdminLayoutEnd(); ?>
