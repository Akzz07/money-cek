# Fase 0: Fondasi Proyek & Lingkungan Rekayasa
**Status**: ✅ Selesai · **Target Waktu**: 1–2 Hari Kerja

Dokumen ini memuat langkah detail untuk menyelesaikan **Fase 0** sesuai dengan [Monetra_PRD_Bahasa_Indonesia.md](file:///Users/akmalzulfikar/Testing/documents/Monetra_PRD_Bahasa_Indonesia.md) dan [Monetra_Rencana_Implementasi.md](file:///Users/akmalzulfikar/Testing/documents/Monetra_Rencana_Implementasi.md).

---

## Step 0.1 — Stabilkan Koneksi Database & User Provisioning 🔐
- **Ukuran**: S (≤ 0.5 hari)
- **Tujuan**: Menjamin database dev (`mymoney`) dan database testing (`mymoney_testing`) terisolasi, menggunakan user aplikasi `monetra` dengan prinsip *least privilege* (bukan root), dan migrasi awal berhasil dijalankan.
- **Rujukan**: PRD §11.1, §13, §18; Rencana Implementasi Step 0.1.

### 1. Spesifikasi Teknis & Schema
- Host: `127.0.0.1` (TCP)
- Port: `8000` (XAMPP MariaDB) atau `3306` (Homebrew MariaDB tergantung preferensi lokal yang aktif)
- Charset: `utf8mb4` | Collation: `utf8mb4_unicode_ci`
- Database:
  - `mymoney` (Development)
  - `mymoney_testing` (Automated Testing)
- Pengguna Database: `monetra` (hanya berhak `ALL PRIVILEGES` pada `mymoney.*` dan `mymoney_testing.*`)

### 2. Langkah Pengerjaan
1. Jalankan SQL Provisioning menggunakan koneksi administratif lokal:
   ```sql
   CREATE DATABASE IF NOT EXISTS mymoney CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE DATABASE IF NOT EXISTS mymoney_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER IF NOT EXISTS 'monetra'@'localhost' IDENTIFIED BY 'monetra_secret_local';
   CREATE USER IF NOT EXISTS 'monetra'@'127.0.0.1' IDENTIFIED BY 'monetra_secret_local';
   GRANT ALL PRIVILEGES ON mymoney.* TO 'monetra'@'localhost';
   GRANT ALL PRIVILEGES ON mymoney.* TO 'monetra'@'127.0.0.1';
   GRANT ALL PRIVILEGES ON mymoney_testing.* TO 'monetra'@'localhost';
   GRANT ALL PRIVILEGES ON mymoney_testing.* TO 'monetra'@'127.0.0.1';
   FLUSH PRIVILEGES;
   ```
2. Perbarui [.env](file:///Users/akmalzulfikar/Testing/.env):
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=8000 # Atau 3306 sesuai server aktif
   DB_DATABASE=mymoney
   DB_USERNAME=monetra
   DB_PASSWORD=monetra_secret_local
   ```
3. Perbarui [.env.example](file:///Users/akmalzulfikar/Testing/.env.example) agar mencerminkan konfigurasi MySQL dengan placeholder tanpa kredensial rahasia.
4. Jalankan dan uji migrasi:
   ```bash
   php artisan migrate --no-interaction
   php artisan migrate:status
   ```

### 3. Kriteria Penerimaan & Verifikasi
- [ ] `php artisan migrate:status` menampilkan semua migrasi dasar berstatus `Ran`.
- [ ] Koneksi TCP via user `monetra` berhasil tanpa menggunakan akun `root`.
- [ ] File [.env](file:///Users/akmalzulfikar/Testing/.env) tidak bocor ke Git.

---

## Step 0.2 — Inisialisasi Git & Baseline Hygiene
- **Ukuran**: S (≤ 0.5 hari)
- **Tujuan**: Memulai jejak audit source control dengan branch `main`, konfigurasi `.gitignore` yang ketat, dan baseline commit pertama.
- **Rujukan**: PRD §11.1, §22.11; Rencana Implementasi Step 0.2.

### 1. Spesifikasi Teknis
- Version Control: Git (branch utama: `main`).
- Pola Commit: *Conventional Commits* (`feat:`, `fix:`, `test:`, `chore:`, `docs:`).

### 2. Langkah Pengerjaan
1. Inisialisasi repositori Git bila belum ada:
   ```bash
   git init -b main
   ```
2. Pastikan file [.gitignore](file:///Users/akmalzulfikar/Testing/.gitignore) mengabaikan:
   - `/.env`, `/.env.backup`, `/.env.production`
   - `/vendor`, `/node_modules`
   - `/public/build`, `/public/hot`
   - `/storage/*.key`, `/storage/logs/*`
   - `/.phpunit.cache`
   - Export CSV dan file dump database (`*.sql`, `*.csv`)
3. Tambahkan [.gitattributes](file:///Users/akmalzulfikar/Testing/.gitattributes) untuk memastikan konsistensi line ending (LF).
4. Buat baseline commit:
   ```bash
   git add .
   git commit -m "chore: baseline laravel 13 skeleton with PRD, architecture, and step plans"
   ```

### 3. Kriteria Penerimaan & Verifikasi
- [ ] `git status` berstatus clean setelah commit baseline.
- [ ] `git check-ignore -v .env` mengonfirmasi bahwa `.env` diabaikan oleh Git.

---

## Step 0.3 — Standar Mutu Kode & Tooling Pengujian
- **Ukuran**: S (≤ 0.5 hari)
- **Tujuan**: Memasang gate otomatis agar formatting, analisis statis, dan pengujian berjalan konsisten sebelum kode di-merge.
- **Rujukan**: PRD §11.1, §16; Rencana Implementasi Step 0.3.

### 1. Spesifikasi Teknis
- Pengujian: PHPUnit 12 + `laravel/pao`.
- Formatter: Laravel Pint (preset `laravel`).
- Static Analysis: Larastan (PHPStan) Level 6.

### 2. Langkah Pengerjaan
1. Konfigurasikan [phpunit.xml](file:///Users/akmalzulfikar/Testing/phpunit.xml) untuk menggunakan database pengujian terisolasi:
   ```xml
   <env name="DB_CONNECTION" value="mysql"/>
   <env name="DB_DATABASE" value="mymoney_testing"/>
   ```
2. Install Larastan (memerlukan persetujuan dependensi dev):
   ```bash
   composer require --dev larastan/larastan
   ```
3. Buat file konfigurasi `phpstan.neon`:
   ```neon
   includes:
       - vendor/larastan/larastan/extension.neon
   parameters:
       paths:
           - app/
           - bootstrap/
           - config/
           - database/
           - routes/
       level: 6
   ```
4. Tambahkan bagian "Pengembangan & Pengujian" di [README.md](file:///Users/akmalzulfikar/Testing/README.md) dengan panduan perintah:
   - `php artisan test --compact`
   - `vendor/bin/pint --format agent`
   - `vendor/bin/phpstan analyse`
   - `npm run dev` / `composer run dev`

### 3. Kriteria Penerimaan & Verifikasi
- [ ] `php artisan test --compact` berjalan hijau pada database `mymoney_testing`.
- [ ] `vendor/bin/pint --test` lolos tanpa pelanggaran style.
- [ ] `vendor/bin/phpstan analyse` selesai dengan 0 error.

---

## Step 0.4 — Design System, Tailwind CSS 4 Tokens & Shell Blade Mobile-First
- **Ukuran**: M (1 hari)
- **Tujuan**: Membangun fondasi antarmuka visual mobile-first berstandar tinggi yang meniru 100% spesifikasi [Monetra Finance App UI Showcase.png](file:///Users/akmalzulfikar/Testing/documents/Monetra%20Finance%20App%20UI%20Showcase.png).
- **Rujukan**: PRD §7.1–7.3, §15; Rencana Implementasi Step 0.4.

### 1. Spesifikasi Token Desain (`resources/css/app.css`)
Gunakan sintaks native `@theme` Tailwind CSS 4:
```css
@theme {
  --color-brand-primary: #2563EB;     /* Biru utama Monetra */
  --color-brand-primary-hover: #1D4ED8;
  --color-brand-dark: #1E3A8A;
  --color-surface-bg: #F8FAFC;        /* Background abu-abu lembut */
  --color-surface-card: #FFFFFF;
  --color-accent-success: #16A34A;    /* Pemasukan / Saldo positif */
  --color-accent-danger: #DC2626;     /* Pengeluaran / Warning */
  --color-accent-warning: #D97706;    /* Ambang anggaran / Tagihan */
  --color-text-primary: #0F172A;
  --color-text-secondary: #64748B;
  --color-text-muted: #94A3B8;
  --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  --radius-card: 1rem;               /* 16px border radius */
  --radius-button: 0.75rem;          /* 12px border radius */
}
```

### 2. Komponen Blade Inti (`resources/views/components/ui/`)
- `x-ui.card`: Kartu putih dengan shadow halus, border ringan, dan padding responsif.
- `x-ui.button`: Tombol dengan varian `primary`, `secondary`, `outline`, `danger`, `ghost`, touch target ≥ 44px.
- `x-ui.badge`: Label status (Mendatang, Terlambat, Lunas, Persentase).
- `x-ui.bottom-nav`: Navigasi bawah 5 tab persis mockup:
  1. **Beranda** (Home icon)
  2. **Aktivitas** (List/Activity icon)
  3. **[+] Tambah** (Tombol aksen melingkar menonjol di tengah)
  4. **Anggaran** (Chart/Pie icon)
  5. **Aset** (Wallet icon)
- `x-ui.empty-state`: Ilustrasi, judul, deskripsi, dan tombol aksi untuk keadaan data kosong.
- `x-ui.modal-confirm`: Dialog konfirmasi aksi berbahaya (misal: Void transaksi, Arsip akun).

### 3. Shell Layout & Halaman Placeholder
- Buat `resources/views/layouts/app.blade.php` (mobile frame terpusat pada desktop, full width pada mobile ≤ 640px).
- Buat rute dan halaman placeholder awal:
  - `/` (Beranda)
  - `/transactions` (Aktivitas)
  - `/transactions/create` (Tambah Transaksi)
  - `/budgets` (Anggaran)
  - `/assets` (Aset & Laporan)

### 4. Kriteria Penerimaan & Verifikasi
- [ ] Tampilan responsif sempurna pada lebar layar 360px tanpa scroll horizontal.
- [ ] 5 Tab navigasi bawah aktif dengan indikator tab aktif (warna + bentuk).
- [ ] Seluruh tombol dan kontrol input memenuhi target sentuh ≥ 44px.
- [ ] Audit aksesibilitas: kontras warna teks dan focus ring keyboard terlihat jelas.

---

## Step 0.5 — Dokumentasi Keputusan Arsitektur (ADR)
- **Ukuran**: S (≤ 0.5 hari)
- **Tujuan**: Mendokumentasikan keputusan teknis strategis agar tidak hilang antar-sesi pengembangan.
- **Rujukan**: PRD §23; Rencana Implementasi Step 0.5.

### 1. Daftar Dokumen ADR (`docs/adr/`)
Format baku setiap ADR: *Konteks, Keputusan, Alternatif yang Dipertimbangkan, Konsekuensi, Tanggal*.
1. `ADR-001-laravel-mariadb-monolith.md`: Arsitektur Laravel 13 Blade monolit modular & kompatibilitas MariaDB 11.8/MySQL 8.
2. `ADR-002-decimal-precision-money-vo.md`: Representasi nilai moneter `DECIMAL(19,2)`, `brick/math`, dan pelarangan tipe `float`.
3. `ADR-003-transaction-lifecycle-calculated-balances.md`: Siklus status posted/voided, transfer atomik, dan penghitungan saldo on-the-fly.
4. `ADR-004-private-app-provisioning-security.md`: Nonaktif pendaftaran publik, command provisioning CLI, rate limiting throttle.
5. `ADR-005-filament-maintenance-panel-guards.md`: Filament 5 terproteksi di `/admin`, memanggil Action/Policy yang sama.
6. `ADR-006-safe-to-spend-deduction-isolation.md`: Formula Safe-to-Spend, isolasi deduksi, dan pencegahan pengurangan ganda.
7. `ADR-007-backup-restore-verification.md`: Strategi backup database, uji simulasi restore, dan ekspor data aman.

### 2. Kriteria Penerimaan & Verifikasi
- [ ] Seluruh 7 file ADR telah dibuat di direktori `docs/adr/`.
- [ ] Setiap ADR dirujuk secara tepat dari file dokumen perencanaan implementasi.
