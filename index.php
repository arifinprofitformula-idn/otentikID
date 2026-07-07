<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/includes/functions.php';

$settings = getSettings($pdo);
$warnaAksenGelap = warnaLebihGelap($settings['warna_aksen']);
$pageTitle = 'Beranda';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($settings['nama_perusahaan']); ?> - <?php echo e($settings['tagline']); ?></title>
    <meta name="description" content="<?php echo e($settings['nama_perusahaan']); ?> - Terbitkan dan verifikasi keabsahan dokumen resmi secara instan lewat kode unik dan QR code.">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --color-primary: <?php echo e($settings['warna_aksen']); ?>;
            --color-primary-dark: <?php echo e($warnaAksenGelap); ?>;
        }
    </style>
</head>
<body class="landing-page">

    <section class="landing-hero">
        <div class="container">
            <div class="landing-hero-logo">
                <?php if (!empty($settings['logo_path'])) : ?>
                    <img src="<?php echo e($settings['logo_path']); ?>" alt="<?php echo e($settings['nama_perusahaan']); ?>">
                <?php else : ?>
                    <span class="brand-mark-lg"><?php echo e(mb_strtoupper(mb_substr($settings['nama_perusahaan'], 0, 2))); ?></span>
                <?php endif; ?>
            </div>

            <h1>Lindungi Keaslian Setiap Dokumen Anda</h1>
            <p class="landing-tagline">
                <?php echo e($settings['nama_perusahaan']); ?> membantu instansi dan perusahaan menerbitkan serta
                memverifikasi keabsahan dokumen resmi secara instan lewat kode unik dan QR code
                &mdash; cepat, aman, dan bisa dicek siapa saja, kapan saja.
            </p>

            <div class="landing-hero-actions">
                <a href="verify/" class="btn btn-primary">Verifikasi Dokumen</a>
                <a href="admin/login.php" class="btn btn-secondary">Masuk Admin</a>
            </div>
        </div>
    </section>

    <section class="landing-section">
        <div class="container">
            <h2>Mengapa Verifikasi Dokumen Itu Penting?</h2>
            <p class="section-lead">
                Pemalsuan dokumen resmi seperti sertifikat, surat keputusan, dan ijazah semakin marak
                dan merugikan banyak pihak. Sistem verifikasi digital hadir sebagai solusi untuk
                memastikan setiap dokumen yang beredar benar-benar sah.
            </p>

            <div class="why-grid">
                <div class="why-card">
                    <div class="why-icon">&#128737;&#65039;</div>
                    <h3>Cegah Pemalsuan</h3>
                    <p>Setiap dokumen memiliki kode unik dan hash yang tidak bisa diduplikasi atau dipalsukan.</p>
                </div>
                <div class="why-card">
                    <div class="why-icon">&#9889;</div>
                    <h3>Verifikasi Instan</h3>
                    <p>Cukup pindai QR code atau masukkan kode, keabsahan dokumen langsung terlihat dalam hitungan detik.</p>
                </div>
                <div class="why-card">
                    <div class="why-icon">&#127760;</div>
                    <h3>Bisa Diakses Siapa Saja</h3>
                    <p>Tanpa perlu login atau instalasi aplikasi khusus &mdash; verifikasi terbuka untuk publik, kapan pun dan di mana pun.</p>
                </div>
                <div class="why-card">
                    <div class="why-icon">&#128202;</div>
                    <h3>Tercatat &amp; Transparan</h3>
                    <p>Setiap upaya verifikasi tercatat dalam sistem, memberi jejak audit yang jelas bagi penerbit dokumen.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="landing-section landing-section-alt">
        <div class="container">
            <h2>Bagaimana Cara Kerjanya?</h2>
            <p class="section-lead">Tiga langkah sederhana dari penerbitan hingga verifikasi dokumen.</p>

            <div class="steps">
                <div class="step">
                    <span class="step-number">1</span>
                    <h3>Dokumen Diterbitkan</h3>
                    <p>Admin menerbitkan dokumen resmi lengkap dengan kode unik dan QR code yang tertaut ke sistem.</p>
                </div>
                <div class="step">
                    <span class="step-number">2</span>
                    <h3>Kode &amp; QR Disematkan</h3>
                    <p>Kode unik dan QR code dicetak atau ditempelkan pada dokumen fisik maupun digital.</p>
                </div>
                <div class="step">
                    <span class="step-number">3</span>
                    <h3>Siapa Saja Bisa Verifikasi</h3>
                    <p>Penerima atau pihak ketiga tinggal memindai QR atau memasukkan kode untuk mengecek keabsahan dokumen.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-band">
        <div class="container">
            <h2>Sudah Menerima Dokumen dengan Kode Unik?</h2>
            <p>Verifikasi keabsahannya sekarang juga &mdash; gratis dan tanpa perlu akun.</p>
            <a href="verify/" class="btn btn-primary">Verifikasi Sekarang</a>
        </div>
    </section>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo e($settings['nama_perusahaan']); ?>. <?php echo e($settings['teks_footer']); ?></p>
        </div>
    </footer>
</body>
</html>
