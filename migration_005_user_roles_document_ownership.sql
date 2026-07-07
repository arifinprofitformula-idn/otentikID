-- Otentik ID - Migration 005: User role separation and document ownership
-- Jalankan setelah schema.sql/migration sebelumnya di database aplikasi.

ALTER TABLE admins
    MODIFY role ENUM('superadmin','admin','user') NOT NULL DEFAULT 'user';

UPDATE admins
SET role = 'user'
WHERE role IS NULL OR role = '';

UPDATE admins
SET role = 'superadmin'
WHERE id = (SELECT min_id FROM (SELECT MIN(id) AS min_id FROM admins) AS first_admin)
  AND role <> 'superadmin';

ALTER TABLE documents
    ADD COLUMN IF NOT EXISTS pemilik_id INT NULL AFTER diterbitkan_oleh,
    ADD INDEX IF NOT EXISTS idx_pemilik_id (pemilik_id);

UPDATE documents
SET pemilik_id = diterbitkan_oleh
WHERE pemilik_id IS NULL;
