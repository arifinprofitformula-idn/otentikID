<?php
declare(strict_types=1);

function renderUserLayoutStart(PDO $pdo, string $pageTitle, string $activeMenu = ''): void
{
    $settings = getSettings($pdo);
    $warnaGelap = isValidHexColor($settings['warna_sidebar'] ?? null) ? $settings['warna_sidebar'] : '#0f172a';
    $warnaTombol = isValidHexColor($settings['warna_tombol'] ?? null) ? $settings['warna_tombol'] : '#334155';
    $warnaTombolTeks = isValidHexColor($settings['warna_tombol_teks'] ?? null) ? $settings['warna_tombol_teks'] : '#ffffff';
    ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($pageTitle); ?> - <?php echo e($settings['nama_perusahaan']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --user-dark: <?php echo e($warnaGelap); ?>;
            --user-button: <?php echo e($warnaTombol); ?>;
            --user-button-text: <?php echo e($warnaTombolTeks); ?>;
        }
        .user-button { background: var(--user-button); color: var(--user-button-text); }
        .user-button:hover { filter: brightness(.95); }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <header class="border-b border-white/10 text-white" style="background: var(--user-dark);">
        <div class="mx-auto flex max-w-6xl flex-col gap-4 px-5 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <?php if (!empty($settings['logo_path'])) : ?>
                    <img src="<?php echo e('../' . $settings['logo_path']); ?>" alt="<?php echo e($settings['nama_perusahaan']); ?>" class="mb-3 max-h-12 max-w-[180px] object-contain">
                <?php endif; ?>
                <h1 class="text-xl font-black"><?php echo e($settings['nama_perusahaan']); ?></h1>
                <p class="text-sm text-white/70"><?php echo e($settings['tagline']); ?></p>
            </div>
            <nav class="flex flex-wrap gap-2 text-sm font-semibold" aria-label="Navigasi user">
                <a href="dashboard.php" class="rounded-full px-4 py-2 <?php echo $activeMenu === 'dashboard' ? 'bg-white text-slate-900' : 'bg-white/10 text-white hover:bg-white/20'; ?>">Dokumen Saya</a>
                <a href="change_password.php" class="rounded-full px-4 py-2 <?php echo $activeMenu === 'password' ? 'bg-white text-slate-900' : 'bg-white/10 text-white hover:bg-white/20'; ?>">Ubah Password</a>
                <a href="../admin/logout.php" class="rounded-full bg-white/10 px-4 py-2 text-white hover:bg-white/20">Keluar</a>
            </nav>
        </div>
    </header>
    <main class="mx-auto max-w-6xl space-y-6 px-5 py-8">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Area Pengguna</p>
            <h2 class="mt-1 text-3xl font-black text-slate-900"><?php echo e($pageTitle); ?></h2>
        </div>
    <?php
}

function renderUserLayoutEnd(): void
{
    ?>
    </main>
</body>
</html>
    <?php
}
