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
