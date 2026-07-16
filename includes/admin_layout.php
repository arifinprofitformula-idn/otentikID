<?php
declare(strict_types=1);

function renderAdminLayoutStart(PDO $pdo, string $pageTitle, string $activeMenu = '', string $actionsHtml = ''): void
{
    $settings = getSettings($pdo);
    $hexKeys = [
        'warna_sidebar',
        'warna_topbar',
        'warna_background',
        'warna_kartu_stat',
        'warna_teks_kartu_stat',
        'warna_tombol',
        'warna_tombol_teks',
    ];
    $defaultTheme = getThemePresets()['corporate'];

    foreach ($hexKeys as $key) {
        if (!isValidHexColor($settings[$key] ?? null)) {
            $settings[$key] = $defaultTheme[$key] ?? '#111827';
        }
    }

    $radiusOptions = ['rounded-none', 'rounded-md', 'rounded-xl', 'rounded-2xl'];
    $shadowOptions = ['shadow-none', 'shadow-sm', 'shadow-lg', 'shadow-xl'];
    $radiusUi = in_array($settings['radius_ui'], $radiusOptions, true) ? $settings['radius_ui'] : 'rounded-xl';
    $bayanganUi = in_array($settings['bayangan_ui'], $shadowOptions, true) ? $settings['bayangan_ui'] : 'shadow-sm';
    $warnaSidebarAktif = warnaLebihGelap($settings['warna_sidebar'], 0.22);
    $radiusCss = [
        'rounded-none' => '0',
        'rounded-md' => '0.375rem',
        'rounded-xl' => '0.75rem',
        'rounded-2xl' => '1rem',
    ][$radiusUi];
    $shadowCss = [
        'shadow-none' => 'none',
        'shadow-sm' => '0 1px 2px rgba(15, 23, 42, 0.08)',
        'shadow-lg' => '0 10px 15px -3px rgba(15, 23, 42, 0.12), 0 4px 6px -4px rgba(15, 23, 42, 0.12)',
        'shadow-xl' => '0 20px 25px -5px rgba(15, 23, 42, 0.16), 0 8px 10px -6px rgba(15, 23, 42, 0.16)',
    ][$bayanganUi];
    ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($pageTitle); ?> - Sistem Otentik ID</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        epi: {
                            gold: '#d4af37',
                            charcoal: '#111827',
                            navy: '#0b1f4d'
                        }
                    },
                    boxShadow: {
                        solid: '0 18px 0 rgba(15, 23, 42, 0.16), 0 24px 40px rgba(15, 23, 42, 0.18)'
                    }
                }
            }
        };
    </script>
    <style>
        :root {
            --theme-sidebar: <?php echo e($settings['warna_sidebar']); ?>;
            --theme-sidebar-active: <?php echo e($warnaSidebarAktif); ?>;
            --theme-topbar: <?php echo e($settings['warna_topbar']); ?>;
            --theme-bg: <?php echo e($settings['warna_background']); ?>;
            --theme-stat-card: <?php echo e($settings['warna_kartu_stat']); ?>;
            --theme-stat-text: <?php echo e($settings['warna_teks_kartu_stat']); ?>;
            --theme-button: <?php echo e($settings['warna_tombol']); ?>;
            --theme-button-text: <?php echo e($settings['warna_tombol_teks']); ?>;
            --theme-radius: <?php echo e($radiusCss); ?>;
            --theme-shadow: <?php echo e($shadowCss); ?>;
        }

        .theme-button {
            background: var(--theme-button);
            color: var(--theme-button-text);
        }

        .theme-button:hover {
            filter: brightness(0.95);
        }

        .theme-focus:focus {
            border-color: var(--theme-button);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--theme-button) 18%, transparent);
        }

        .theme-stat-card {
            background: var(--theme-stat-card);
            color: var(--theme-stat-text);
        }

        .theme-surface {
            border-radius: var(--theme-radius);
            box-shadow: var(--theme-shadow);
        }
    </style>
