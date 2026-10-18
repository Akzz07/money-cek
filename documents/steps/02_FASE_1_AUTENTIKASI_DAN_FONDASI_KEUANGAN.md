# Fase 1: Autentikasi & Fondasi Keuangan Inti
**Status**: ⬜ Dalam Persiapan · **Target Waktu**: 3–5 Hari Kerja

Fase 1 adalah fase paling krusial dalam sistem Monetra. Sesuai prinsip *Milestone 1*: **"Fondasi keuangan yang benar dan teruji lebih dulu; dashboard dibangun di atas fondasi yang sudah lulus rekonsiliasi."**

---

## Step 1.1 — Autentikasi Privat & Preferensi Profil 🔐
- **Status**: ✅ Selesai · **Ukuran**: L (1–1.5 hari)
- **Tujuan**: Membangun mekanisme login/logout/reset kata sandi untuk aplikasi privat (pendaftaran publik dinonaktifkan total), rate limiting ketat, perluasan preferensi pengguna (zona waktu, jadwal gajian), dan command CLI provisioning pemilik.
- **Rujukan**: PRD FR-001, FR-002, OD-01, OD-11, DT-05, §12.1.

### 1. Spesifikasi Teknis
- Paket Starter: `laravel/breeze` (Blade stack, dikustomisasi penuh).
- Rute Registrasi Publik: Dihapus total (`GET /register`, `POST /register` mengembalikan 404).
- Rate Limiting: 5 percobaan login gagal per menit per kombinasi email + IP (mengembalikan HTTP 429).
- Perluasan Kolom Tabel `users`:
  - `timezone`: VARCHAR(50), default `Asia/Jakarta`.
  - `locale`: VARCHAR(10), default `id`.
  - `currency`: VARCHAR(3), default `IDR`.
  - `payday_day`: TINYINT UNSIGNED NULLABLE (1–31).
  - `next_payday_date`: DATE NULLABLE.
- Command CLI: `php artisan monetra:create-owner` (idempoten, interaktif tersembunyi untuk input kata sandi).
- Domain Service: `App\Domain\Finance\PaydaySchedule`:
  - Metode: `nextPayday(User $user, ?CarbonImmutable $from = null): CarbonImmutable`
  - Aturan: Jika `payday_day` melebihi jumlah hari dalam bulan bersangkutan (misal: 31 pada bulan Februari atau April), tanggal jatuh tempo adalah **hari terakhir bulan tersebut**.

### 2. Langkah Pengerjaan
1. Instal Laravel Breeze (setelah dependensi disetujui):
   ```bash
   composer require --dev laravel/breeze
   php artisan breeze:install blade --no-interaction
   ```
