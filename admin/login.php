<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/functions.php';

$settings = getSettings($pdo);
$logoPath = !empty($settings['logo_path']) ? '../' . $settings['logo_path'] : '';
$namaPerusahaan = $settings['nama_perusahaan'] ?: 'EPI Sistem Otentik ID';
$tagline = $settings['tagline'] ?: 'Validasi & Verifikasi Keabsahan Dokumen';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$pesanError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $pesanError = 'Username/email dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, username, email, password_hash, nama_lengkap, status, role
             FROM admins
             WHERE username = :identifier_username OR email = :identifier_email
             LIMIT 1'
        );
        $stmt->execute([
            'identifier_username' => $username,
            'identifier_email' => $username,
        ]);
        $admin = $stmt->fetch();

        if ($admin && $admin['status'] === 'approved' && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_nama'] = $admin['nama_lengkap'];
            $_SESSION['admin_role'] = $admin['role'];

            header('Location: ' . dashboardPathForRole($admin['role']));
            exit;
        }

        $pesanError = 'Username atau password salah, atau akun belum aktif.';
    }
}

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin - <?php echo e($namaPerusahaan); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        epi: {
                            navy: '#07111f',
                            gold: '#d9a441',
                            amber: '#f4bd50'
                        }
                    },
                    boxShadow: {
                        gold: '0 18px 35px rgba(217, 164, 65, 0.28)'
                    }
                }
            }
        };
    </script>
    <style>
        :root {
            --theme-sidebar: <?php echo e(isValidHexColor($settings['warna_sidebar']) ? $settings['warna_sidebar'] : '#07111f'); ?>;
            --theme-button: <?php echo e(isValidHexColor($settings['warna_tombol']) ? $settings['warna_tombol'] : '#d9a441'); ?>;
            --theme-button-text: <?php echo e(isValidHexColor($settings['warna_tombol_teks']) ? $settings['warna_tombol_teks'] : '#1f1605'); ?>;
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
    </style>
</head>
<body class="min-h-screen bg-white text-slate-900 antialiased">
    <main class="grid min-h-screen w-full grid-cols-1 lg:grid-cols-[55%_45%]">
        <section class="relative hidden min-h-screen items-center justify-center overflow-hidden px-12 text-white lg:flex" style="background: var(--theme-sidebar);" aria-label="Branding <?php echo e($namaPerusahaan); ?>">
            <div class="absolute inset-0 opacity-90" style="background: radial-gradient(circle at 25% 20%, color-mix(in srgb, var(--theme-button) 22%, transparent), transparent 30%), linear-gradient(135deg, var(--theme-sidebar) 0%, #101827 55%, #2c1c08 100%);"></div>
            <div class="absolute -left-24 top-20 h-72 w-72 rounded-full bg-amber-400/10 blur-3xl"></div>
            <div class="absolute bottom-16 right-16 h-96 w-96 rounded-full bg-yellow-200/10 blur-3xl"></div>

            <div class="relative z-10 max-w-xl text-center">
                <?php if ($logoPath !== '') : ?>
                    <img src="<?php echo e($logoPath); ?>" alt="<?php echo e($namaPerusahaan); ?>" class="mx-auto mb-8 max-h-28 max-w-xs object-contain drop-shadow-2xl">
                <?php else : ?>
                    <div class="mx-auto mb-8 flex h-20 w-20 items-center justify-center rounded-2xl border border-white/20 bg-white/10 shadow-2xl backdrop-blur">
                        <span class="text-2xl font-black tracking-[0.18em]" style="color: var(--theme-button);">EPI</span>
                    </div>
                <?php endif; ?>
                <h1 class="text-4xl font-black leading-tight tracking-tight text-white xl:text-5xl">
                    <?php echo e($namaPerusahaan); ?>
                </h1>
                <p class="mx-auto mt-6 max-w-md text-lg leading-8 text-slate-200">
                    <?php echo e($tagline); ?>
                </p>
            </div>
        </section>

        <section class="flex min-h-screen flex-col bg-white px-6 py-8 sm:px-10 lg:px-14" aria-label="Form login admin">
            <div class="flex flex-1 items-center justify-center">
                <div class="w-full max-w-md">
                    <div class="mb-10 lg:hidden">
                        <?php if ($logoPath !== '') : ?>
                            <img src="<?php echo e($logoPath); ?>" alt="<?php echo e($namaPerusahaan); ?>" class="mb-4 max-h-14 max-w-[180px] object-contain">
                        <?php else : ?>
                            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-[#07111f] shadow-lg">
                                <span class="text-base font-black tracking-[0.16em]" style="color: var(--theme-button);">EPI</span>
                            </div>
                        <?php endif; ?>
                        <p class="text-base font-bold text-slate-900"><?php echo e($namaPerusahaan); ?></p>
                        <p class="mt-1 text-sm text-slate-500"><?php echo e($tagline); ?></p>
                    </div>

                    <div class="mb-8">
                        <p class="mb-3 text-sm font-semibold uppercase tracking-[0.22em]" style="color: var(--theme-button);">Admin Portal</p>
                        <h2 class="text-3xl font-bold tracking-tight text-slate-900">Login Admin</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-500">
                            Masuk untuk mengelola penerbitan dan validasi dokumen resmi.
                        </p>
                    </div>

                    <?php if ($pesanError !== '') : ?>
                        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert">
                            <?php echo e($pesanError); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="space-y-5" novalidate>
                        <div>
                            <label for="username" class="mb-2 block text-sm font-semibold text-slate-700">Username atau Email</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M20 21a8 8 0 0 0-16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        <path d="M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z" stroke="currentColor" stroke-width="1.8"/>
                                    </svg>
                                </span>
                                <input
                                    type="text"
                                    id="username"
                                    name="username"
                                    autocomplete="username"
                                    required
                                    autofocus
                                    class="theme-focus block w-full rounded-md border border-slate-200 bg-white py-3 pl-12 pr-4 text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400"
                                    placeholder="Masukkan username atau email"
                                >
                            </div>
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M7 10V8a5 5 0 0 1 10 0v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        <path d="M6.5 10h11A1.5 1.5 0 0 1 19 11.5v7A1.5 1.5 0 0 1 17.5 20h-11A1.5 1.5 0 0 1 5 18.5v-7A1.5 1.5 0 0 1 6.5 10Z" stroke="currentColor" stroke-width="1.8"/>
                                    </svg>
                                </span>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    autocomplete="current-password"
                                    required
                                    class="theme-focus block w-full rounded-md border border-slate-200 bg-white py-3 pl-12 pr-12 text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400"
                                    placeholder="Masukkan password"
                                >
                                <button
                                    type="button"
                                    id="toggle-password"
                                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 transition hover:opacity-80 focus:outline-none focus:ring-2"
                                    aria-label="Tampilkan password"
                                >
                                    <svg id="eye-icon" class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                        <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.8"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="theme-button w-full rounded-md px-5 py-3.5 text-sm font-bold uppercase tracking-[0.18em] shadow-gold transition focus:outline-none active:translate-y-px"
                        >
                            Masuk
                        </button>
                    </form>
                </div>
            </div>

            <footer class="pt-8 text-center text-sm text-slate-400">
                &copy; 2026 <?php echo e($namaPerusahaan); ?>. <?php echo e($settings['teks_footer']); ?>
            </footer>
        </section>
    </main>

    <script>
        const togglePassword = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');

        togglePassword?.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            togglePassword.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Tampilkan password');
        });
    </script>
</body>
</html>