</head>
<body class="min-h-screen text-slate-900 antialiased" style="background: var(--theme-bg);">
    <div class="flex min-h-screen">
        <aside class="hidden w-[250px] flex-shrink-0 flex-col text-slate-200 md:flex" style="background: var(--theme-sidebar);">
            <div class="border-b border-white/10 px-6 py-7">
                <?php if (!empty($settings['logo_path'])) : ?>
                    <img
                        src="<?php echo e('../' . $settings['logo_path']); ?>"
                        alt="<?php echo e($settings['nama_perusahaan']); ?>"
                        class="mb-4 max-h-16 w-auto max-w-[180px] object-contain"
                    >
                <?php else : ?>
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-xl border border-white/15 bg-white/10">
                        <span class="text-lg font-black tracking-[0.18em]" style="color: var(--theme-button);">OT</span>
                    </div>
                <?php endif; ?>
                <h1 class="text-xl font-bold leading-tight text-white"><?php echo e($settings['nama_perusahaan']); ?></h1>
                <p class="mt-1 text-sm leading-snug text-slate-400"><?php echo e($settings['tagline']); ?></p>
            </div>

            <nav class="flex-1 space-y-2 px-4 py-6" aria-label="Navigasi admin">
                <?php
                $menus = [
                    'dashboard' => ['Dashboard', 'dashboard.php'],
                    'issue' => ['Terbitkan Dokumen', 'issue.php'],
                    'password' => ['Ubah Password', 'change_password.php'],
                ];

                if (currentUserRole() === 'superadmin') {
                    $menus = [
                        'dashboard' => ['Dashboard', 'dashboard.php'],
                        'issue' => ['Terbitkan Dokumen', 'issue.php'],
                        'brands' => ['Brand Penerbit', 'brands.php'],
                        'registrations' => ['Registrasi', 'registrations.php'],
                        'settings' => ['Pengaturan', 'settings.php'],
                        'password' => ['Ubah Password', 'change_password.php'],
                    ];
                }
                foreach ($menus as $key => [$label, $href]) :
                    $isActive = $activeMenu === $key;
                    ?>
                    <a href="<?php echo e($href); ?>" <?php echo $isActive ? 'style="background: var(--theme-sidebar-active);"' : ''; ?> class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm <?php echo $isActive ? 'font-semibold text-white shadow-sm' : 'font-medium text-slate-300 transition hover:bg-white/10 hover:text-white'; ?>">
                        <span class="h-2 w-2 rounded-full <?php echo $isActive ? '' : 'bg-slate-500'; ?>" <?php echo $isActive ? 'style="background: var(--theme-button);"' : ''; ?>></span>
                        <?php echo e($label); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="border-t border-white/10 p-4">
                <a href="logout.php" class="flex items-center justify-center rounded-lg border border-white/10 px-4 py-3 text-sm font-semibold text-slate-300 transition hover:bg-white/10 hover:text-white">
                    Keluar
                </a>
            </div>
        </aside>

        <main class="flex min-w-0 flex-1 flex-col" style="background: var(--theme-bg);">
            <header class="sticky top-0 z-20 border-b border-slate-200" style="background: var(--theme-topbar);">
                <div class="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-center sm:justify-between lg:px-8">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] md:hidden" style="color: var(--theme-button);">Sistem Otentik ID</p>
                        <h2 class="text-2xl font-bold tracking-tight text-slate-900"><?php echo e($pageTitle); ?></h2>
                    </div>
                    <?php if ($actionsHtml !== '') : ?>
                        <div class="flex items-center gap-3">
                            <?php echo $actionsHtml; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </header>

            <section class="space-y-6 px-5 py-6 lg:px-8">
    <?php
}

function renderAdminLayoutEnd(): void
{
    ?>
            </section>
        </main>
    </div>
</body>
</html>
    <?php
}
