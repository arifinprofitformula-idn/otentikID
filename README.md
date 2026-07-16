# Sistem Otentik ID

Sistem validasi dan verifikasi keabsahan dokumen berbasis web — terbitkan dokumen resmi dengan kode unik dan QR code, lalu publik bisa memverifikasi keasliannya secara instan.

Dibangun dengan PHP native (tanpa framework, tanpa Composer) + MySQL/MariaDB. Siap jalan di shared hosting maupun VPS.

---

## Fitur

**Panel Admin**
- Login admin dengan session security (HttpOnly, Secure, SameSite)
- Dashboard ringkasan dokumen & verifikasi
- Terbitkan dokumen baru + generate QR code otomatis
- Manajemen dokumen (detail, revoke/pembatalan)
- Multi-brand — kelola beberapa brand/penerbit dokumen
- Manajemen pengguna (registrasi, persetujuan, role superadmin/admin/user)
- Pengaturan tema & branding (warna, logo, preset tema)
- Audit log untuk semua aksi admin
- Proteksi CSRF + session regeneration

**Halaman Publik**
- Landing page dengan branding kustom
- Verifikasi dokumen via kode unik atau scan QR code (kamera)
- Tampilan hasil: Valid / Dibatalkan / Tidak Ditemukan
- Registrasi pengguna (dengan persetujuan admin)

**Keamanan**
- Session: secure cookie, HttpOnly, SameSite=Lax
- CSRF protection (token auto-regenerate tiap 30 menit)
- Password hashing dengan `password_hash()` (bcrypt)
- Input sanitization & XSS prevention (`htmlspecialchars`)
- Semua query database menggunakan prepared statements (PDO)
- Kode unik dokumen di-hash dengan SHA-256 + salt
- File sensitif diblokir akses publik

---

## Teknologi

| Komponen | Detail |
|----------|--------|
| Bahasa | PHP 7.4+ (native, tanpa framework) |
| Database | MySQL 5.7+ / MariaDB 10.x |
| Session | File-based (storage/sessions) |
| QR Code | qrcode-generator (MIT, lokal) |
| CSS | Tailwind CSS CDN (admin) + custom CSS (public) |
| Web Server | Apache (`.htaccess`) atau Caddy/Nginx |

---

## Persyaratan

- PHP 7.4 atau lebih baru (disarankan 8.1+)
- MySQL 5.7+ atau MariaDB 10.x
- Ekstensi PHP: `pdo_mysql`, `mbstring`, `json`
- `mod_rewrite` aktif (jika pakai Apache)
- HTTPS (wajib untuk produksi)

---

## Instalasi

### 1. Upload / Clone

Upload seluruh file ke `public_html/` atau folder tujuan Anda.

```bash
git clone https://github.com/arifinprofitformula-idn/otentikID.git .
```

### 2. Buat Database

Buat database MySQL baru, misalnya `otentik`, lalu import schema:

```bash
mysql -u root -p otentik < schema.sql
mysql -u root -p otentik < migration_001_security_tables.sql
mysql -u root -p otentik < migration_002_theme_settings.sql
mysql -u root -p otentik < migration_003_dynamic_brands.sql
mysql -u root -p otentik < migration_004_user_registration_password.sql
mysql -u root -p otentik < migration_005_user_roles_document_ownership.sql
```

### 3. Konfigurasi

Copy `config.example.php` menjadi `config.php`:

```bash
cp config.example.php config.php
```

Edit `config.php` — isi kredensial database dan ganti `SALT_RAHASIA`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'nama_database');
define('DB_USER', 'username_db');
define('DB_PASS', 'password_db');

define('SALT_RAHASIA', 'STRING_ACAK_RAHASIA_ANDA_MINIMAL_32_KARAKTER');
```

**⚠️ Wajib ganti `SALT_RAHASIA` sebelum dipakai produksi.** Nilai default hanya untuk development.

### 4. Buat Admin Pertama

Buka `https://domain-anda.com/create_admin.php` melalui browser. Isi username, nama lengkap, dan password admin pertama, lalu submit.

### 5. HAPUS create_admin.php

Setelah admin berhasil dibuat, **segera hapus file `create_admin.php`** dari server:

```bash
rm create_admin.php
```

File ini tidak memiliki proteksi login dan hanya digunakan satu kali saat instalasi.

### 6. Selesai

- Login admin: `https://domain-anda.com/admin/login.php`
- Verifikasi publik: `https://domain-anda.com/verify/`

---

## Struktur Folder

