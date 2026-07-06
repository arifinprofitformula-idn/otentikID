<?php
declare(strict_types=1);

require __DIR__ . '/auth_check.php';

$uploadDir = __DIR__ . '/../uploads/branding';
$ekstensiDiizinkan = [
    'image/png' => 'png',
    'image/jpeg' => 'jpg',
    'image/svg+xml' => 'svg',
];

$settings = getSettings($pdo);
$errors = [];
$sukses = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaPerusahaan = trim((string) ($_POST['nama_perusahaan'] ?? ''));
    $tagline = trim((string) ($_POST['tagline'] ?? ''));
    $warnaAksen = trim((string) ($_POST['warna_aksen'] ?? ''));
    $teksFooter = trim((string) ($_POST['teks_footer'] ?? ''));
    $hapusLogo = isset($_POST['hapus_logo']);

    if ($namaPerusahaan === '') {
        $errors[] = 'Nama perusahaan wajib diisi.';
    }
    if ($teksFooter === '') {
        $errors[] = 'Teks footer wajib diisi.';
    }
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $warnaAksen)) {
        $errors[] = 'Warna aksen harus format kode HEX, contoh: #1e3a5f.';
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
                teks_footer = :teks_footer
             WHERE id = 1'
        );
        $stmt->execute([
            'nama_perusahaan' => $namaPerusahaan,
            'tagline' => $tagline,
            'warna_aksen' => $warnaAksen,
            'logo_path' => $logoPathBaru,
            'teks_footer' => $teksFooter,
        ]);

        $settings = getSettings($pdo);
        $sukses = true;
    } else {
        $settings['nama_perusahaan'] = $namaPerusahaan;
        $settings['tagline'] = $tagline;
        $settings['warna_aksen'] = $warnaAksen;
        $settings['teks_footer'] = $teksFooter;
    }
}

$pageTitle = 'Pengaturan Branding';
$basePath = '../';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Pengaturan Branding</h1>
</div>

<div class="card">
    <?php if ($sukses) : ?>
        <div class="alert alert-success">Pengaturan branding berhasil disimpan.</div>
    <?php endif; ?>

    <?php if ($errors) : ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $err) : ?>
                    <li><?php echo e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" novalidate>
        <div class="form-group">
            <label for="nama_perusahaan">Nama Perusahaan</label>
            <input type="text" id="nama_perusahaan" name="nama_perusahaan" value="<?php echo e($settings['nama_perusahaan']); ?>" required>
        </div>
        <div class="form-group">
            <label for="tagline">Tagline</label>
            <input type="text" id="tagline" name="tagline" value="<?php echo e($settings['tagline']); ?>">
        </div>
        <div class="form-group">
            <label for="warna_aksen">Warna Aksen (kode HEX)</label>
            <input type="color" id="warna_aksen_picker" value="<?php echo e($settings['warna_aksen']); ?>" style="width:60px;height:40px;padding:2px;vertical-align:middle;margin-right:8px;">
            <input type="text" id="warna_aksen" name="warna_aksen" value="<?php echo e($settings['warna_aksen']); ?>" placeholder="#1e3a5f" pattern="^#[0-9a-fA-F]{6}$" style="width:140px;display:inline-block;" required>
        </div>
        <div class="form-group">
            <label for="teks_footer">Teks Footer</label>
            <input type="text" id="teks_footer" name="teks_footer" value="<?php echo e($settings['teks_footer']); ?>" required>
        </div>
        <div class="form-group">
            <label>Logo Perusahaan</label>
            <?php if (!empty($settings['logo_path'])) : ?>
                <div style="margin-bottom:10px;">
                    <img src="<?php echo e($basePath . $settings['logo_path']); ?>" alt="Logo saat ini" style="max-height:60px;max-width:240px;display:block;margin-bottom:8px;">
                    <label class="text-small">
                        <input type="checkbox" name="hapus_logo" value="1"> Hapus logo saat ini
                    </label>
                </div>
            <?php else : ?>
                <p class="text-muted text-small">Belum ada logo. Tanpa logo, sistem menampilkan singkatan nama perusahaan.</p>
            <?php endif; ?>
            <input type="file" name="logo" accept=".png,.jpg,.jpeg,.svg">
            <p class="text-muted text-small">Format PNG, JPG, atau SVG. Maksimal 2MB.</p>
        </div>
        <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
        <a href="dashboard.php" class="btn btn-link">Kembali ke Dashboard</a>
    </form>
</div>

<script>
document.getElementById('warna_aksen_picker').addEventListener('input', function () {
    document.getElementById('warna_aksen').value = this.value;
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
