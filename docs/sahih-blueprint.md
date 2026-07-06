# Blueprint: Sistem Validasi Tanda Tangan & Keabsahan Dokumen
**Untuk:** Coach Arifin — PT. Emas Perak Indonesia (EPI) / Katalisis / rahasiaemas.id
**Arsitektur:** PHP + MySQL (Shared Hosting cPanel)
**Versi dokumen:** 1.0 — Juli 2026

---

## 1. RINGKASAN EKSEKUTIF

### 1.1 Masalah yang Diselesaikan
Dokumen (sertifikat, surat resmi, SK, dll.) yang diterbitkan Coach/EPI mudah dipalsukan atau digandakan tanpa cara mudah bagi penerima untuk memverifikasi keasliannya.

### 1.2 Solusi
Sistem web berbasis PHP + MySQL yang:
- Menerbitkan **kode unik + QR code + hash digital** untuk setiap dokumen yang ditandatangani.
- Menyediakan halaman publik `verify.php` untuk mengecek keabsahan dokumen — cukup scan QR atau input kode manual.
- Mencatat riwayat penerbitan dan mendukung **revoke** (pembatalan) tanpa menghapus jejak audit.

### 1.3 Prinsip Desain
- **Amanah & auditable** — setiap tindakan tercatat, tidak ada penghapusan diam-diam.
- **Sederhana untuk shared hosting** — tanpa dependency berat, tanpa butuh akses server (SSH) khusus.
- **Aman secara default** — password di-hash, prepared statements, kode unik tidak bisa ditebak.

---

## 2. ARSITEKTUR SISTEM

### 2.1 Struktur Folder
```
/tandatangan-validator/
├── config.php                 # Koneksi database & konfigurasi umum
├── schema.sql                 # Struktur database (import via phpMyAdmin)
├── .htaccess                  # Proteksi folder & pretty URL (opsional)
├── /admin/
│   ├── login.php               # Form login admin
│   ├── logout.php
│   ├── dashboard.php           # Daftar dokumen + filter status + statistik ringkas
│   ├── issue.php                # Form terbitkan dokumen baru
│   ├── issue_success.php        # Tampilkan QR + stempel digital setelah terbit
│   ├── revoke.php                # Aksi batalkan kode (dengan alasan wajib diisi)
│   ├── detail.php                # Detail satu dokumen + riwayat perubahan status
│   └── auth_check.php            # Helper: cek sesi login di setiap halaman admin
├── /verify/
│   └── index.php                  # Halaman publik verifikasi (scan/manual input)
├── /assets/
│   ├── css/style.css
│   ├── js/qr-scanner.js            # Wrapper untuk library scan QR via kamera
│   └── js/qrcode.min.js            # Library generate QR code (client-side)
├── /includes/
│   ├── functions.php                # Generate kode unik, hash, helper umum
│   ├── header.php
│   └── footer.php
├── /uploads/
│   └── stempel/                     # PNG stempel digital hasil generate (opsional simpan)
└── README.md                        # Panduan instalasi
```

### 2.2 Alur Data (Flow)

**A. Penerbitan (Admin)**
1. Coach login di `/admin/login.php`.
2. Isi form di `/admin/issue.php`: nama dokumen, jenis dokumen, brand (GOLDGRAM/MEEZAN GOLD/SILVERGRAM/Katalisis/Umum), nama penerima, tanggal terbit, catatan.
3. Sistem generate:
   - `kode_unik` — format `EPI-YYYY-XXXXXX` (6 karakter acak alfanumerik, huruf besar).
   - `hash_dokumen` — SHA-256 dari gabungan semua field + kode unik + salt rahasia (server-side, tidak bisa ditebak/direkayasa).
   - `qr_payload` — URL lengkap: `https://domain-coach.com/verify/?kode=EPI-2026-A3F9K2`
4. Simpan ke tabel `documents` dengan status `aktif`.
5. Tampilkan halaman sukses berisi QR code (generate client-side pakai qrcode.min.js) + preview "stempel digital" (kode + QR + tanggal) yang bisa di-download sebagai PNG untuk ditempel ke dokumen asli (Word/Canva/PDF editor apa pun milik Coach).

