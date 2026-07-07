<?php
declare(strict_types=1);

require __DIR__ . '/auth_check.php';
require __DIR__ . '/../includes/admin_layout.php';

$uploadDir = __DIR__ . '/../uploads/branding';
$ekstensiDiizinkan = [
    'image/png' => 'png',
    'image/jpeg' => 'jpg',
    'image/svg+xml' => 'svg',
];

$settings = getSettings($pdo);
$themePresets = getThemePresets();
$radiusOptions = [
    'rounded-none' => 'Kotak tegas',
    'rounded-md' => 'Sedikit membulat',
    'rounded-xl' => 'Modern rounded',
    'rounded-2xl' => 'Sangat membulat',
];
$shadowOptions = [
    'shadow-none' => 'Tanpa bayangan',
    'shadow-sm' => 'Halus',
    'shadow-lg' => 'Medium',
    'shadow-xl' => 'Tebal premium',
];
$errors = [];
$sukses = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaPerusahaan = trim((string) ($_POST['nama_perusahaan'] ?? ''));
    $tagline = trim((string) ($_POST['tagline'] ?? ''));
    $warnaAksen = trim((string) ($_POST['warna_aksen'] ?? ''));
    $teksFooter = trim((string) ($_POST['teks_footer'] ?? ''));
    $hapusLogo = isset($_POST['hapus_logo']);
    $temaPreset = trim((string) ($_POST['tema_preset'] ?? 'corporate'));
    $warnaSidebar = trim((string) ($_POST['warna_sidebar'] ?? ''));
    $warnaTopbar = trim((string) ($_POST['warna_topbar'] ?? ''));
    $warnaBackground = trim((string) ($_POST['warna_background'] ?? ''));
    $warnaKartuStat = trim((string) ($_POST['warna_kartu_stat'] ?? ''));
    $warnaTeksKartuStat = trim((string) ($_POST['warna_teks_kartu_stat'] ?? ''));
    $warnaTombol = trim((string) ($_POST['warna_tombol'] ?? ''));
    $warnaTombolTeks = trim((string) ($_POST['warna_tombol_teks'] ?? ''));
    $radiusUi = trim((string) ($_POST['radius_ui'] ?? 'rounded-xl'));
    $bayanganUi = trim((string) ($_POST['bayangan_ui'] ?? 'shadow-sm'));

    if ($namaPerusahaan === '') {
        $errors[] = 'Nama perusahaan wajib diisi.';
    }
    if ($teksFooter === '') {
        $errors[] = 'Teks footer wajib diisi.';
    }
    if (!isValidHexColor($warnaAksen)) {
        $errors[] = 'Warna aksen harus format kode HEX, contoh: #1e3a5f.';
    }
    if (!isset($themePresets[$temaPreset]) && $temaPreset !== 'custom') {
        $errors[] = 'Preset tema tidak valid.';
    }

    $warnaTema = [
        'Warna sidebar' => $warnaSidebar,
        'Warna topbar' => $warnaTopbar,
        'Warna background' => $warnaBackground,
        'Warna kartu statistik' => $warnaKartuStat,
        'Warna teks kartu statistik' => $warnaTeksKartuStat,
        'Warna tombol' => $warnaTombol,
        'Warna teks tombol' => $warnaTombolTeks,
    ];

    foreach ($warnaTema as $label => $nilai) {
        if (!isValidHexColor($nilai)) {
            $errors[] = $label . ' harus format kode HEX, contoh: #d4af37.';
        }
    }

    if (!isset($radiusOptions[$radiusUi])) {
        $errors[] = 'Radius UI tidak valid.';
    }

    if (!isset($shadowOptions[$bayanganUi])) {
        $errors[] = 'Bayangan UI tidak valid.';
    }

    $logoPathBaru = $settings['logo_path'];

    if ($hapusLogo) {
        $logoPathBaru = null;
    }

    if (!$errors && isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['logo'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Gagal mengunggah logo. Silakan coba lagi.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Ukuran logo maksimal 2MB.';
        } else {
            $mime = mime_content_type($file['tmp_name']);
            if (!isset($ekstensiDiizinkan[$mime])) {
                $errors[] = 'Format logo harus PNG, JPG, atau SVG.';
            } else {
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $namaFile = 'logo-' . bin2hex(random_bytes(4)) . '.' . $ekstensiDiizinkan[$mime];
                $tujuan = $uploadDir . '/' . $namaFile;

                if (!move_uploaded_file($file['tmp_name'], $tujuan)) {
                    $errors[] = 'Gagal menyimpan file logo di server.';
                } else {
                    if (!empty($settings['logo_path'])) {
                        $fileLama = __DIR__ . '/../' . $settings['logo_path'];
                        if (is_file($fileLama)) {
                            unlink($fileLama);
                        }
                    }
                    $logoPathBaru = 'uploads/branding/' . $namaFile;
                }
            }
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'UPDATE settings SET
                nama_perusahaan = :nama_perusahaan,
                tagline = :tagline,
                warna_aksen = :warna_aksen,
                logo_path = :logo_path,
                teks_footer = :teks_footer,
                tema_preset = :tema_preset,
                warna_sidebar = :warna_sidebar,
                warna_topbar = :warna_topbar,
                warna_background = :warna_background,
                warna_kartu_stat = :warna_kartu_stat,
                warna_teks_kartu_stat = :warna_teks_kartu_stat,
                warna_tombol = :warna_tombol,
                warna_tombol_teks = :warna_tombol_teks,
                radius_ui = :radius_ui,
                bayangan_ui = :bayangan_ui
             WHERE id = 1'
        );
        $stmt->execute([
            'nama_perusahaan' => $namaPerusahaan,
            'tagline' => $tagline,
            'warna_aksen' => $warnaAksen,
            'logo_path' => $logoPathBaru,
            'teks_footer' => $teksFooter,
            'tema_preset' => $temaPreset,
            'warna_sidebar' => $warnaSidebar,
            'warna_topbar' => $warnaTopbar,
            'warna_background' => $warnaBackground,
            'warna_kartu_stat' => $warnaKartuStat,
            'warna_teks_kartu_stat' => $warnaTeksKartuStat,
            'warna_tombol' => $warnaTombol,
            'warna_tombol_teks' => $warnaTombolTeks,
            'radius_ui' => $radiusUi,
            'bayangan_ui' => $bayanganUi,
        ]);

        $settings = getSettings($pdo);
        $sukses = true;
    } else {
        $settings['nama_perusahaan'] = $namaPerusahaan;
        $settings['tagline'] = $tagline;
        $settings['warna_aksen'] = $warnaAksen;
        $settings['teks_footer'] = $teksFooter;
        $settings['tema_preset'] = $temaPreset;
        $settings['warna_sidebar'] = $warnaSidebar;
        $settings['warna_topbar'] = $warnaTopbar;
        $settings['warna_background'] = $warnaBackground;
        $settings['warna_kartu_stat'] = $warnaKartuStat;
        $settings['warna_teks_kartu_stat'] = $warnaTeksKartuStat;
        $settings['warna_tombol'] = $warnaTombol;
        $settings['warna_tombol_teks'] = $warnaTombolTeks;
        $settings['radius_ui'] = $radiusUi;
        $settings['bayangan_ui'] = $bayanganUi;
    }
}

