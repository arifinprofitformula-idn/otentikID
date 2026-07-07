-- Otentik ID - Migration 004: Admin registration approval and account profile
-- Jalankan setelah schema.sql/migration sebelumnya di database aplikasi.

ALTER TABLE admins
    ADD COLUMN IF NOT EXISTS email VARCHAR(150) NULL UNIQUE AFTER username,
    ADD COLUMN IF NOT EXISTS status ENUM('pending','approved','rejected','inactive') NOT NULL DEFAULT 'approved' AFTER nama_lengkap,
    ADD COLUMN IF NOT EXISTS role ENUM('superadmin','admin') NOT NULL DEFAULT 'admin' AFTER status,
    ADD COLUMN IF NOT EXISTS organisasi VARCHAR(150) NULL AFTER role,
    ADD COLUMN IF NOT EXISTS alasan_daftar TEXT NULL AFTER organisasi,
    ADD COLUMN IF NOT EXISTS disetujui_oleh INT NULL AFTER alasan_daftar,
    ADD COLUMN IF NOT EXISTS disetujui_pada DATETIME NULL AFTER disetujui_oleh;

UPDATE admins
SET status = 'approved'
WHERE status IS NULL OR status = '';

UPDATE admins
SET role = 'superadmin'
WHERE id = (SELECT min_id FROM (SELECT MIN(id) AS min_id FROM admins) AS first_admin)
  AND role = 'admin';
