# ADR-004: Model Aplikasi Privat, Provisioning Pemilik via CLI, dan Keamanan Autentikasi

- **Status**: Diterima (Accepted)
- **Tanggal**: 2026-10-17
- **Pengambil Keputusan**: Tim Rekayasa Monetra

---

## 1. Konteks
Monetra adalah aplikasi catatan keuangan pribadi untuk satu pengguna pemilik (*single-user private app*). Membiarkan formulir pendaftaran terbuka untuk publik (*open registration*) menimbulkan risiko keamanan serius, potensi serangan spam/bot, dan akses pihak ketiga yang tidak diinginkan.

## 2. Keputusan
1. **Pendaftaran Publik Dinonaktifkan**:
   - Seluruh rute pendaftaran publik (`GET /register`, `POST /register`) dihapus secara total dari aplikasi. Permintaan ke URL tersebut menghasilkan 404 Not Found.
2. **Provisioning Pemilik via CLI**:
   - Akun pemilik dibuat melalui Artisan Command `php artisan monetra:create-owner` yang interaktif dan menyembunyikan input password (tidak terekam di riwayat bash).
3. **Pertahanan Autentikasi**:
   - Rate limiting ketat pada endpoint login: Maksimal 5 percobaan gagal per menit per kombinasi email dan alamat IP (menghasilkan HTTP 429).
   - Percobaan gagal dicatat ke log keamanan terisolasi.

## 3. Alternatif yang Dipertimbangkan
- *Formulir registrasi dengan kode undangan (invite code)*: Ditolak karena tetap membuka permukaan serangan publik yang tidak perlu.
- *Hardcoded user credentials di seeder*: Ditolak keras karena risiko kebocoran kata sandi ke dalam source control.

## 4. Konsekuensi
- **Positif**:
  - Nol resiko pendaftaran akun liar oleh pihak luar.
  - Alur autentikasi ringkas, terfokus, dan sangat aman.
- **Negatif / Mitigasi**:
  - Pembuatan akun awal membutuhkan akses terminal CLI server.