2. Hapus rute registrasi dari `routes/auth.php` dan hapus view `resources/views/auth/register.blade.php`.
3. Terapkan tampilan login persis [Monetra Finance App UI Showcase.png](file:///Users/akmalzulfikar/Testing/documents/Monetra%20Finance%20App%20UI%20Showcase.png) Layar #1 (Logo Monetra, tagline, form bersih, tombol Masuk warna biru `--color-brand-primary`).
4. Buat migrasi penambahan kolom preferensi ke tabel `users`.
5. Buat Artisan Command `CreateOwnerCommand` di `app/Console/Commands/CreateOwnerCommand.php`.
6. Implementasikan kelas `App\Domain\Finance\PaydaySchedule` dan buat unit test lengkap (uji bulan 28/29/30/31 hari, tahun kabisat, dan explicit payday date).

### 3. Kriteria Penerimaan & Verifikasi
- [ ] Permintaan `GET /register` mengembalikan status 404 Not Found.
- [ ] Percobaan login gagal ke-6 dalam 1 menit menghasilkan status 429 Too Many Requests.
- [ ] Command `php artisan monetra:create-owner` berhasil membuat akun pemilik tanpa membocorkan password di history bash.
- [ ] Unit test `PaydayScheduleTest` hijau untuk semua variasi kalender masehi.

---

## Step 1.2 — Domain Uang: Value Object `Money` & Format IDR
- **Status**: ✅ Selesai · **Ukuran**: M (1 hari)
- **Tujuan**: Menjamin seluruh aplikasi memiliki satu representasi nilai uang yang immutable, akurat secara desimal, dilarang memakai floating point, dan diformat konsisten ke gaya IDR (`Rp12.450.000`).
- **Rujukan**: PRD §9.1, DT-04, DT-12; Rencana Implementasi Step 1.2.

### 1. Spesifikasi Teknis
- Dependensi: `brick/math` (dideklarasikan eksplisit di `composer.json`).
- Arsitektur:
  - `App\Domain\Money\Money` (final readonly class).
  - `App\Domain\Money\RoundingPolicy` (konfigurasi pembulatan terpusat: `RoundingMode::HALF_UP`, presisi 2 desimal).
  - `App\Domain\Money\MoneyFormatter` (`NumberFormatter('id_ID')`, default tanpa desimal untuk IDR).
  - `App\Casts\MoneyCast` (Eloquent attribute cast: `DECIMAL(19,2)` string ↔ `Money` object).
  - Helper / Blade Directive: `@money($amount)` → `Rp 12.450.000`.

### 2. Method Signature `Money`
```php
namespace App\Domain\Money;

use Brick\Math\BigDecimal;

final readonly class Money
{
    public static function of(string|int $amount): self;
    public static function zero(): self;
    public function add(self $other): self;
    public function subtract(self $other): self;
    public function multiply(string|int $multiplier): self;
    public function isZero(): bool;
    public function isPositive(): bool;
    public function isNegative(): bool;
    public function greaterThan(self $other): bool;
    public function lessThan(self $other): bool;
    public function equals(self $other): bool;
    public function toDecimalString(): string; // Untuk database DECIMAL(19,2)
    public function formatted(): string;       // "Rp 12.450.000"
}
```

### 3. Kriteria Penerimaan & Verifikasi
- [x] Audit kode: Tidak ada fungsi `floatval()`, `(float)`, atau operator aritmatika bawaan (`+`, `-`, `*`) pada objek moneter.
- [x] Test unit: Pengujian penjumlahan dan pengurangan nilai 19 digit tidak mengalami overflow atau precision loss.
- [x] Formatting test: Nilai `12450000.00` diformat menjadi `Rp 12.450.000`.

---

## Step 1.3 — Manajemen Akun Keuangan ✅
- **Ukuran**: L (1–1.5 hari)
- **Status**: Selesai (Tested & Pint Verified)
- **Tujuan**: Pengguna dapat mencatat akun keuangan (Bank, Tunai, Dompet Digital, Investasi, Tabungan) beserta saldo awal (`opening_balance`) sebagai dasar seluruh perhitungan saldo.
- **Rujukan**: PRD FR-003, §10.2, OD-05.

### 1. Spesifikasi Teknis & Schema
- Tabel: `accounts`
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `user_id`: BIGINT UNSIGNED NOT NULL (FK to `users`, `ON DELETE RESTRICT`)
  - `name`: VARCHAR(100) NOT NULL
  - `type`: ENUM (`cash`, `bank`, `e_wallet`, `investment`, `savings`, `other`)
  - `account_number`: VARCHAR(50) NULLABLE
  - `institution_name`: VARCHAR(100) NULLABLE
  - `opening_balance`: DECIMAL(19,2) NOT NULL DEFAULT 0.00
  - `opening_date`: DATE NOT NULL
  - `include_in_total_assets`: BOOLEAN NOT NULL DEFAULT TRUE
  - `include_in_safe_to_spend`: BOOLEAN NOT NULL (default: TRUE kecuali `investment` = FALSE)
  - `status`: ENUM (`active`, `archived`) DEFAULT `active`
  - `notes`: TEXT NULLABLE
  - `created_at`, `updated_at`: TIMESTAMP
  - Index: `INDEX (user_id, status)`
- Enums: `AccountType`, `AccountStatus`.
- Actions:
  - `App\Actions\Accounts\CreateAccount`
  - `App\Actions\Accounts\UpdateAccount`
  - `App\Actions\Accounts\ArchiveAccount` (Akun tidak pernah di-hard-delete jika memiliki transaksi).
- Policy: `AccountPolicy` (`viewAny`, `view`, `create`, `update`, `archive`).

### 2. Langkah Pengerjaan
1. Buat migrasi, model `Account`, enums, dan factory lengkap dengan state untuk masing-masing `AccountType`.
2. Implementasikan Actions dengan validasi invarian domain (`opening_balance` ≥ 0, `opening_date` tidak boleh di masa depan).
3. Bangun FormRequest `StoreAccountRequest` dan `UpdateAccountRequest`.
4. Buat tampilan antarmuka akun mengacu pada Layar #8 [Monetra Finance App UI Showcase.png](file:///Users/akmalzulfikar/Testing/documents/Monetra%20Finance%20App%20UI%20Showcase.png) (kartu Bank, Dompet Digital, Tunai).

### 3. Kriteria Penerimaan & Verifikasi
- [x] Pengguna lain tidak dapat melihat atau mengubah akun milik pengguna lain (pengujian negatif IDOR menghasilkan 404).
- [x] Akun dengan tipe `investment` secara otomatis menyetel `include_in_safe_to_spend = false`.
- [x] Akun berstatus `archived` tidak muncul pada dropdown pembuatan transaksi baru, namun seluruh transaksi historis tetap utuh.

---

## Step 1.4 — Kategori Pemasukan & Pengeluaran ✅
- **Ukuran**: M (1 hari)
- **Status**: Selesai (Tested & Pint Verified)
- **Tujuan**: Menyediakan kategori transaksi sistem bawaan serta kategori buatan pengguna, lengkap dengan pemisahan jenis pengeluaran vs pemasukan.
- **Rujukan**: PRD FR-004, §10.3.

### 1. Spesifikasi Teknis & Schema
- Tabel: `categories`
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `user_id`: BIGINT UNSIGNED NULLABLE (NULL untuk kategori sistem bawaan)
  - `name`: VARCHAR(100) NOT NULL
  - `type`: ENUM (`income`, `expense`) NOT NULL
  - `icon`: VARCHAR(50) NOT NULL (Heroicon identifier)
  - `color`: VARCHAR(20) NOT NULL (Token warna Tailwind)
  - `is_system`: BOOLEAN NOT NULL DEFAULT FALSE
  - `status`: ENUM (`active`, `archived`) DEFAULT `active`
  - Index: `INDEX (user_id, type, status)`
- Seeder Sistem (`CategorySeeder`):
  - **Expense**: Makanan, Transportasi, Tempat Tinggal, Tagihan, Kesehatan, Belanja, Hiburan, Pendidikan, Keluarga, Biaya Layanan, Lainnya.
  - **Income**: Gaji, Freelance, Bisnis, Bunga/Dividen, Hadiah, Pengembalian Dana, Lainnya.

### 2. Kriteria Penerimaan & Verifikasi
- [x] Kategori sistem (`is_system = true`) tidak dapat diubah atau dihapus oleh pengguna.
- [x] Form transaksi memfilter pilihan kategori berdasarkan tipe transaksi (`income` vs `expense`).
- [x] Seeder bersifat idempoten (`firstOrCreate` / upsert) dan aman dijalankan berulang.

---

## Step 1.5 — Transaksi: Pemasukan & Pengeluaran ✅
- **Ukuran**: L (1–1.5 hari)
- **Status**: Selesai (Tested & Pint Verified)
- **Tujuan**: Alur pencatatan cepat transaksi harian pemasukan dan pengeluaran (< 30 detik) dengan pencatatan `audit_logs` otomatis.
- **Rujukan**: PRD FR-005, §10.4; Layar #3 & Layar #4 mockup.

### 1. Spesifikasi Teknis & Schema
- Tabel: `transactions`
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `user_id`: BIGINT UNSIGNED NOT NULL (FK `users`, `ON DELETE RESTRICT`)
  - `source_account_id`: BIGINT UNSIGNED NOT NULL (FK `accounts`)
  - `destination_account_id`: BIGINT UNSIGNED NULLABLE (Hanya untuk transfer)
  - `category_id`: BIGINT UNSIGNED NULLABLE (Wajib untuk income/expense, NULL untuk transfer)
  - `type`: ENUM (`income`, `expense`, `transfer`, `adjustment`)
  - `amount`: DECIMAL(19,2) NOT NULL (Selalu bernilai positif > 0)
  - `transaction_date`: DATE NOT NULL (Zona waktu pengguna)
  - `description`: VARCHAR(255) NULLABLE
  - `notes`: TEXT NULLABLE
  - `status`: ENUM (`posted`, `voided`) DEFAULT `posted`
  - `adjustment_direction`: ENUM (`increase`, `decrease`) NULLABLE (Hanya untuk adjustment)
  - `client_request_id`: VARCHAR(36) NULLABLE UNIQUE (Anti double submit)
  - `voided_at`: TIMESTAMP NULLABLE
  - `void_reason`: VARCHAR(255) NULLABLE
  - Index: `INDEX (user_id, transaction_date)`, `INDEX (source_account_id, transaction_date)`, `INDEX (category_id, transaction_date)`
- Tabel `audit_logs`:
  - `id`, `actor_id`, `action`, `entity_type`, `entity_id`, `request_id`, `metadata` (JSON tanpa informasi rahasia), `created_at`.
- Action: `App\Actions\Transactions\PostTransaction`.

### 2. Kriteria Penerimaan & Verifikasi
- [x] Nominal `amount ≤ 0` ditolak oleh validasi server.
- [x] Kategori bertipe `expense` tidak bisa digunakan untuk transaksi bertipe `income`, dan sebaliknya.
- [x] Event `TransactionPosted` berhasil memicu pencatatan baris ke tabel `audit_logs`.

---

## Step 1.6 — Transfer Antar-Akun Atomik & Pembatalan (Void) 🔐 ✅
- **Ukuran**: L (1.5 hari)
- **Status**: Selesai (Tested & Pint Verified)
- **Tujuan**: Memungkinkan pemindahan saldo antar-akun yang atomik (kedua sisi berhasil atau keduanya dibatalkan) serta pembatalan transaksi dengan jejak audit transparan.
- **Rujukan**: PRD FR-005, §9.3, §9.5, §13.

### 1. Spesifikasi Teknis
- Action `PostTransfer`:
  - Invarian: Akun sumber ≠ akun tujuan; kedua akun wajib berstatus aktif dan milik pengguna yang sama; dieksekusi dalam **satu** `DB::transaction`.
  - Transfer **tidak** memengaruhi total agregat kekayaan pengguna.
- Action `VoidTransaction`:
  - Invarian: Hanya transaksi berstatus `posted` yang dapat di-void; status berubah menjadi `voided` dengan catatan `void_reason` dan `voided_at`.
  - Menolak void jika transaksi tersebut tertaut pada pembayaran tagihan aktif (harus membatalkan pembayaran tagihan terlebih dahulu).

### 2. Kriteria Penerimaan & Verifikasi
- [x] Simulasi kegagalan database di tengah proses transfer memicu rollback penuh tanpa perubahan saldo parsial.
- [x] Transfer ditolak jika akun sumber sama dengan akun tujuan.
- [x] Void transaksi ganda (double void) ditolak secara idempoten.

---

## Step 1.7 — Mesin Saldo & Rekonsiliasi ✅
- **Ukuran**: M (1 hari)
- **Status**: Selesai (Tested & Pint Verified)
- **Tujuan**: Menghadirkan satu-satunya sumber kebenaran penghitungan saldo akun (`AccountBalanceService`) melalui agregasi SQL efisien, serta fitur penyesuaian rekonsiliasi manual.
- **Rujukan**: PRD FR-003, §9.2, OD-05.

### 1. Spesifikasi Teknis
- Service: `App\Services\Accounts\AccountBalanceService`:
  - Rumus saldo akun = `opening_balance` + Σ(income) − Σ(expense) + Σ(transfer_in) − Σ(transfer_out) + Σ(adjustment_increase) − Σ(adjustment_decrease) (hanya transaksi `posted`).
- Action: `App\Actions\Accounts\ReconcileAccount`:
  - Menghitung selisih antara saldo sistem terkini dan saldo riil yang diinput pengguna.
  - Membuat transaksi bertipe `adjustment` dengan `adjustment_direction` yang sesuai dan alasan rekonsiliasi wajib diisi.

### 2. Kriteria Penerimaan & Verifikasi
- [x] Transaksi berstatus `voided` tidak dihitung ke dalam saldo akun.
- [x] Eksekusi rekonsiliasi menghasilkan saldo akun yang tepat sama dengan nilai riil yang diinputkan pengguna.
- [x] Seluruh riwayat perubahan saldo dapat ditelusuri per transaksi tanpa modifikasi baris data masa lalu.