**B. Verifikasi (Publik, tanpa login)**
1. Penerima dokumen scan QR (kamera HP) → otomatis buka `verify/?kode=EPI-2026-A3F9K2`, ATAU buka link verifikasi lalu ketik kode manual.
2. Sistem query database berdasarkan `kode_unik`.
3. Tampilkan hasil:
   - ✅ **Valid** — tampilkan nama dokumen, jenis, penerima, tanggal terbit, brand penerbit.
   - ❌ **Tidak ditemukan** — kode tidak ada di database.
   - ⚠️ **Dibatalkan** — status `revoked`, tampilkan tanggal & alasan pembatalan (opsional disamarkan sebagian jika sensitif).
4. Setiap pengecekan dicatat ke tabel `verification_logs` (opsional, untuk insight: dokumen mana yang paling sering dicek, indikasi mencurigakan jika satu kode dicek berkali-kali dari lokasi berbeda).

**C. Revoke (Admin)**
1. Coach buka detail dokumen → klik "Batalkan".
2. Wajib isi alasan pembatalan.
3. Status berubah jadi `revoked`, `kode_unik` TETAP ADA di database (tidak dihapus) — hanya status & alasan yang berubah. Ini menjaga jejak audit.

---

## 3. SKEMA DATABASE

```sql
-- Tabel admin (Coach & tim yang diberi akses)
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabel dokumen yang diterbitkan
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_unik VARCHAR(20) UNIQUE NOT NULL,
    nama_dokumen VARCHAR(255) NOT NULL,
    jenis_dokumen VARCHAR(100) NOT NULL,
    brand_penerbit ENUM('GOLDGRAM','MEEZAN GOLD','SILVERGRAM','Katalisis','Umum') DEFAULT 'Umum',
    nama_penerima VARCHAR(255) NOT NULL,
    catatan TEXT NULL,
    tanggal_terbit DATE NOT NULL,
    hash_dokumen VARCHAR(64) NOT NULL,
    status ENUM('aktif','revoked') DEFAULT 'aktif',
    alasan_revoke TEXT NULL,
    direvoke_pada DATETIME NULL,
    diterbitkan_oleh INT NOT NULL,
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (diterbitkan_oleh) REFERENCES admins(id),
    INDEX idx_kode (kode_unik)
);

-- Tabel log setiap kali ada yang melakukan pengecekan/verifikasi
CREATE TABLE verification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NULL,
    kode_dicek VARCHAR(20) NOT NULL,
    hasil ENUM('valid','revoked','tidak_ditemukan') NOT NULL,
    ip_address VARCHAR(45) NULL,
    dicek_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id)
);
```

---

## 4. FITUR PER HALAMAN

| Halaman | Fitur |
|---|---|
| `admin/dashboard.php` | Tabel semua dokumen, filter by status/brand/jenis, search by kode/nama penerima, statistik ringkas (total terbit, total revoked, total verifikasi bulan ini) |
| `admin/issue.php` | Form terbitkan dengan validasi input, dropdown brand, date picker |
| `admin/issue_success.php` | QR code + kartu stempel digital siap download PNG (pakai HTML5 canvas) |
| `admin/detail.php` | Detail lengkap 1 dokumen + histori kapan saja diverifikasi (dari `verification_logs`) |
| `admin/revoke.php` | Form alasan wajib + konfirmasi sebelum submit |
| `verify/index.php` | Input manual kode ATAU auto-baca dari parameter URL `?kode=`, tombol "Scan QR" pakai kamera (library JS, tanpa perlu HTTPS khusus jika hosting sudah pakai SSL) |

---

## 5. KEAMANAN

