<?php
declare(strict_types=1);

function generateKodeUnik(PDO $pdo): string
{
    $tahun = date('Y');
    $karakter = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $panjangKarakter = strlen($karakter);

    for ($percobaan = 0; $percobaan < 10; $percobaan++) {
        $acak = '';
        $bytes = random_bytes(6);

        for ($i = 0; $i < 6; $i++) {
            $acak .= $karakter[ord($bytes[$i]) % $panjangKarakter];
        }

        $kode = 'BA-' . $tahun . '-' . $acak;
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM documents WHERE kode_unik = :kode_unik');
        $stmt->execute(['kode_unik' => $kode]);

        if ((int) $stmt->fetchColumn() === 0) {
            return $kode;
        }
    }

    throw new RuntimeException('Gagal membuat kode unik. Silakan coba lagi.');
}

function generateHashDokumen(array $dataArray, string $salt): string
{
    ksort($dataArray);

    return hash('sha256', json_encode($dataArray, JSON_UNESCAPED_UNICODE) . $salt);
}

function isLoggedIn(): bool
{
    return isset($_SESSION['admin_id']) && (int) $_SESSION['admin_id'] > 0;
}

function requireLogin(): void
{
    if (isLoggedIn()) {
        return;
    }

    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    $loginPath = str_contains($scriptName, '/user/') ? '../admin/login.php' : 'login.php';

    header('Location: ' . $loginPath);
    exit;
}

function currentUserRole(): string
{
    return (string) ($_SESSION['admin_role'] ?? '');
}

function isRole(array $roles): bool
{
    return isLoggedIn() && in_array(currentUserRole(), $roles, true);
}

function requireRole(array $roles): void
{
    requireLogin();

    if (isRole($roles)) {
        return;
    }

    http_response_code(403);
    exit('Akses ditolak.');
}

function dashboardPathForRole(string $role): string
{
    return $role === 'user' ? '../user/dashboard.php' : 'dashboard.php';
}

