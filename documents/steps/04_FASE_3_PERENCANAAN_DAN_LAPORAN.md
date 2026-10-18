# Fase 3: Perencanaan, Analitik & Administrasi
**Status**: ⬜ Dalam Persiapan · **Target Waktu**: 4–6 Hari Kerja

Fase 3 menghadirkan fitur nilai tambah utama (*core value proposition*) Monetra: anggaran, tagihan, tabungan, algoritma **Safe-to-Spend**, analitik visual, ekspor CSV aman, dan panel pemeliharaan Filament 5.

---

## Step 3.1 — Anggaran Bulanan & Peringatan Ambang
- **Ukuran**: L (1–1.5 hari)
- **Status**: ✅ Selesai
- **Tujuan**: Membantu pengguna menetapkan batas pengeluaran per kategori per bulan dengan pemantauan progres visual dan peringatan ambang (80% & 100%).
- **Rujukan**: PRD FR-007, §10.5; Layar #5 [Monetra Finance App UI Showcase.png](file:///Users/akmalzulfikar/Testing/documents/Monetra%20Finance%20App%20UI%20Showcase.png).

### 1. Spesifikasi Teknis & Schema
- Tabel: `budgets`
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `user_id`: BIGINT UNSIGNED NOT NULL (FK `users`)
  - `category_id`: BIGINT UNSIGNED NOT NULL (FK `categories`)
  - `period_start`: DATE NOT NULL (Hari pertama bulan bersangkutan)
  - `period_end`: DATE NOT NULL (Hari pertama bulan berikutnya — setengah terbuka `[start, end)`)
  - `limit_amount`: DECIMAL(19,2) NOT NULL (Wajib > 0)
  - `warning_threshold`: TINYINT UNSIGNED NOT NULL DEFAULT 80
  - `status`: ENUM (`active`, `archived`) DEFAULT `active`
  - Unique Constraint: `UNIQUE (user_id, category_id, period_start)` (Mencegah duplikasi anggaran aktif).
- Service: `App\Services\Budgets\BudgetUsageService`:
  - `usageFor(Budget $budget): BudgetUsage` (Σ expense `posted` kategori tersebut dalam rentang tanggal).
  - `getMonthlyBudgetOverview(User $user, ?string $month = null): MonthlyBudgetOverview` (agregasi batch bebas N+1).
- UI Mengacu Layar #5 Mockup:
  - Ringkasan: Total Anggaran, Terpakai, Sisa (dengan warna hijau jika surplus).
  - Daftar Kategori: Ikon, nama kategori, angka "Rp 650.000 / Rp 1.000.000", badge status teks eksplisit, dan progress bar.

### 2. Kriteria Penerimaan & Verifikasi
- [x] Transaksi di luar rentang tanggal tidak memengaruhi pemakaian anggaran.
- [x] Upaya membuat dua anggaran aktif untuk kategori dan bulan yang sama ditolak validasi.
- [x] Ambang peringatan (80% oranye, 100% merah) tampil dengan label teks eksplisit (tidak hanya mengandalkan warna).

---

## Step 3.2 — Tagihan & Pelacakan Jatuh Tempo
- **Ukuran**: L (1.5 hari)
- **Status**: ✅ Selesai
- **Tujuan**: Mencatat kewajiban rutin mendatang, status jatuh tempo (zona waktu pengguna), dan pembayaran yang tertaut ke **tepat satu** transaksi pengeluaran.
- **Rujukan**: PRD FR-008, §10.6; Layar #6 mockup.

### 1. Spesifikasi Teknis & Schema
- Tabel: `bills`
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `user_id`: BIGINT UNSIGNED NOT NULL (FK `users`)
  - `name`: VARCHAR(100) NOT NULL
  - `amount`: DECIMAL(19,2) NOT NULL
  - `due_date`: DATE NOT NULL
  - `category_id`: BIGINT UNSIGNED NULLABLE (FK `categories`)
  - `paid_transaction_id`: BIGINT UNSIGNED NULLABLE UNIQUE (FK `transactions`, `ON DELETE SET NULL`)
  - `status`: ENUM (`upcoming`, `paid`, `skipped`) DEFAULT `upcoming`
  - `notes`: TEXT NULLABLE
- Service `BillStatusService`:
  - Menghitung status turunan: Tagihan yang belum lunas dan `due_date < hari_ini` berstatus **Terlambat** (Overdue).
- Actions:
  - `PayBill`: Menandai tagihan lunas dengan membuat transaksi pengeluaran dan menautkan ID-nya secara atomik.
  - `CancelBillPayment`: Membatalkan status lunas dan me-void transaksi pengeluaran yang tertaut.
  - `SkipBill`: Melewati siklus tagihan tanpa pencatatan pengeluaran.