```
/
├── config.example.php       Template konfigurasi (copy ke config.php)
├── schema.sql               Skema database utama
├── migration_*.sql           Migration tambahan (security, tema, brand, user)
├── create_admin.php         Script sekali-pakai buat admin pertama (HAPUS setelah dipakai)
├── .htaccess                Blokir akses file sensitif (Apache)
├── .gitignore               
│
├── admin/                   Panel admin (butuh login)
│   ├── login.php            Halaman login
│   ├── dashboard.php        Dashboard ringkasan
│   ├── issue.php            Terbitkan dokumen baru
│   ├── brands.php           Manajemen brand
│   ├── registrations.php    Manajemen pendaftaran user
│   ├── settings.php         Pengaturan tema & branding
│   └── ...
│
├── verify/                  Halaman publik verifikasi dokumen
│   └── index.php            Form verifikasi kode unik + QR scanner
│
├── user/                    Dashboard user (setelah registrasi disetujui)
│
├── includes/                Shared functions, header, footer, layout
│   ├── functions.php        Utilitas: CSRF, auth, hash, validasi
│   ├── header.php           Header publik (+ dynamic branding)
│   ├── footer.php           Footer publik
│   └── admin_layout.php     Layout panel admin
│
├── assets/                  CSS & JS statis
│   ├── css/style.css
│   └── js/qrcode.min.js     QR code generator (MIT license, lokal)
│
├── storage/sessions/        File session PHP
└── uploads/branding/        Upload logo brand
```

---

## Server Web — Selain Apache

Aplikasi ini menyertakan `.htaccess` untuk Apache. Jika Anda menggunakan **Caddy** atau **Nginx**, berikut konfigurasi minimalnya:

### Caddy

```caddyfile
domain-anda.com {
    root * /var/www/otentik

    @blocked {
        path /config.php /schema.sql
    }
    handle @blocked {
        respond 403
    }

    @assets {
        path *.css *.js *.png *.jpg *.svg *.ico *.woff2 *.ttf *.woff *.pdf
    }
    handle @assets {
        file_server
    }

    handle {
        rewrite * {path}index.php
        reverse_proxy unix//run/php/php8.3-fpm.sock {
            transport fastcgi {
                split .php
                root /var/www/otentik
            }
        }
    }
}
```

### Nginx

```nginx
server {
    listen 443 ssl;
    server_name domain-anda.com;
    root /var/www/otentik;

    index index.php;

    location ~ /(config\.php|schema\.sql) {
        deny all;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## Keamanan — Checklist Produksi

Sebelum go-live, pastikan:

- [ ] `config.php` sudah diisi kredensial database yang benar
- [ ] `SALT_RAHASIA` sudah diganti dengan string acak minimal 32 karakter
- [ ] `create_admin.php` sudah dihapus dari server
- [ ] `config.php` diblokir dari akses publik (403)
- [ ] `schema.sql` dan file migration diblokir dari akses publik
- [ ] HTTPS aktif (Let's Encrypt / Cloudflare)
- [ ] `display_errors = Off` di `php.ini`
- [ ] `expose_php = Off` di `php.ini`
- [ ] `allow_url_include = Off` di `php.ini`
- [ ] `disable_functions` mencakup `exec, system, shell_exec, passthru, popen, proc_open`
- [ ] Folder `storage/` dan `uploads/` writable oleh PHP-FPM
- [ ] Session cookie: `secure`, `HttpOnly`, `SameSite=Lax`
- [ ] Rate limiting diaktifkan (Caddy/Nginx/Cloudflare)

---

## Library Pihak Ketiga

- `assets/js/qrcode.min.js` — QR Code generator dari [kazuhikoarase/qrcode-generator](https://github.com/kazuhikoarase/qrcode-generator) (lisensi MIT). Disertakan sebagai file lokal — tidak memerlukan koneksi internet untuk generate QR code.

- Fitur "Scan QR pakai Kamera" di `verify/index.php` memuat `html5-qrcode` dari CDN saat tombol scan ditekan. Fitur ini opsional — fallback ke input manual jika tidak ada koneksi internet.

---

## Format Kode Unik

Setiap dokumen mendapat kode unik dengan format:

```
BA-YYYY-XXXXXX
```

- `BA` — prefix aplikasi
- `YYYY` — tahun terbit
- `XXXXXX` — 6 karakter acak (alphanumeric uppercase)

Contoh: `BA-2026-A7K92M`

---

## Kontribusi

Pull request dipersilakan. Untuk perubahan besar, buka issue terlebih dahulu untuk diskusi.

---

## Lisensi

Proyek ini menggunakan lisensi [MIT](https://opensource.org/licenses/MIT).