$basePath = '../';
renderAdminLayoutStart($pdo, 'Pengaturan Branding', 'settings', '<a href="dashboard.php" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke Dashboard</a>');
?>

<div class="theme-surface border border-slate-200 bg-white p-6">
    <?php if ($sukses) : ?>
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">Pengaturan branding berhasil disimpan.</div>
    <?php endif; ?>

    <?php if ($errors) : ?>
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $err) : ?>
                    <li><?php echo e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 gap-5 lg:grid-cols-2" novalidate>
        <div>
            <label for="nama_perusahaan" class="mb-2 block text-sm font-semibold text-slate-700">Nama Perusahaan</label>
            <input class="theme-focus w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition" type="text" id="nama_perusahaan" name="nama_perusahaan" value="<?php echo e($settings['nama_perusahaan']); ?>" required>
        </div>
        <div>
            <label for="tagline" class="mb-2 block text-sm font-semibold text-slate-700">Tagline</label>
            <input class="theme-focus w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition" type="text" id="tagline" name="tagline" value="<?php echo e($settings['tagline']); ?>">
        </div>
        <div>
            <label for="warna_aksen" class="mb-2 block text-sm font-semibold text-slate-700">Warna Aksen (kode HEX)</label>
            <div class="flex gap-3">
                <input type="color" id="warna_aksen_picker" value="<?php echo e($settings['warna_aksen']); ?>" class="h-12 w-16 rounded-lg border border-slate-200 bg-white p-1">
                <input class="theme-focus w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition" type="text" id="warna_aksen" name="warna_aksen" value="<?php echo e($settings['warna_aksen']); ?>" placeholder="#1e3a5f" pattern="^#[0-9a-fA-F]{6}$" required>
            </div>
        </div>
        <div>
            <label for="teks_footer" class="mb-2 block text-sm font-semibold text-slate-700">Teks Footer</label>
            <input class="theme-focus w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition" type="text" id="teks_footer" name="teks_footer" value="<?php echo e($settings['teks_footer']); ?>" required>
        </div>
        <div class="lg:col-span-2">
            <div class="mb-4 border-t border-slate-200 pt-6">
                <h3 class="text-lg font-bold text-slate-900">Tema Website</h3>
                <p class="mt-1 text-sm text-slate-500">Atur tampilan dashboard dan halaman admin sesuai karakter brand Anda.</p>
            </div>
        </div>
        <div>
            <label for="tema_preset" class="mb-2 block text-sm font-semibold text-slate-700">Preset Tema</label>
            <select class="theme-focus w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition" id="tema_preset" name="tema_preset">
                <?php foreach ($themePresets as $key => $preset) : ?>
                    <option value="<?php echo e($key); ?>" <?php echo $settings['tema_preset'] === $key ? 'selected' : ''; ?>><?php echo e($preset['label']); ?></option>
                <?php endforeach; ?>
                <option value="custom" <?php echo $settings['tema_preset'] === 'custom' ? 'selected' : ''; ?>>Custom Manual</option>
            </select>
        </div>
        <div>
            <label for="warna_sidebar" class="mb-2 block text-sm font-semibold text-slate-700">Warna Sidebar</label>
            <div class="flex gap-3">
                <input type="color" data-color-picker="warna_sidebar" value="<?php echo e($settings['warna_sidebar']); ?>" class="h-12 w-16 rounded-lg border border-slate-200 bg-white p-1">
                <input class="theme-focus w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition" type="text" id="warna_sidebar" name="warna_sidebar" value="<?php echo e($settings['warna_sidebar']); ?>" required>
            </div>
        </div>
        <div>
            <label for="warna_topbar" class="mb-2 block text-sm font-semibold text-slate-700">Warna Topbar</label>
            <div class="flex gap-3">
                <input type="color" data-color-picker="warna_topbar" value="<?php echo e($settings['warna_topbar']); ?>" class="h-12 w-16 rounded-lg border border-slate-200 bg-white p-1">
                <input class="theme-focus w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition" type="text" id="warna_topbar" name="warna_topbar" value="<?php echo e($settings['warna_topbar']); ?>" required>
            </div>
        </div>
        <div>
            <label for="warna_background" class="mb-2 block text-sm font-semibold text-slate-700">Warna Background</label>
            <div class="flex gap-3">
                <input type="color" data-color-picker="warna_background" value="<?php echo e($settings['warna_background']); ?>" class="h-12 w-16 rounded-lg border border-slate-200 bg-white p-1">
                <input class="theme-focus w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition" type="text" id="warna_background" name="warna_background" value="<?php echo e($settings['warna_background']); ?>" required>
            </div>
        </div>
        <div>
            <label for="warna_kartu_stat" class="mb-2 block text-sm font-semibold text-slate-700">Warna Kartu Statistik</label>
            <div class="flex gap-3">
                <input type="color" data-color-picker="warna_kartu_stat" value="<?php echo e($settings['warna_kartu_stat']); ?>" class="h-12 w-16 rounded-lg border border-slate-200 bg-white p-1">
                <input class="theme-focus w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition" type="text" id="warna_kartu_stat" name="warna_kartu_stat" value="<?php echo e($settings['warna_kartu_stat']); ?>" required>
            </div>
        </div>
        <div>
            <label for="warna_teks_kartu_stat" class="mb-2 block text-sm font-semibold text-slate-700">Warna Teks Kartu Statistik</label>
            <div class="flex gap-3">
                <input type="color" data-color-picker="warna_teks_kartu_stat" value="<?php echo e($settings['warna_teks_kartu_stat']); ?>" class="h-12 w-16 rounded-lg border border-slate-200 bg-white p-1">
                <input class="theme-focus w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition" type="text" id="warna_teks_kartu_stat" name="warna_teks_kartu_stat" value="<?php echo e($settings['warna_teks_kartu_stat']); ?>" required>
            </div>
        </div>
        <div>
            <label for="warna_tombol" class="mb-2 block text-sm font-semibold text-slate-700">Warna Tombol Utama</label>
            <div class="flex gap-3">
                <input type="color" data-color-picker="warna_tombol" value="<?php echo e($settings['warna_tombol']); ?>" class="h-12 w-16 rounded-lg border border-slate-200 bg-white p-1">
                <input class="theme-focus w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition" type="text" id="warna_tombol" name="warna_tombol" value="<?php echo e($settings['warna_tombol']); ?>" required>
            </div>
        </div>
        <div>
            <label for="warna_tombol_teks" class="mb-2 block text-sm font-semibold text-slate-700">Warna Teks Tombol</label>
            <div class="flex gap-3">
                <input type="color" data-color-picker="warna_tombol_teks" value="<?php echo e($settings['warna_tombol_teks']); ?>" class="h-12 w-16 rounded-lg border border-slate-200 bg-white p-1">
                <input class="theme-focus w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition" type="text" id="warna_tombol_teks" name="warna_tombol_teks" value="<?php echo e($settings['warna_tombol_teks']); ?>" required>
            </div>
        </div>
        <div>
            <label for="radius_ui" class="mb-2 block text-sm font-semibold text-slate-700">Radius Komponen</label>
            <select class="theme-focus w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition" id="radius_ui" name="radius_ui">
                <?php foreach ($radiusOptions as $key => $label) : ?>
                    <option value="<?php echo e($key); ?>" <?php echo $settings['radius_ui'] === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="bayangan_ui" class="mb-2 block text-sm font-semibold text-slate-700">Bayangan Komponen</label>
            <select class="theme-focus w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none transition" id="bayangan_ui" name="bayangan_ui">
                <?php foreach ($shadowOptions as $key => $label) : ?>
                    <option value="<?php echo e($key); ?>" <?php echo $settings['bayangan_ui'] === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="lg:col-span-2">
            <div class="grid grid-cols-1 gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-3">
                <div class="rounded-xl p-4 text-white" id="preview-sidebar" style="background: <?php echo e($settings['warna_sidebar']); ?>;">
                    <p class="text-sm font-bold" id="preview-logo" style="color: <?php echo e($settings['warna_tombol']); ?>;">EPI Sistem Otentik ID</p>
                    <p class="mt-3 rounded-lg px-3 py-2 text-sm" style="background: rgba(255,255,255,.12);">Dashboard</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-4" id="preview-bg" style="background: <?php echo e($settings['warna_background']); ?>;">
                    <div class="mb-3 rounded-lg border border-slate-200 p-3" id="preview-topbar" style="background: <?php echo e($settings['warna_topbar']); ?>;">Topbar</div>
                    <button type="button" class="rounded-lg px-4 py-2 text-sm font-bold" id="preview-button" style="background: <?php echo e($settings['warna_tombol']); ?>; color: <?php echo e($settings['warna_tombol_teks']); ?>;">Tombol</button>
                </div>
                <div class="rounded-xl p-4" id="preview-card" style="background: <?php echo e($settings['warna_kartu_stat']); ?>; color: <?php echo e($settings['warna_teks_kartu_stat']); ?>;">
                    <p class="text-xs font-bold uppercase tracking-wide">Total Dokumen</p>
                    <p class="mt-3 text-3xl font-black">128</p>
                </div>
            </div>
        </div>
        <div class="lg:col-span-2">
            <label class="mb-2 block text-sm font-semibold text-slate-700">Logo Perusahaan</label>
            <?php if (!empty($settings['logo_path'])) : ?>
                <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <img src="<?php echo e($basePath . $settings['logo_path']); ?>" alt="Logo saat ini" class="mb-3 max-h-20 max-w-xs object-contain">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="hapus_logo" value="1" class="rounded border-slate-300 text-[#d4af37] focus:ring-[#d4af37]"> Hapus logo saat ini
                    </label>
                </div>
            <?php else : ?>
                <p class="mb-3 text-sm text-slate-500">Belum ada logo. Tanpa logo, sistem menampilkan singkatan nama perusahaan.</p>
            <?php endif; ?>
            <input class="block w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800" type="file" name="logo" accept=".png,.jpg,.jpeg,.svg">
            <p class="mt-2 text-sm text-slate-500">Format PNG, JPG, atau SVG. Maksimal 2MB. Logo ini juga dipakai di tengah QR code.</p>
        </div>
        <div class="flex flex-wrap gap-3 lg:col-span-2">
            <button type="submit" class="theme-button rounded-lg px-6 py-3 text-sm font-bold shadow-sm transition">Simpan Pengaturan</button>
            <a href="dashboard.php" class="rounded-lg border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke Dashboard</a>
        </div>
    </form>