function sanitizeInput(?string $str): string
{
    return htmlspecialchars(trim((string) $str), ENT_QUOTES, 'UTF-8');
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// === CSRF PROTECTION ===

/**
 * Return current CSRF token value (for meta tags / JS usage)
 */
function csrfToken(): string
{
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * Return hidden input field with CSRF token
 */
function csrfField(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . e(csrfToken()) . '">';
}

/**
 * Validate CSRF token from POST request — call at top of POST handlers
 */
function csrfCheck(): void
{
    $token = (string) ($_POST['_csrf_token'] ?? '');
    $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');

    if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
        http_response_code(403);
        exit('Permintaan ditolak: token keamanan tidak valid.');
    }
}

function formatTanggalIndonesia(?string $tanggal): string
{
    if ($tanggal === null || $tanggal === '') {
        return '-';
    }

    $timestamp = strtotime($tanggal);
    if ($timestamp === false) {
        return e($tanggal);
    }

    return date('d/m/Y', $timestamp);
}

function formatTanggalWaktuIndonesia(?string $tanggalWaktu): string
{
    if ($tanggalWaktu === null || $tanggalWaktu === '') {
        return '-';
    }

    $timestamp = strtotime($tanggalWaktu);
    if ($timestamp === false) {
        return e($tanggalWaktu);
    }

    return date('d/m/Y H:i', $timestamp);
}

function getSettings(PDO $pdo): array
{
    $default = [
        'nama_perusahaan' => 'Otentik ID',
        'tagline' => 'Validasi Keabsahan Dokumen',
        'warna_aksen' => '#1e3a5f',
        'logo_path' => null,
        'teks_footer' => 'Sistem validasi tanda tangan dan keabsahan dokumen.',
        'tema_preset' => 'corporate',
        'warna_sidebar' => '#111827',
        'warna_topbar' => '#ffffff',
        'warna_background' => '#f1f5f9',
        'warna_kartu_stat' => '#1e3a8a',
        'warna_teks_kartu_stat' => '#d4af37',
        'warna_tombol' => '#d4af37',
        'warna_tombol_teks' => '#0f172a',
        'radius_ui' => 'rounded-xl',
        'bayangan_ui' => 'shadow-sm',
    ];

    $baris = $pdo->query('SELECT * FROM settings WHERE id = 1')->fetch();

    return $baris ? array_merge($default, $baris) : $default;
}

function isValidHexColor(?string $value): bool
{
    return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1;
}

function getThemePresets(): array
{
    return [
        'corporate' => [
            'label' => 'Corporate Navy Gold',
            'warna_sidebar' => '#111827',
            'warna_topbar' => '#ffffff',
            'warna_background' => '#f1f5f9',
            'warna_kartu_stat' => '#1e3a8a',
            'warna_teks_kartu_stat' => '#d4af37',
            'warna_tombol' => '#d4af37',
            'warna_tombol_teks' => '#0f172a',
        ],
        'emerald' => [
            'label' => 'Emerald Executive',
            'warna_sidebar' => '#064e3b',
            'warna_topbar' => '#ffffff',
            'warna_background' => '#ecfdf5',
            'warna_kartu_stat' => '#065f46',
            'warna_teks_kartu_stat' => '#facc15',
            'warna_tombol' => '#059669',
            'warna_tombol_teks' => '#ffffff',
        ],
        'slate' => [
            'label' => 'Slate Minimal',
            'warna_sidebar' => '#0f172a',
            'warna_topbar' => '#ffffff',
            'warna_background' => '#f8fafc',
            'warna_kartu_stat' => '#334155',
            'warna_teks_kartu_stat' => '#f8fafc',
            'warna_tombol' => '#334155',
            'warna_tombol_teks' => '#ffffff',
        ],
        'maroon' => [
            'label' => 'Maroon Premium',
            'warna_sidebar' => '#3f0d12',
            'warna_topbar' => '#fffaf0',
            'warna_background' => '#fff7ed',
            'warna_kartu_stat' => '#7f1d1d',
            'warna_teks_kartu_stat' => '#fbbf24',
            'warna_tombol' => '#b45309',
            'warna_tombol_teks' => '#ffffff',
        ],
    ];
}

function slugifyBrand(string $namaBrand): string
{
    $slug = strtolower(trim($namaBrand));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');

    return $slug !== '' ? $slug : 'brand';
}

function generateUniqueBrandSlug(PDO $pdo, string $namaBrand, ?int $abaikanId = null): string
{
    $baseSlug = slugifyBrand($namaBrand);
    $slug = $baseSlug;
    $counter = 2;

    while (true) {
        $sql = 'SELECT COUNT(*) FROM brands WHERE slug = :slug';
        $params = ['slug' => $slug];

        if ($abaikanId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $abaikanId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if ((int) $stmt->fetchColumn() === 0) {
            return $slug;
        }

        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
}

function getBrands(PDO $pdo, bool $hanyaAktif = false): array
{
    $sql = 'SELECT id, nama_brand, slug, aktif, dibuat_pada, diperbarui_pada FROM brands';
    if ($hanyaAktif) {
        $sql .= ' WHERE aktif = 1';
    }
    $sql .= ' ORDER BY aktif DESC, nama_brand ASC';

    return $pdo->query($sql)->fetchAll();
}

function getBrandById(PDO $pdo, int $brandId, bool $hanyaAktif = false): ?array
{
    $sql = 'SELECT id, nama_brand, slug, aktif FROM brands WHERE id = :id';
    if ($hanyaAktif) {
        $sql .= ' AND aktif = 1';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $brandId]);
    $brand = $stmt->fetch();

    return $brand ?: null;
}

function passwordKuat(string $password): bool
{
    return strlen($password) >= 10
        && preg_match('/[a-z]/', $password) === 1
        && preg_match('/[A-Z]/', $password) === 1
        && preg_match('/[0-9]/', $password) === 1;
}

function auditLog(PDO $pdo, ?int $adminId, string $aksi, string $detail = ''): void
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs (admin_id, aksi, detail)
             VALUES (:admin_id, :aksi, :detail)'
        );
        $stmt->execute([
            'admin_id' => $adminId,
            'aksi' => $aksi,
            'detail' => $detail !== '' ? $detail : null,
        ]);
    } catch (Throwable $e) {
        error_log('Gagal menulis audit log: ' . $e->getMessage());
    }
}

function warnaLebihGelap(string $hex, float $persen = 0.15): string
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
        $hex = '1e3a5f';
    }

    [$r, $g, $b] = [
        (int) hexdec(substr($hex, 0, 2)),
        (int) hexdec(substr($hex, 2, 2)),
        (int) hexdec(substr($hex, 4, 2)),
    ];

    $r = (int) max(0, $r * (1 - $persen));
    $g = (int) max(0, $g * (1 - $persen));
    $b = (int) max(0, $b * (1 - $persen));

    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

function samarkanIp(?string $ip): string
{
    if ($ip === null || $ip === '') {
        return '-';
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $bagian = explode('.', $ip);
        return $bagian[0] . '.' . $bagian[1] . '.x.x';
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $bagian = explode(':', $ip);
        return ($bagian[0] ?? 'xxxx') . ':' . ($bagian[1] ?? 'xxxx') . ':xxxx:xxxx:xxxx:xxxx:xxxx:xxxx';
    }

    return 'x.x.x.x';
}
