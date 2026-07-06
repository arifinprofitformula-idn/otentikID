# Konteks Project: Sistem Validasi Tanda Tangan Dokumen

## Apa project ini

Sistem PHP native + MySQL untuk menerbitkan dan memverifikasi keabsahan
dokumen via kode unik + QR code. Target deploy: shared hosting cPanel.
Blueprint lengkap ada di /docs/Blueprint_Sistem_Validasi_Tandatangan_Dokumen.md
— selalu rujuk file itu sebagai sumber kebenaran struktur & fitur.

## Batasan teknis (JANGAN dilanggar)

- PHP native saja, TIDAK PAKAI framework (Laravel, Symfony, dll) dan
  TIDAK PAKAI Composer/dependency manager.
- Database: MySQL murni, semua query WAJIB pakai PDO prepared statements.
- Tidak ada build step (tidak ada npm build, webpack, dll) — semua asset
  CSS/JS ditulis langsung sebagai file statis di /assets/.
- Bahasa antarmuka: Bahasa Indonesia.

## Yang TIDAK relevan untuk project ini

Jangan bawa masuk konteks dari project Coach yang lain (EPIC Hub, VaultMind,
FinPlan Pro, poster generator EPI, dsb) kecuali diminta eksplisit.
Jangan sarankan framework, library besar, atau arsitektur cloud/serverless
untuk project ini — target deploy tetap shared hosting sederhana.

## Kalau ragu

Tanya dulu sebelum menambah dependency, mengubah struktur folder di
blueprint, atau memperluas scope fitur di luar yang tertulis di blueprint.
