<?php
$pageTitle = isset($pageTitle) ? $pageTitle : 'Otentik ID';
$bodyClass = isset($bodyClass) ? $bodyClass : '';
$basePath = isset($basePath) ? $basePath : '';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($pageTitle); ?> - Otentik ID</title>
    <link rel="stylesheet" href="<?php echo e($basePath); ?>assets/css/style.css">
</head>
<body class="<?php echo e($bodyClass); ?>">
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="<?php echo e($basePath); ?>verify/">
                <span class="brand-mark">ID</span>
                <span>
                    <strong>Otentik ID</strong>
                    <small>Validasi Keabsahan Dokumen</small>
                </span>
            </a>

            <?php if (isLoggedIn()) : ?>
                <nav class="main-nav" aria-label="Navigasi admin">
                    <a href="<?php echo e($basePath); ?>admin/dashboard.php">Dashboard</a>
                    <a href="<?php echo e($basePath); ?>admin/issue.php">Terbitkan</a>
                    <a href="<?php echo e($basePath); ?>admin/logout.php">Keluar</a>
                </nav>
            <?php endif; ?>
        </div>
    </header>

    <main class="container page-content">
