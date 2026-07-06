# Otentik ID — Sistem Validasi Tanda Tangan & Keabsahan Dokumen

Sistem PHP native (tanpa framework, tanpa Composer) + MySQL untuk menerbitkan
dan memverifikasi keabsahan dokumen melalui kode unik dan QR code. Dirancang
untuk berjalan di shared hosting cPanel standar (PHP 7.4+, MySQL 5.7+).

## Struktur Folder

```
/tandatangan-validator/
├── config.php              Konfigurasi koneksi database & salt hash
├── schema.sql               Skema database (import via phpMyAdmin)
├── .htaccess                 Blokir akses file sensitif, nonaktifkan directory listing
├── create_admin.php         Script sekali-pakai membuat admin pertama (HAPUS setelah dipakai)
├── /admin/                   Panel admin (butuh login)
├── /verify/                  Halaman publik verifikasi dokumen (tanpa login)
├── /assets/                  CSS & JS statis (termasuk qrcode-generator lokal)
└── /includes/                Fungsi bersama, header, footer
```

## Panduan Instalasi di Shared Hosting cPanel

1. **Upload file**
   Upload seluruh isi folder `tandatangan-validator/` ke `public_html/` (atau
   subdomain/subfolder pilihan Anda) via File Manager cPanel atau FTP.

2. **Buat database MySQL**
   - Masuk ke cPanel → **MySQL Databases**.
   - Buat database baru, misalnya `namauser_otentik`.
   - Buat user MySQL baru dan berikan **All Privileges** ke database tersebut.
   - Catat nama database, username, dan password.

3. **Import schema.sql**
   - Buka **phpMyAdmin** dari cPanel.
   - Pilih database yang baru dibuat.
   - Buka tab **Import**, pilih file `schema.sql`, lalu jalankan.
   - Pastikan 3 tabel berhasil dibuat: `admins`, `documents`, `verification_logs`.

4. **Edit config.php**
   Buka `config.php` dan isi sesuai kredensial database Anda:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'namauser_otentik');
   define('DB_USER', 'namauser_dbuser');
   define('DB_PASS', 'password_database_anda');
   ```
   **Ganti juga `SALT_RAHASIA`** dengan string acak panjang milik Anda sendiri
   sebelum digunakan untuk produksi (lihat catatan keamanan di bawah).

5. **Jalankan create_admin.php**
   Akses `https://domain-anda.com/create_admin.php` melalui browser, isi
   username, nama lengkap, dan password untuk admin pertama, lalu submit.

6. **HAPUS create_admin.php**
   Setelah admin pertama berhasil dibuat, **segera hapus file
   `create_admin.php`** dari server (via File Manager/FTP). File ini tidak
   memiliki proteksi login dan akan otomatis menolak membuat admin baru jika
   sudah ada admin, namun tetap harus dihapus demi keamanan.

7. **Mulai pakai**
   - Login admin di `https://domain-anda.com/admin/login.php`.
   - Terbitkan dokumen baru melalui menu **+ Terbitkan Dokumen Baru**.
   - Bagikan QR code / kode unik ke penerima dokumen.
   - Publik dapat memverifikasi via `https://domain-anda.com/verify/`.

## Catatan Keamanan

- **Wajib ganti `SALT_RAHASIA`** di `config.php` dengan nilai acak dan rahasia
  sebelum dipakai di produksi. Nilai default hanya untuk pengembangan.
- **Hapus `create_admin.php`** setelah admin pertama dibuat.
- Pastikan hosting menjalankan PHP melalui HTTPS agar sesi login admin dan
  data verifikasi tidak dapat disadap.
- Jangan bagikan kredensial database (`DB_USER`/`DB_PASS`) di tempat publik.
- File `.htaccess` sudah memblokir akses langsung ke `config.php` dan
  `schema.sql`, namun tetap pastikan modul `mod_rewrite`/`mod_authz` aktif di
  hosting Anda.

## Library Pihak Ketiga

- `assets/js/qrcode.min.js` — QR Code generator open source dari
  [kazuhikoarase/qrcode-generator](https://github.com/kazuhikoarase/qrcode-generator)
  (lisensi MIT), disertakan sebagai file lokal, tidak memerlukan koneksi
  internet untuk generate QR code di halaman `issue_success.php`.
- Fitur "Scan QR pakai Kamera" di `verify/index.php` memuat pustaka
  `html5-qrcode` dari CDN saat tombol scan ditekan. Fitur ini opsional — jika
  browser tidak memiliki akses internet, sistem akan otomatis menampilkan
  pesan fallback dan pengguna tetap bisa memasukkan kode secara manual.
# otentikID
