-- Otentik ID - Migration 001: Security tables and admin 2FA columns
-- Jalankan setelah schema.sql di database aplikasi.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NULL,
    berhasil TINYINT(1) NOT NULL DEFAULT 0,
    dicoba_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempts_username_time (username, dicoba_pada),
    INDEX idx_login_attempts_ip_time (ip_address, dicoba_pada),
    INDEX idx_login_attempts_berhasil (berhasil)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,
    aksi VARCHAR(100) NOT NULL,
    detail TEXT NULL,
    dilakukan_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_logs_admin
        FOREIGN KEY (admin_id) REFERENCES admins(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    INDEX idx_audit_logs_admin_id (admin_id),
    INDEX idx_audit_logs_aksi (aksi),
    INDEX idx_audit_logs_dilakukan_pada (dilakukan_pada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS add_admin_security_columns;

DELIMITER $$

CREATE PROCEDURE add_admin_security_columns()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'admins'
          AND COLUMN_NAME = 'secret_2fa'
    ) THEN
        ALTER TABLE admins
            ADD COLUMN secret_2fa VARCHAR(255) NULL AFTER nama_lengkap;
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'admins'
          AND COLUMN_NAME = 'two_fa_aktif'
    ) THEN
        ALTER TABLE admins
            ADD COLUMN two_fa_aktif TINYINT(1) NOT NULL DEFAULT 0 AFTER secret_2fa;
    END IF;
END$$

DELIMITER ;

CALL add_admin_security_columns();

DROP PROCEDURE IF EXISTS add_admin_security_columns;
