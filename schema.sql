-- Otentik ID - Skema Database
-- Import file ini melalui phpMyAdmin atau tool MySQL lain sebelum menjalankan aplikasi.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_unik VARCHAR(20) UNIQUE NOT NULL,
    nama_dokumen VARCHAR(255) NOT NULL,
    jenis_dokumen VARCHAR(100) NOT NULL,
    brand_penerbit VARCHAR(100) NOT NULL DEFAULT 'Umum',
    brand_id INT NULL,
    nama_penerima VARCHAR(255) NOT NULL,
    nomor_surat VARCHAR(100) NULL,
    nama_penandatangan VARCHAR(150) NOT NULL,
    jabatan_penandatangan VARCHAR(150) NOT NULL,
    catatan TEXT NULL,
    tanggal_terbit DATE NOT NULL,
    hash_dokumen VARCHAR(64) NOT NULL,
    status ENUM('aktif','revoked') DEFAULT 'aktif',
    alasan_revoke TEXT NULL,
    direvoke_pada DATETIME NULL,
    diterbitkan_oleh INT NOT NULL,
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_documents_admin
        FOREIGN KEY (diterbitkan_oleh) REFERENCES admins(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    INDEX idx_kode (kode_unik),
    INDEX idx_status (status),
    INDEX idx_brand (brand_penerbit),
    INDEX idx_brand_id (brand_id),
    INDEX idx_dibuat_pada (dibuat_pada),
    INDEX idx_nomor_surat (nomor_surat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_brand VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(120) NOT NULL UNIQUE,
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_brands_aktif (aktif),
    INDEX idx_brands_nama (nama_brand)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO brands (nama_brand, slug, aktif) VALUES
    ('GOLDGRAM', 'goldgram', 1),
    ('MEEZAN GOLD', 'meezan-gold', 1),
    ('SILVERGRAM', 'silvergram', 1),
    ('Katalisis', 'katalisis', 1),
    ('Umum', 'umum', 1),
    ('Personal', 'personal', 1)
ON DUPLICATE KEY UPDATE nama_brand = VALUES(nama_brand);

CREATE TABLE IF NOT EXISTS settings (
    id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
    nama_perusahaan VARCHAR(150) NOT NULL DEFAULT 'Otentik ID',
    tagline VARCHAR(200) NOT NULL DEFAULT 'Validasi Keabsahan Dokumen',
    warna_aksen VARCHAR(7) NOT NULL DEFAULT '#1e3a5f',
    logo_path VARCHAR(255) NULL,
    teks_footer VARCHAR(255) NOT NULL DEFAULT 'Sistem validasi tanda tangan dan keabsahan dokumen.',
    tema_preset VARCHAR(30) NOT NULL DEFAULT 'corporate',
    warna_sidebar VARCHAR(7) NOT NULL DEFAULT '#111827',
    warna_topbar VARCHAR(7) NOT NULL DEFAULT '#ffffff',
    warna_background VARCHAR(7) NOT NULL DEFAULT '#f1f5f9',
    warna_kartu_stat VARCHAR(7) NOT NULL DEFAULT '#1e3a8a',
    warna_teks_kartu_stat VARCHAR(7) NOT NULL DEFAULT '#d4af37',
    warna_tombol VARCHAR(7) NOT NULL DEFAULT '#d4af37',
    warna_tombol_teks VARCHAR(7) NOT NULL DEFAULT '#0f172a',
    radius_ui VARCHAR(20) NOT NULL DEFAULT 'rounded-xl',
    bayangan_ui VARCHAR(20) NOT NULL DEFAULT 'shadow-sm',
    diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (id) VALUES (1) ON DUPLICATE KEY UPDATE id = id;

CREATE TABLE IF NOT EXISTS verification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NULL,
    kode_dicek VARCHAR(20) NOT NULL,
    hasil ENUM('valid','revoked','tidak_ditemukan') NOT NULL,
    ip_address VARCHAR(45) NULL,
    dicek_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_verification_logs_document
        FOREIGN KEY (document_id) REFERENCES documents(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    INDEX idx_document_id (document_id),
    INDEX idx_kode_dicek (kode_dicek),
    INDEX idx_hasil (hasil),
    INDEX idx_dicek_pada (dicek_pada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
