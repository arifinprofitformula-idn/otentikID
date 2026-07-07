<?php
$pageTitle = isset($pageTitle) ? $pageTitle : 'Beranda';
$bodyClass = isset($bodyClass) ? $bodyClass : '';
$basePath = isset($basePath) ? $basePath : '';
$settings = getSettings($pdo);
$warnaAksenGelap = warnaLebihGelap($settings['warna_aksen']);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($pageTitle); ?> - <?php echo e($settings['nama_perusahaan']); ?></title>
    <link rel="stylesheet" href="<?php echo e($basePath); ?>assets/css/style.css">
    <style>
        :root {
            --color-primary: <?php echo e($settings['warna_aksen']); ?>;
            --color-primary-dark: <?php echo e($warnaAksenGelap); ?>;
            --color-bg: <?php echo e(isValidHexColor($settings['warna_background']) ? $settings['warna_background'] : '#f4f6f8'); ?>;
        }

        .site-header {
            background: <?php echo e(isValidHexColor($settings['warna_sidebar']) ? $settings['warna_sidebar'] : $settings['warna_aksen']); ?>;
        }

        .brand-mark {
            color: <?php echo e(isValidHexColor($settings['warna_tombol']) ? $settings['warna_tombol'] : $settings['warna_aksen']); ?>;
        }
    </style>
</head>
<body class="<?php echo e($bodyClass); ?>">
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="<?php echo e($basePath); ?>verify/">
                <?php if (!empty($settings['logo_path'])) : ?>
                    <img class="brand-logo" src="<?php echo e($basePath . $settings['logo_path']); ?>" alt="<?php echo e($settings['nama_perusahaan']); ?>">
                <?php else : ?>
                    <span class="brand-mark"><?php echo e(mb_strtoupper(mb_substr($settings['nama_perusahaan'], 0, 2))); ?></span>
                <?php endif; ?>
                <span>
                    <strong><?php echo e($settings['nama_perusahaan']); ?></strong>
                    <small><?php echo e($settings['tagline']); ?></small>
                </span>
            </a>

            <?php if (isLoggedIn()) : ?>
                <nav class="main-nav" aria-label="Navigasi admin">
                    <a href="<?php echo e($basePath); ?>admin/dashboard.php">Dashboard</a>
                    <a href="<?php echo e($basePath); ?>admin/issue.php">Terbitkan</a>
                    <a href="<?php echo e($basePath); ?>admin/settings.php">Pengaturan</a>
                    <a href="<?php echo e($basePath); ?>admin/logout.php">Keluar</a>
                </nav>
            <?php endif; ?>
        </div>
    </header>

    <main class="container page-content">
