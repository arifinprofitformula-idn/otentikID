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

        $kode = 'EPI-' . $tahun . '-' . $acak;
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

    header('Location: login.php');
    exit;
}

function sanitizeInput(?string $str): string
{
    return htmlspecialchars(trim((string) $str), ENT_QUOTES, 'UTF-8');
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
    ];

    $baris = $pdo->query('SELECT * FROM settings WHERE id = 1')->fetch();

    return $baris ? array_merge($default, $baris) : $default;
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