### 2. Kriteria Penerimaan & Verifikasi
- [x] Tagihan tidak dapat dibayar dua kali (`paid_transaction_id` bersifat unik).
- [x] Status terlambat dihitung dinamis berdasarkan tanggal hari ini di zona waktu pengguna.
- [x] Pembatalan pembayaran tagihan secara otomatis me-void transaksi terkait dan memulihkan saldo akun.

---

## Step 3.3 — Target Tabungan & Alokasi Dana
- **Ukuran**: L (1–1.5 hari)
- **Status**: ✅ Selesai
- **Tujuan**: Memfasilitasi penetapan target tabungan dengan ledger kontribusi/penarikan mandiri yang mencegah pengurangan ganda pada perhitungan Safe-to-Spend.
- **Rujukan**: PRD FR-009, §10.7; Layar #7 mockup.

### 1. Spesifikasi Teknis & Schema
- Tabel: `savings_goals`
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `user_id`: BIGINT UNSIGNED NOT NULL (FK `users`)
  - `name`: VARCHAR(100) NOT NULL (misal: "Dana Darurat", "Liburan")
  - `target_amount`: DECIMAL(19,2) NOT NULL
  - `target_date`: DATE NULLABLE
  - `status`: ENUM (`active`, `paused`, `completed`, `cancelled`) DEFAULT `active`
- Tabel: `savings_goal_entries` (Ledger):
  - `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  - `savings_goal_id`: BIGINT UNSIGNED NOT NULL (FK `savings_goals`, `ON DELETE CASCADE`)
  - `direction`: ENUM (`contribution`, `withdrawal`) NOT NULL
  - `amount`: DECIMAL(19,2) NOT NULL
  - `entry_date`: DATE NOT NULL
  - `linked_transaction_id`: BIGINT UNSIGNED NULLABLE (FK `transactions`)
- Service: `App\Services\SavingsGoals\SavingsGoalProgressService`:
  - `currentAmount(SavingsGoal $goal): Money` (Σ kontribusi − Σ penarikan).
  - `getOverview(User $user, ?SavingsGoalStatus $status = null): SavingsGoalsOverview` (batch agregasi bebas N+1).

### 2. Kriteria Penerimaan & Verifikasi
- [x] Progres target tabungan mencerminkan riwayat ledger secara matematis akurat.
- [x] Target yang sudah melampaui 100% menampilkan status surplus tanpa memicu crash atau kesalahan kalkulasi.

---

## Step 3.4 — Kalkulasi Safe-to-Spend Transparan 🔐
- **Ukuran**: L (1.5 hari)
- **Status**: ✅ Selesai
- **Tujuan**: Menyediakan angka estimasi dana yang aman dibelanjakan (*Safe-to-Spend*) hingga tanggal perencanaan (hari gajian) dengan transparansi formula dan tanpa pengurangan ganda.
- **Rujukan**: PRD FR-010, §5.4, ADR-006.

### 1. Formula & Logika Komputasi
```
Safe-to-Spend = Total Saldo Akun Spendable
              − Tagihan Belum Dibayar s/d Tanggal Rencana
              − Alokasi Target Belum Dieksekusi
