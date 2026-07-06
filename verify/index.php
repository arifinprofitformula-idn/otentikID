<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/functions.php';

$kode = trim((string) ($_GET['kode'] ?? ''));
$hasilCek = null;
$dokumen = null;

if ($kode !== '') {
    $stmt = $pdo->prepare('SELECT * FROM documents WHERE kode_unik = :kode_unik');
    $stmt->execute(['kode_unik' => $kode]);
    $dokumen = $stmt->fetch();

    if (!$dokumen) {
        $hasilCek = 'tidak_ditemukan';
    } elseif ($dokumen['status'] === 'revoked') {
        $hasilCek = 'revoked';
    } else {
        $hasilCek = 'valid';
    }

    $stmtLog = $pdo->prepare(
        'INSERT INTO verification_logs (document_id, kode_dicek, hasil, ip_address)
         VALUES (:document_id, :kode_dicek, :hasil, :ip_address)'
    );
    $stmtLog->execute([
        'document_id' => $dokumen['id'] ?? null,
        'kode_dicek' => $kode,
        'hasil' => $hasilCek,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

$pageTitle = 'Verifikasi Dokumen';
$basePath = '../';
require __DIR__ . '/../includes/header.php';
?>

<div class="verify-wrap">
    <?php if ($hasilCek === null) : ?>
        <div class="card verify-form-card">
            <h1>Verifikasi Keabsahan Dokumen</h1>
            <p class="text-muted">Masukkan kode unik yang tertera pada dokumen, atau pindai QR code pada dokumen.</p>

            <form method="get" class="verify-form">
                <div class="form-group">
                    <label for="kode">Kode Unik Dokumen</label>
                    <input type="text" id="kode" name="kode" placeholder="EPI-2026-XXXXXX" required autofocus autocapitalize="characters">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Verifikasi</button>
            </form>

            <button type="button" id="btn-toggle-scan" class="btn btn-secondary btn-block">Scan QR pakai Kamera</button>

            <!-- TODO: pemindaian kamera memerlukan koneksi internet di sisi browser untuk memuat
                 pustaka html5-qrcode dari CDN. Jika perangkat/browser tidak memiliki akses internet,
                 gunakan input kode manual di atas. -->
            <div id="scan-area" class="scan-area" style="display:none;">
                <div id="qr-reader"></div>
                <p class="text-muted text-small">Arahkan kamera ke QR code pada dokumen.</p>
            </div>
        </div>
    <?php elseif ($hasilCek === 'valid') : ?>
        <div class="card result-card result-valid">
            <div class="result-icon">&#10004;</div>
            <h1>Dokumen Valid</h1>
            <p>Dokumen ini terdaftar dan sah diterbitkan oleh sistem kami.</p>

            <dl class="detail-grid">
                <dt>Kode Unik</dt>
                <dd><code><?php echo e($dokumen['kode_unik']); ?></code></dd>
                <dt>Nama Dokumen</dt>
                <dd><?php echo e($dokumen['nama_dokumen']); ?></dd>
                <dt>Jenis Dokumen</dt>
                <dd><?php echo e($dokumen['jenis_dokumen']); ?></dd>
                <dt>Brand Penerbit</dt>
                <dd><?php echo e($dokumen['brand_penerbit']); ?></dd>
                <dt>Nama Penerima</dt>
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
        </div>
    <?php elseif ($hasilCek === 'revoked') : ?>
        <div class="card result-card result-revoked">
            <div class="result-icon">&#9888;</div>
            <h1>Dokumen Telah Dibatalkan</h1>
            <p>Dokumen ini telah dibatalkan penerbitnya dan tidak lagi berlaku.</p>
            <dl class="detail-grid">
                <dt>Kode Unik</dt>
                <dd><code><?php echo e($dokumen['kode_unik']); ?></code></dd>
                <dt>Tanggal Dibatalkan</dt>
                <dd><?php echo formatTanggalIndonesia($dokumen['direvoke_pada']); ?></dd>
            </dl>
        </div>
    <?php else : ?>
        <div class="card result-card result-notfound">
            <div class="result-icon">?</div>
            <h1>Kode Tidak Ditemukan</h1>
            <p>Kode tidak ditemukan dalam sistem kami. Pastikan kode yang dimasukkan sudah benar.</p>
        </div>
    <?php endif; ?>

    <?php if ($hasilCek !== null) : ?>
        <div class="actions-row">
            <a href="./" class="btn btn-secondary">Verifikasi Kode Lain</a>
        </div>
    <?php endif; ?>
</div>

<?php if ($hasilCek === null) : ?>
<script>
document.getElementById('btn-toggle-scan').addEventListener('click', function () {
    var area = document.getElementById('scan-area');
    if (area.style.display === 'none') {
        area.style.display = 'block';
        var script = document.createElement('script');
        script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
        script.onload = function () {
            var scanner = new Html5QrcodeScanner('qr-reader', { fps: 10, qrbox: 250 });
            scanner.render(function (decodedText) {
                var url = new URL(window.location.href);
                var kodeHasil = decodedText;
                try {
                    var parsed = new URL(decodedText);
                    kodeHasil = parsed.searchParams.get('kode') || decodedText;
                } catch (e) {}
                window.location.href = './?kode=' + encodeURIComponent(kodeHasil);
            });
        };
        script.onerror = function () {
            area.innerHTML = '<p class="text-muted">Pemindaian kamera memerlukan koneksi internet dan tidak tersedia saat ini. Silakan gunakan input kode manual.</p>';
        };
        document.head.appendChild(script);
        this.style.display = 'none';
    }
});
</script>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
