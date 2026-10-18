# ADR-007: Kebijakan Pencadangan, Verifikasi Pemulihan Data (Restore), dan Ekspor Aman

- **Status**: Diterima (Accepted)
- **Tanggal**: 2026-10-17
- **Pengambil Keputusan**: Tim Rekayasa Monetra

---

## 1. Konteks
Kehilangan data keuangan pribadi adalah risiko dengan tingkat keparahan paling kritis (*High Severity*) pada PRD §20. Backup yang tidak pernah diuji restore-nya bukanlah backup yang andal. Selain itu, ekspor data CSV berpotensi menjadi celah keamanan *CSV Formula Injection* jika tidak disanitasi.

## 2. Keputusan
1. **Pencadangan Basis Data**:
   - Skrip operasional `scripts/backup-db.sh` menghasilkan dump SQL terkompresi menggunakan `mariadb-dump` / `mysqldump` dengan timestamp dan checksum SHA-256.
   - File dump disimpan di direktori aman yang tidak dapat diakses publik dan diabaikan oleh Git.
2. **Kewajiban Uji Simulasi Restore**:
   - Sebelum rilis versi (Fase 4, Step 4.3), simulasi restore penuh wajib dilakukan ke database uji `mymoney_restore_test`.
   - Integritas data diverifikasi dengan membandingkan checksum tabel dan hasil kalkulasi saldo antar-database.
3. **Ekspor CSV Bebas Formula Injection**:
   - Seluruh sel string pada ekspor CSV yang diawali karakter eksekusi spreadsheet (`=`, `+`, `-`, `@`, `\t`, `\r`) wajib diberi prefiks kutip tunggal (`'`).
   - Endpoint ekspor dilindungi otorisasi kepemilikan dan *rate limiting*.

## 3. Alternatif yang Dipertimbangkan
- *Hanya mengandalkan snapshot hosting cloud*: Ditolak sebagai satu-satunya tumpuan karena pengguna harus memiliki kepemilikan penuh dan portabilitas atas data pribadinya dalam bentuk berkas SQL mentah.

## 4. Konsekuensi
- **Positif**:
  - Jaminan pemulihan 100% jika terjadi kegagalan perangkat keras atau kerusakan data.
  - Data yang diekspor aman dibuka di aplikasi spreadsheet mana pun.