- Password admin disimpan dengan `password_hash()` PHP (bcrypt), dicek dengan `password_verify()`.
- Semua query database pakai **PDO prepared statements** — tidak ada raw SQL dari input user.
- Kode unik digenerate dari `random_bytes()`/`bin2hex()`, bukan `rand()` — supaya tidak bisa ditebak polanya.
- Hash dokumen memakai salt rahasia yang disimpan di `config.php` (di luar folder web-accessible bila memungkinkan).
- Folder `/admin/` diproteksi sesi login PHP (`$_SESSION`) — setiap halaman admin memanggil `auth_check.php` di baris pertama.
- Rate-limiting sederhana di `verify/index.php` (opsional: max cek per IP per menit) untuk mencegah brute-force menebak kode.
- `.htaccess` mencegah listing folder & akses langsung ke `config.php`.

---

## 6. RENCANA INSTALASI DI SHARED HOSTING

1. Upload seluruh folder `tandatangan-validator/` via File Manager cPanel atau FTP.
2. Buat database MySQL baru + user database via cPanel → catat nama DB, user, password.
3. Import `schema.sql` lewat phpMyAdmin.
4. Edit `config.php` — isi kredensial database.
5. Buat 1 akun admin awal (lewat script kecil `create_admin.php` sekali jalan, lalu dihapus — akan disediakan di README).
6. Akses `https://domain-coach.com/admin/login.php` untuk mulai pakai.
7. Bagikan `https://domain-coach.com/verify/` sebagai link publik verifikasi (bisa ditaruh di QR maupun dicantumkan di footer dokumen).

---

## 7. RENCANA PENGEMBANGAN LANJUTAN (opsional, tidak dikerjakan sekarang)
- Integrasi n8n (n8n.daganta.store) sebagai backend alternatif jika ingin sinkron ke Google Sheets/WhatsApp notifikasi saat dokumen diverifikasi.
- Multi-admin dengan role (superadmin vs staff per brand).
- Export riwayat ke Excel.
- API endpoint JSON untuk verifikasi (agar bisa dipanggil dari app lain).

---
---

# PROMPT LENGKAP UNTUK VS CODE (Copilot/Claude Code/Cursor)

> Salin seluruh blok di bawah ini sebagai satu prompt ke asisten coding pilihan Coach di VS Code. Prompt ini dirancang untuk dieksekusi bertahap per section agar hasilnya rapi dan tidak terpotong.