</div>

<script>
const themePresets = <?php echo json_encode($themePresets, JSON_UNESCAPED_SLASHES); ?>;

function setInputValue(id, value) {
    const input = document.getElementById(id);
    const picker = document.querySelector('[data-color-picker="' + id + '"]');
    if (input) {
        input.value = value;
    }
    if (picker) {
        picker.value = value;
    }
}

function updatePreview() {
    const sidebar = document.getElementById('warna_sidebar')?.value || '#111827';
    const topbar = document.getElementById('warna_topbar')?.value || '#ffffff';
    const bg = document.getElementById('warna_background')?.value || '#f1f5f9';
    const card = document.getElementById('warna_kartu_stat')?.value || '#1e3a8a';
    const cardText = document.getElementById('warna_teks_kartu_stat')?.value || '#d4af37';
    const button = document.getElementById('warna_tombol')?.value || '#d4af37';
    const buttonText = document.getElementById('warna_tombol_teks')?.value || '#0f172a';

    document.getElementById('preview-sidebar').style.background = sidebar;
    document.getElementById('preview-logo').style.color = button;
    document.getElementById('preview-topbar').style.background = topbar;
    document.getElementById('preview-bg').style.background = bg;
    document.getElementById('preview-card').style.background = card;
    document.getElementById('preview-card').style.color = cardText;
    document.getElementById('preview-button').style.background = button;
    document.getElementById('preview-button').style.color = buttonText;
}

