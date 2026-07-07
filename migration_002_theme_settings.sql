-- Otentik ID - Migration 002: Theme customization settings
-- Jalankan setelah schema.sql/migration sebelumnya di database aplikasi.

ALTER TABLE settings
    ADD COLUMN IF NOT EXISTS tema_preset VARCHAR(30) NOT NULL DEFAULT 'corporate',
    ADD COLUMN IF NOT EXISTS warna_sidebar VARCHAR(7) NOT NULL DEFAULT '#111827',
    ADD COLUMN IF NOT EXISTS warna_topbar VARCHAR(7) NOT NULL DEFAULT '#ffffff',
    ADD COLUMN IF NOT EXISTS warna_background VARCHAR(7) NOT NULL DEFAULT '#f1f5f9',
    ADD COLUMN IF NOT EXISTS warna_kartu_stat VARCHAR(7) NOT NULL DEFAULT '#1e3a8a',
    ADD COLUMN IF NOT EXISTS warna_teks_kartu_stat VARCHAR(7) NOT NULL DEFAULT '#d4af37',
    ADD COLUMN IF NOT EXISTS warna_tombol VARCHAR(7) NOT NULL DEFAULT '#d4af37',
    ADD COLUMN IF NOT EXISTS warna_tombol_teks VARCHAR(7) NOT NULL DEFAULT '#0f172a',
    ADD COLUMN IF NOT EXISTS radius_ui VARCHAR(20) NOT NULL DEFAULT 'rounded-xl',
    ADD COLUMN IF NOT EXISTS bayangan_ui VARCHAR(20) NOT NULL DEFAULT 'shadow-sm';