```
Saya sedang membangun sistem "Validasi Tanda Tangan & Keabsahan Dokumen" berbasis PHP native (tanpa framework) + MySQL, untuk dijalankan di shared hosting cPanel standar (PHP 7.4+, MySQL 5.7+). Tolong bangun proyek ini lengkap, production-ready, dengan struktur folder berikut:

/tandatangan-validator/
├── config.php
├── schema.sql
├── .htaccess
├── create_admin.php        (script sekali-pakai untuk membuat admin pertama)
├── /admin/
│   ├── login.php
│   ├── logout.php
│   ├── dashboard.php
│   ├── issue.php
│   ├── issue_success.php
│   ├── revoke.php
│   ├── detail.php
│   └── auth_check.php
├── /verify/
│   └── index.php
├── /assets/
│   ├── css/style.css
│   └── js/qrcode.min.js   (pakai library qrcode-generator open source, sertakan sebagai file lokal)
├── /includes/
│   ├── functions.php
│   ├── header.php
│   └── footer.php
└── README.md

SPESIFIKASI FUNGSIONAL:

1. DATABASE (schema.sql)
   Buat 3 tabel: admins, documents, verification_logs — sesuai struktur berikut:
   [TEMPEL BLOK SQL DARI BAGIAN 3 BLUEPRINT DI ATAS DI SINI]

2. config.php
   - Definisikan konstanta koneksi database (DB_HOST, DB_NAME, DB_USER, DB_PASS) yang mudah diisi manual.
   - Buat koneksi PDO dengan error mode exception.
   - Definisikan konstanta SALT_RAHASIA untuk hashing dokumen (nilai default acak, beri komentar agar diganti).
   - session_start() di sini agar bisa dipakai semua halaman.

3. includes/functions.php
   - Fungsi generateKodeUnik($pdo): buat kode format "EPI-2026-XXXXXX" (6 karakter alfanumerik besar dari random_bytes), pastikan unik dengan cek ke tabel documents, retry jika collision.
   - Fungsi generateHashDokumen($data_array, $salt): SHA-256 dari json_encode data + salt.
   - Fungsi isLoggedIn(): cek $_SESSION['admin_id'].
   - Fungsi requireLogin(): redirect ke login.php kalau belum login.
   - Fungsi sanitizeInput($str): trim + htmlspecialchars.

4. create_admin.php
   - Script sederhana (form HTML minimal) untuk membuat 1 akun admin pertama kali: input username, password, nama lengkap → simpan dengan password_hash().
   - Tambahkan komentar tebal di paling atas: "HAPUS FILE INI SETELAH ADMIN PERTAMA DIBUAT!"

5. admin/login.php
   - Form login (username + password).
   - Verifikasi dengan password_verify().
   - Set $_SESSION['admin_id'] dan $_SESSION['admin_nama'] jika berhasil.
   - Redirect ke dashboard.php.
   - Tampilkan pesan error jika gagal (tanpa membocorkan apakah username atau password yang salah).

6. admin/auth_check.php
   - Include di baris pertama semua halaman admin (kecuali login.php).
   - Panggil requireLogin().

7. admin/dashboard.php
   - Tabel semua dokumen dari yang terbaru: kode_unik, nama_dokumen, nama_penerima, brand_penerbit, status, tanggal_terbit.
   - Filter dropdown: status (semua/aktif/revoked), brand.
   - Search box: cari berdasarkan kode_unik atau nama_penerima (pakai LIKE query, prepared statement).
   - 3 kartu statistik di atas: Total Dokumen Aktif, Total Direvoke, Total Verifikasi 30 Hari Terakhir (query dari verification_logs).
   - Tombol "+ Terbitkan Dokumen Baru" menuju issue.php.
   - Setiap baris ada link "Detail" ke detail.php?id=X.

8. admin/issue.php
   - Form dengan field: nama_dokumen (text), jenis_dokumen (text), brand_penerbit (dropdown: GOLDGRAM, MEEZAN GOLD, SILVERGRAM, Katalisis, Umum), nama_penerima (text), tanggal_terbit (date, default hari ini), catatan (textarea, optional).
   - Validasi server-side semua field wajib kecuali catatan.
   - Saat submit: generate kode_unik, hitung hash_dokumen, INSERT ke documents dengan diterbitkan_oleh = $_SESSION['admin_id'].
   - Redirect ke issue_success.php?id=X setelah berhasil.

9. admin/issue_success.php
   - Ambil data dokumen berdasarkan id dari URL.
   - Tampilkan halaman "stempel digital" berisi: kode unik besar, QR code (generate via JS qrcode.min.js dengan payload URL "https://[DOMAIN]/verify/?kode=KODE_UNIK" — domain diambil dari variabel PHP $_SERVER['HTTP_HOST']), nama dokumen, penerima, tanggal.
   - Tombol "Download sebagai PNG" — gunakan HTML5 canvas untuk render kartu stempel (kode + QR + tanggal + logo teks "EPI Verified") lalu convert ke PNG dan trigger download via JavaScript (tanpa perlu server round-trip).
   - Tombol "Kembali ke Dashboard".

10. admin/detail.php
    - Tampilkan semua field dokumen berdasarkan id dari URL.
    - Jika status revoked, tampilkan alasan & tanggal revoke dengan styling warning (kuning/merah).
    - Tampilkan tabel riwayat verifikasi dari verification_logs untuk dokumen ini (tanggal cek, hasil, IP disamarkan sebagian misal "192.168.x.x").
    - Tombol "Batalkan Dokumen Ini" (jika masih aktif) menuju revoke.php?id=X.

11. admin/revoke.php
    - GET: tampilkan form konfirmasi + textarea alasan wajib diisi.
    - POST: UPDATE status jadi 'revoked', simpan alasan_revoke dan direvoke_pada = NOW().
    - Redirect ke detail.php?id=X dengan pesan sukses.

12. verify/index.php (HALAMAN PUBLIK — TANPA LOGIN)
    - Jika ada parameter GET 'kode', otomatis proses pengecekan.
    - Jika tidak ada parameter, tampilkan form input manual kode + tombol "Scan QR pakai Kamera" (gunakan library JS ringan seperti html5-qrcode dari CDN, atau jika tidak ada akses internet di sisi server cukup sediakan input manual saja dan catat sebagai TODO).
    - Setelah kode diproses: query ke documents, tentukan hasil (valid/revoked/tidak_ditemukan), INSERT ke verification_logs dengan IP dari $_SERVER['REMOTE_ADDR'].
    - Tampilkan hasil dengan visual jelas:
      * VALID → kartu hijau, tampilkan detail dokumen.
      * REVOKED → kartu kuning/merah, tampilkan pesan "Dokumen ini telah dibatalkan penerbitnya" + tanggal (tanpa menampilkan alasan detail ke publik, cukup ke admin).
      * TIDAK DITEMUKAN → kartu abu-abu, pesan "Kode tidak ditemukan dalam sistem kami."
    - Halaman ini harus terlihat profesional dan bisa diakses dari HP (mobile-first, karena diakses lewat scan QR).

13. assets/css/style.css
    - Desain bersih, profesional, warna netral (biru tua/abu-abu sebagai warna utama, hijau untuk valid, merah untuk revoked/error).
    - Mobile responsive terutama untuk halaman verify/index.php.
    - Jangan pakai framework CSS eksternal (Bootstrap dll) — tulis CSS custom ringan agar tidak bergantung pada CDN.

14. .htaccess
    - Blokir akses langsung ke config.php dan schema.sql.
    - Nonaktifkan directory listing.

15. README.md
    - Panduan instalasi step-by-step di shared hosting cPanel: buat database, import schema.sql, edit config.php, jalankan create_admin.php, hapus create_admin.php, mulai pakai.
    - Sertakan catatan keamanan: segera ganti SALT_RAHASIA di config.php sebelum dipakai produksi.

ATURAN UMUM PENGERJAAN:
- Semua teks antarmuka dalam Bahasa Indonesia.
- Gunakan PDO dengan prepared statements di SEMUA query — tidak ada string concatenation SQL.
- Semua output ke HTML wajib di-escape dengan htmlspecialchars() untuk mencegah XSS.
- Kode harus jalan di PHP native tanpa Composer/dependency manager (karena keterbatasan shared hosting), kecuali library JS client-side yang boleh disertakan sebagai file lokal di /assets/js/.
- Kerjakan bertahap: mulai dari schema.sql + config.php + functions.php, lalu modul admin (login → dashboard → issue → issue_success → detail → revoke), baru modul verify/index.php, dan terakhir styling + README.
- Setelah selesai satu bagian, tampilkan ringkasan singkat apa yang sudah dibuat sebelum lanjut ke bagian berikutnya.

Mulai dari langkah 1: buat schema.sql dan config.php terlebih dahulu.
```

---

## 8. CATATAN UNTUK COACH ARIFIN

- Prompt di atas dirancang untuk **dieksekusi bertahap** — biarkan asisten coding menyelesaikan satu modul dulu (misal: database + login) sebelum lanjut ke modul berikutnya. Ini mencegah kode terpotong/error karena terlalu panjang sekaligus.
- Setelah semua modul jadi, **wajib** ganti `SALT_RAHASIA` di `config.php` dan **hapus `create_admin.php`** setelah akun admin pertama dibuat — dua hal ini paling sering terlewat dan jadi celah keamanan.
- Karena skala awal "puluhan dokumen per event", arsitektur ini sudah lebih dari cukup. Kalau nanti berkembang ke skala Katalisis/EPI penuh (ratusan-ribuan dokumen), bagian 7 (Rencana Pengembangan Lanjutan) bisa jadi rujukan upgrade — cukup beri tahu saya kapan waktunya.
