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
    CONSTRAINT fk_documents_admin
        FOREIGN KEY (diterbitkan_oleh) REFERENCES admins(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    INDEX idx_kode (kode_unik),
    INDEX idx_status (status),
    INDEX idx_brand (brand_penerbit),
    INDEX idx_dibuat_pada (dibuat_pada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
