-- Otentik ID - Migration 003: Dynamic issuer brands
-- Jalankan setelah schema.sql/migration sebelumnya di database aplikasi.

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

ALTER TABLE documents
    MODIFY brand_penerbit VARCHAR(100) NOT NULL DEFAULT 'Umum',
    ADD COLUMN IF NOT EXISTS brand_id INT NULL AFTER brand_penerbit;

UPDATE documents d
LEFT JOIN brands b ON b.nama_brand = d.brand_penerbit
SET d.brand_id = b.id
WHERE d.brand_id IS NULL;