```
- **Isolasi Deduksi (Anti Hitung Ganda)**:
  - Jika kontribusi tabungan sudah dilakukan via transfer riil (saldo spendable sudah berkurang), sistem **tidak boleh** mengurangkannya kembali dari sisa alokasi.
  - Tagihan yang sudah lunas (`status = paid`) **tidak dikurangkan lagi**.
- DTO: `App\Services\SafeToSpend\SafeToSpendResult`:
  - `spendableBalance`: Money
  - `unpaidBills`: Money
  - `pendingAllocations`: Money
  - `safeToSpend`: Money (bisa bernilai negatif jika tagihan > saldo)
  - `planningDate`: CarbonImmutable
  - `assumptions`: array (daftar asumsi berbahasa Indonesia)
  - `isEstimate`: bool

### 2. Kriteria Penerimaan & Verifikasi
- [x] Test unit komprehensif menguji kasus: saldo positif, nol, negatif, dan tanpa tagihan.
- [x] Akun bertipe investasi (yang tidak memiliki flag `include_in_safe_to_spend`) terbukti tidak masuk dalam saldo spendable.
- [x] Layar menampilkan rincian komponen perhitungan dan disclaimer jujur: *"Estimasi perencanaan, bukan jaminan atau nasihat keuangan formal."*

---

## Step 3.5 — Laporan Arus Kas & Analitik Kategori
- **Ukuran**: L (1 hari)
- **Status**: ✅ Selesai
- **Tujuan**: Visualisasi tren pemasukan vs pengeluaran dan rincian per kategori mengacu pada Layar #8 [Monetra Finance App UI Showcase.png](file:///Users/akmalzulfikar/Testing/documents/Monetra%20Finance%20App%20UI%20Showcase.png).
- **Rujukan**: PRD FR-011, §15; Layar #8 mockup.

### 1. Spesifikasi Teknis
- Services:
  - `App\Services\Reporting\FinancialReportService`: Agregasi arus kas bulanan (6 bulan tren) dan kategori dengan mengecualikan transfer dan adjustment.
  - Ringkasan kekayaan bersih (Net Worth) per jenis instrumen rekening.
- Tampilan Visual (Layar #8 Mockup):
  - Menggunakan grafik bar CSS native yang ringan dan aksesibel (tanpa library JavaScript pihak ketiga di MVP).
  - Menyediakan tabel data alternatif yang ramah screen-reader dan presisi.

### 2. Kriteria Penerimaan & Verifikasi
- [x] Penjumlahan angka pada laporan arus kas cocok 100% dengan total transaksi di database.
- [x] Periode tanpa transaksi menampilkan empty state informatif tanpa grafik rusak.
- [x] Transaksi internal transfer dan penyesuaian saldo (adjustment) tidak masuk dalam arus kas operasional.

---

## Step 3.6 — Ekspor CSV Transaksi Aman 🔐
- **Ukuran**: M (1 hari)
- **Status**: ✅ Selesai
- **Tujuan**: Memungkinkan pengguna mengunduh seluruh atau sebagian catatan transaksi ke dalam format CSV dengan perlindungan ketat terhadap serangan *CSV Formula Injection*.
- **Rujukan**: PRD FR-012, §12.5.

### 1. Spesifikasi Teknis
- Service: `App\Services\Exports\TransactionCsvExporter`:
  - Menggunakan stream response (`Symfony\Component\HttpFoundation\StreamedResponse`) untuk efisiensi memori.
  - Kolom ekspor: `id`, `tanggal`, `jenis`, `kategori`, `akun_sumber`, `akun_tujuan`, `nominal`, `status`, `deskripsi`, `catatan`.
- Sanitasi Formula Injection:
  - Setiap sel teks yang diawali karakter `=`, `+`, `-`, `@`, `\t`, atau `\r` wajib diberi tanda kutip tunggal (`'`) di depannya agar spreadsheet (Excel/LibreOffice) tidak mengeksekusinya sebagai rumus.
- Otorisasi: Pengguna hanya dapat mengekspor data miliknya sendiri; rute ekspor dilindungi rate limit.

### 2. Kriteria Penerimaan & Verifikasi
- [x] Test unit verifikasi sanitasi terhadap formula berbahaya (misal: `=cmd|' /C calc'!A0` tersanitasi aman).
- [x] File CSV hasil unduhan dapat dibuka dengan benar di Microsoft Excel dan Google Sheets dengan encoding UTF-8 BOM.

---

## Step 3.7 — Panel Filament 5 Terproteksi 🔐
- **Ukuran**: L (1.5 hari)
- **Status**: ✅ Selesai
- **Tujuan**: Panel administrasi dan pemeliharaan data di `/admin` yang terikat ketat pada aturan domain yang sama tanpa jalur pintas ilegal.
- **Rujukan**: PRD FR-016, §11.5, ADR-005.

### 1. Spesifikasi Teknis
- Paket: `filament/filament` v5.x.
- Hak Akses:
  - Model `User` mengimplementasikan `FilamentUser`.
  - Metode `canAccessPanel()` hanya mengembalikan `true` untuk ID pemilik akun privat (`is_admin = true`). Pengguna lain menerima HTTP 403.
- Batasan Resource Panel:
  - Transaksi: Hanya dapat dilihat (*View*) dan dibatalkan (*Void*) melalui `VoidTransaction` Action. Pembuatan atau penghapusan permanen transaksi langsung dinonaktifkan.
  - Akun & Kategori: Dibatasi view dan arsip melalui Action domain (`ArchiveAccount`, `ArchiveCategory`).
  - Audit Logs: Read-only viewer.

### 2. Kriteria Penerimaan & Verifikasi
- [x] Akses ke `/admin` oleh pengguna tidak sah ditolak dengan status 403 Forbidden.
- [x] Aksi void dari Filament memicu event dan audit log yang identik dengan aksi dari Blade UI.
- [x] Aksi arsip akun dan kategori memanggil Action domain tanpa menghapus transaksi historis.
