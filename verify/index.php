<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/functions.php';

$settings = getSettings($pdo);
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

$logoPath = !empty($settings['logo_path']) ? '../' . $settings['logo_path'] : '';
$namaPerusahaan = $settings['nama_perusahaan'] ?: 'Sistem Otentik ID';
$tagline = $settings['tagline'] ?: 'Validasi & Verifikasi Keabsahan Dokumen';
$warnaGelap = isValidHexColor($settings['warna_sidebar'] ?? null) ? $settings['warna_sidebar'] : '#0f172a';
$warnaAksen = isValidHexColor($settings['warna_tombol'] ?? null) ? $settings['warna_tombol'] : '#d4af37';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Dokumen - <?php echo e($namaPerusahaan); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --verify-bg: <?php echo e($warnaGelap); ?>;
            --verify-accent: <?php echo e($warnaAksen); ?>;
        }
    </style>
</head>
<body class="min-h-screen text-slate-900 antialiased" style="background: radial-gradient(circle at 50% 0%, color-mix(in srgb, var(--verify-accent) 20%, transparent), transparent 34%), var(--verify-bg);">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <section class="w-full max-w-2xl" aria-label="Verifikasi keabsahan dokumen">
            <div class="mb-6 text-center">
                <?php if ($logoPath !== '') : ?>
                    <img src="<?php echo e($logoPath); ?>" alt="<?php echo e($namaPerusahaan); ?>" class="mx-auto mb-3 max-h-14 max-w-[220px] object-contain">
                <?php else : ?>
                    <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-900 text-sm font-black tracking-[0.18em] text-yellow-400 shadow-lg">
                        OT
                    </div>
                <?php endif; ?>
                <p class="text-sm font-bold uppercase tracking-[0.22em] text-white">Sistem Otentik ID</p>
                <p class="mt-1 text-sm text-white/70"><?php echo e($tagline); ?></p>
            </div>

            <?php if ($hasilCek === null) : ?>
                <div class="w-full rounded-xl bg-white p-8 shadow-2xl shadow-slate-200/80">
                    <div class="border-b-2 border-gray-100 pb-7 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-700">
                            <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 3 4.5 6.2v5.7c0 4.7 3.2 7.8 7.5 9.1 4.3-1.3 7.5-4.4 7.5-9.1V6.2L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                <path d="M9 12h6M12 9v6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <h1 class="mt-5 text-2xl font-bold uppercase tracking-wide text-slate-900">Verifikasi Dokumen</h1>
                        <p class="mt-2 text-sm text-gray-500">Masukkan kode unik pada dokumen atau pindai QR code untuk memeriksa keabsahan.</p>
                    </div>

                    <form method="get" class="mt-7 space-y-5">
                        <div>
                            <label for="kode" class="mb-2 block text-sm font-semibold text-gray-600">Kode Unik Dokumen</label>
                            <input
                                type="text"
                                id="kode"
                                name="kode"
                                placeholder="BA-2026-XXXXXX"
                                required
                                autofocus
                                autocapitalize="characters"
                                class="w-full rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-900 outline-none transition focus:border-slate-700 focus:ring-4 focus:ring-slate-100"
                            >
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-slate-900 px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-slate-800">
                            Verifikasi
                        </button>
                    </form>

                    <button type="button" id="btn-toggle-scan" class="mt-4 w-full rounded-full border border-gray-300 px-5 py-3 text-sm font-semibold text-gray-600 transition-all hover:bg-gray-100">
                        Scan QR pakai Kamera
                    </button>

                    <div id="scan-area" class="mt-5 hidden rounded-lg bg-slate-50 p-4">
                        <div id="qr-reader"></div>
                        <p class="mt-3 text-center text-sm text-gray-500">Arahkan kamera ke QR code pada dokumen.</p>
                    </div>
                </div>
            <?php elseif ($hasilCek === 'valid') : ?>
                <article class="w-full rounded-xl bg-white shadow-2xl shadow-slate-200/80">
                    <header class="border-b-2 border-gray-100 px-8 py-8 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-50 text-green-500">
                            <svg class="h-11 w-11" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 3 4.5 6.2v5.7c0 4.7 3.2 7.8 7.5 9.1 4.3-1.3 7.5-4.4 7.5-9.1V6.2L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                <path d="m8.8 12.3 2.2 2.2 4.6-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h1 class="mt-5 text-center text-2xl font-bold uppercase tracking-wide text-green-600">Dokumen Valid</h1>
                        <p class="mt-2 text-center text-gray-500">Dokumen ini terdaftar dan sah diterbitkan oleh sistem kami.</p>
                    </header>

                    <div class="px-8 py-2">
                        <dl>
                            <div class="grid grid-cols-1 border-b border-gray-100 py-4 sm:grid-cols-3 sm:gap-6">
                                <dt class="text-sm text-gray-500">Kode Unik</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900 sm:col-span-2 sm:mt-0"><code class="rounded bg-slate-100 px-2 py-1"><?php echo e($dokumen['kode_unik']); ?></code></dd>
                            </div>
                            <div class="grid grid-cols-1 border-b border-gray-100 py-4 sm:grid-cols-3 sm:gap-6">
                                <dt class="text-sm text-gray-500">Nama Dokumen</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900 sm:col-span-2 sm:mt-0"><?php echo e($dokumen['nama_dokumen']); ?></dd>
                            </div>
                            <div class="grid grid-cols-1 border-b border-gray-100 py-4 sm:grid-cols-3 sm:gap-6">
                                <dt class="text-sm text-gray-500">Jenis Dokumen</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900 sm:col-span-2 sm:mt-0"><?php echo e($dokumen['jenis_dokumen']); ?></dd>
                            </div>
                            <div class="grid grid-cols-1 border-b border-gray-100 py-4 sm:grid-cols-3 sm:gap-6">
                                <dt class="text-sm text-gray-500">Brand Penerbit</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900 sm:col-span-2 sm:mt-0"><?php echo e($dokumen['brand_penerbit']); ?></dd>
                            </div>
                            <div class="grid grid-cols-1 border-b border-gray-100 py-4 sm:grid-cols-3 sm:gap-6">
                                <dt class="text-sm text-gray-500">Nama Penerima</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900 sm:col-span-2 sm:mt-0"><?php echo e($dokumen['nama_penerima']); ?></dd>
                            </div>
                            <?php if (!empty($dokumen['nomor_surat'])) : ?>
                                <div class="grid grid-cols-1 border-b border-gray-100 py-4 sm:grid-cols-3 sm:gap-6">
                                    <dt class="text-sm text-gray-500">Nomor Surat</dt>
                                    <dd class="mt-1 text-sm font-medium text-gray-900 sm:col-span-2 sm:mt-0"><?php echo e($dokumen['nomor_surat']); ?></dd>
                                </div>
                            <?php endif; ?>
                            <div class="grid grid-cols-1 border-b border-gray-100 py-4 sm:grid-cols-3 sm:gap-6">
                                <dt class="text-sm text-gray-500">Tanggal Terbit</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900 sm:col-span-2 sm:mt-0"><?php echo formatTanggalIndonesia($dokumen['tanggal_terbit']); ?></dd>
                            </div>
                        </dl>

                        <div class="my-6 rounded-lg bg-slate-50 p-4">
                            <div class="flex items-start gap-3">
                                <span class="mt-1 inline-flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="m8.5 12.5 2.3 2.3 4.7-5.1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M12 3 5 6v5.5c0 4.2 2.9 7.2 7 8.5 4.1-1.3 7-4.3 7-8.5V6l-7-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-sm text-gray-500">Ditandatangani Oleh</p>
                                    <p class="mt-1 text-lg font-semibold text-gray-900"><?php echo e($dokumen['nama_penandatangan']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo e($dokumen['jabatan_penandatangan']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            <?php elseif ($hasilCek === 'revoked') : ?>
                <article class="w-full rounded-xl bg-white shadow-2xl shadow-slate-200/80">
                    <header class="border-b-2 border-gray-100 px-8 py-8 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-50 text-red-500">
                            <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 9v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M12 17h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                                <path d="M10.3 4.4 2.8 17.3A2 2 0 0 0 4.5 20h15a2 2 0 0 0 1.7-2.7L13.7 4.4a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h1 class="mt-5 text-center text-2xl font-bold uppercase tracking-wide text-red-600">Dokumen Dibatalkan</h1>
                        <p class="mt-2 text-center text-gray-500">Dokumen ini telah dibatalkan penerbitnya dan tidak lagi berlaku.</p>
                    </header>
                    <dl class="px-8 py-2">
                        <div class="grid grid-cols-1 border-b border-gray-100 py-4 sm:grid-cols-3 sm:gap-6">
                            <dt class="text-sm text-gray-500">Kode Unik</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 sm:col-span-2 sm:mt-0"><code class="rounded bg-slate-100 px-2 py-1"><?php echo e($dokumen['kode_unik']); ?></code></dd>
                        </div>
                        <div class="grid grid-cols-1 py-4 sm:grid-cols-3 sm:gap-6">
                            <dt class="text-sm text-gray-500">Tanggal Dibatalkan</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 sm:col-span-2 sm:mt-0"><?php echo formatTanggalIndonesia($dokumen['direvoke_pada']); ?></dd>
                        </div>
                    </dl>
                </article>
            <?php else : ?>
                <article class="w-full rounded-xl bg-white shadow-2xl shadow-slate-200/80">
                    <header class="px-8 py-8 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                            <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M9.5 9a2.8 2.8 0 1 1 4.8 2c-1.2 1.1-2.3 1.7-2.3 3.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                                <path d="M12 18h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                                <path d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z" stroke="currentColor" stroke-width="1.8"/>
                            </svg>
                        </div>
                        <h1 class="mt-5 text-center text-2xl font-bold uppercase tracking-wide text-slate-700">Kode Tidak Ditemukan</h1>
                        <p class="mt-2 text-center text-gray-500">Kode tidak ditemukan dalam sistem kami. Pastikan kode yang dimasukkan sudah benar.</p>
                    </header>
                </article>
            <?php endif; ?>

            <?php if ($hasilCek !== null) : ?>
                <div class="mt-6 text-center">
                    <a href="./" class="inline-flex rounded-full border border-white/30 bg-white/10 px-6 py-3 text-sm font-semibold text-white transition-all hover:bg-white/20">
                        Verifikasi Kode Lain
                    </a>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php if ($hasilCek === null) : ?>
        <script>
        document.getElementById('btn-toggle-scan').addEventListener('click', function () {
            var area = document.getElementById('scan-area');
            if (area.classList.contains('hidden')) {
                area.classList.remove('hidden');
                var script = document.createElement('script');
                script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
                script.onload = function () {
                    var scanner = new Html5QrcodeScanner('qr-reader', { fps: 10, qrbox: 250 });
                    scanner.render(function (decodedText) {
                        var kodeHasil = decodedText;
                        try {
                            var parsed = new URL(decodedText);
                            kodeHasil = parsed.searchParams.get('kode') || decodedText;
                        } catch (e) {}
                        window.location.href = './?kode=' + encodeURIComponent(kodeHasil);
                    });
                };
                script.onerror = function () {
                    area.innerHTML = '<p class="text-center text-sm text-gray-500">Pemindaian kamera memerlukan koneksi internet dan tidak tersedia saat ini. Silakan gunakan input kode manual.</p>';
                };
                document.head.appendChild(script);
                this.classList.add('hidden');
            }
        });
        </script>
    <?php endif; ?>
</body>
</html>