document.getElementById('warna_aksen_picker').addEventListener('input', function () {
    document.getElementById('warna_aksen').value = this.value;
});

document.querySelectorAll('[data-color-picker]').forEach(function (picker) {
    picker.addEventListener('input', function () {
        setInputValue(this.dataset.colorPicker, this.value);
        document.getElementById('tema_preset').value = 'custom';
        updatePreview();
    });
});

[
    'warna_sidebar',
    'warna_topbar',
    'warna_background',
    'warna_kartu_stat',
    'warna_teks_kartu_stat',
    'warna_tombol',
    'warna_tombol_teks'
].forEach(function (id) {
    document.getElementById(id)?.addEventListener('input', function () {
        const picker = document.querySelector('[data-color-picker="' + id + '"]');
        if (picker && /^#[0-9a-fA-F]{6}$/.test(this.value)) {
            picker.value = this.value;
        }
        document.getElementById('tema_preset').value = 'custom';
        updatePreview();
    });
});

document.getElementById('tema_preset').addEventListener('change', function () {
    const preset = themePresets[this.value];
    if (!preset) {
        return;
    }

    Object.keys(preset).forEach(function (key) {
        if (key !== 'label') {
            setInputValue(key, preset[key]);
        }
    });
    updatePreview();
});
</script>

<?php renderAdminLayoutEnd(); ?>
