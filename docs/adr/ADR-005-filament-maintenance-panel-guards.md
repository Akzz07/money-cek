# ADR-005: Panel Filament 5 Terproteksi & Penegakan Aturan Domain Tanpa Jalur Pintas

- **Status**: Diterima (Accepted)
- **Tanggal**: 2026-10-17
- **Pengambil Keputusan**: Tim Rekayasa Monetra

---

## 1. Konteks
Untuk mempermudah pemeliharaan data teknis, audit log, dan peninjauan database secara visual, panel administrasi Filament 5 dipasang pada path `/admin`. Namun, jika panel admin diizinkan melakukan operasi CRUD langsung (misal: hard delete transaksi atau update saldo manual), integritas keuangan sistem akan rusak.

## 2. Keputusan
1. **Otorisasi Ketat Panel**:
   - Model `User` mengimplementasikan `FilamentUser`.
   - Metode `canAccessPanel()` membatasi akses hanya untuk akun pemilik privat. Percobaan akses oleh pengguna lain atau tamu menghasilkan HTTP 403.
2. **Tidak Ada Jalur Pintas (Zero Bypass)**:
   - Resource `Transactions` di Filament bersifat *read-only* (List & View). Pembuatan transaksi langsung dan bulk-delete dinonaktifkan.
   - Aksi pembatalan transaksi dari panel admin wajib memanggil Domain Action yang sama: `VoidTransaction` (sehingga event dan audit log tetap terpicu identik dengan Blade UI).
   - Resource `Accounts` dan `Categories` hanya mengizinkan arsip melalui Action domain, tanpa penghapusan fisik.

## 3. Alternatif yang Dipertimbangkan
- *Menggunakan tool database client eksternal (DBeaver/Navicat/phpMyAdmin) secara eksklusif*: Meskipun phpMyAdmin tersedia, interface web terintegrasi di `/admin` dengan pembatasan domain memberikan keamanan operasional yang jauh lebih baik daripada query SQL manual tanpa guard.

## 4. Konsekuensi
- **Positif**:
  - Memberikan alat administrasi yang nyaman tanpa merusak integritas invarian domain finansial.
- **Negatif / Mitigasi**:
  - Penambahan dependensi Filament di `composer.json` (dipasang pada Fase 3, Step 3.7).
