---
title: Monetra — Rencana Implementasi Teknis & Fase Pengembangan
version: 1.0.0
tanggal: 2026-10-17
status: Baseline pengembangan — dieksekusi bertahap per step
pemilik: Produk & Rekayasa Perangkat Lunak
dokumen_rujukan: documents/Monetra_PRD_Bahasa_Indonesia.md
platform: Laravel 13 + Blade + MariaDB 11.8 + Filament 5 + Tailwind CSS 4
---

# Monetra — Rencana Implementasi Teknis & Fase Pengembangan

> **Aturan utama:** `documents/Monetra_PRD_Bahasa_Indonesia.md` adalah sumber kebenaran produk.
> Dokumen ini menerjemahkan PRD menjadi tahapan rekayasa yang dapat dieksekusi. Jika terjadi
> konflik antara dokumen ini dan PRD, **PRD yang berlaku** — perbarui dokumen ini melalui perubahan
> yang dapat ditinjau.
>
> **Peran dokumen:** panduan kerja harian (manual maupun berbantuan AI): urutan fase, scope per
> step, kriteria penerimaan terukur, gate kualitas, standar kode enterprise, dan peta layanan
> (service layer) yang menjadi tulang punggung aplikasi.

## Daftar Isi

1. [Ringkasan & Kondisi Proyek Saat Ini](#1-ringkasan--kondisi-proyek-saat-ini)
2. [Keputusan yang Dikunci](#2-keputusan-yang-dikunci)
3. [Arsitektur Aplikasi](#3-arsitektur-aplikasi)
4. [Standar Engineering (Clean Code Enterprise)](#4-standar-engineering-clean-code-enterprise)
5. [Roadmap Fase & Step](#5-roadmap-fase--step)
6. [Matriks Ketertelakan Requirement → Step](#6-matriks-ketertelakan-requirement--step)
7. [Definition of Done & Gate Kualitas](#7-definition-of-done--gate-kualitas)
8. [Perlu Konfirmasi Pemilik](#8-perlu-konfirmasi-pemilik)
9. [Risiko & Mitigasi Implementasi](#9-risiko--mitigasi-implementasi)
10. [Milestone](#10-milestone)

---

## 1. Ringkasan & Kondisi Proyek Saat Ini

### 1.1 Ringkasan produk

Monetra adalah aplikasi web pengelolaan keuangan pribadi mobile-first untuk satu pengguna privat.
Tulang punggungnya adalah **pencatatan keuangan yang akurat dan dapat diaudit**: akun dan saldo
awal, pemasukan/pengeluaran, transfer antar-akun yang atomik, anggaran, tagihan, target tabungan,
perhitungan **Safe-to-Spend** yang transparan, laporan, ekspor CSV, dan panel pemeliharaan
Filament yang tunduk pada aturan domain yang sama. Ruang lingkup MVP = **P0** pada PRD §6.1.

Prinsip yang tidak bisa ditawar (PRD §5): akurasi sebelum tampilan, mobile-first, perhitungan
transparan, tidak ada kehilangan data diam-diam, aman secara bawaan, server adalah sumber
kebenaran, arsitektur sederhana (monolit modular — tanpa microservices prematur).

### 1.2 Stack terverifikasi (hasil pemeriksaan lingkungan)

| Komponen | Versi terpasang | Status | Catatan |
|---|---|---|---|
| PHP | 8.4.1 (NTS) | ✅ | Ekstensi `bcmath`, `intl`, `gd`, `pdo_mysql`, `mysqli` tersedia |
| Laravel Framework | 13.37.0 | ✅ | Kompatibel target PRD §11.1 |
| Database server | MariaDB 11.8.2 (Homebrew, macOS) | ⚠️ | Kompatibel dengan MySQL 8.0+ yang disyaratkan PRD; dicatat pada ADR-001 |
| Node.js / npm | v23.11.0 / 10.9.2 | ✅ | Untuk Vite 8 |
| Tailwind CSS | 4.x (via `@tailwindcss/vite`) | ✅ | OD-03: satu-satunya pendekatan CSS; jangan campur Bootstrap |
| Vite | 8.x | ✅ | Build asset Blade |
| PHPUnit | 12.5.40 | ✅ | OD-04: PHPUnit sebagai gaya pengujian utama (bukan Pest) |
| laravel/pao | 1.1.5 | ✅ | Output pengujian ramah agen (`php artisan test --compact`) |
| Laravel Pint | 1.32.1 | ✅ | Formatter wajib sebelum commit |
| Filament | v5.10.2 (belum dipasang) | 🔜 | Kompatibel dengan Laravel 13 (`illuminate ^11.28\|^12.0\|^13.0`) |
| Laravel Breeze | v2.4.2 (belum dipasang) | 🔜 | Kompatibel dengan Laravel 13 (`illuminate ^13.0`) |

### 1.3 Temuan kondisi saat ini (wajib diselesaikan di Fase 0)

| # | Temuan | Dampak | Rencana penanganan |
|---|---|---|---|
| T-1 | Database `mymoney` **belum ada**; koneksi `php artisan migrate:status` gagal | **Blocker**: session/cache/queue memakai driver `database` | Step 0.1 — buat database + user aplikasi khusus |
| T-2 | Kredensial `root` ditolak via TCP (`ERROR 1698`) — root MariaDB memakai autentikasi `unix_socket` | **Blocker**: `.env` tidak dapat terhubung | Step 0.1 — user aplikasi `monetra` (least privilege, hanya schema Monetra) |
| T-3 | User OS `akmalzulfikar` terverifikasi memiliki `ALL PRIVILEGES ... WITH GRANT OPTION` | Peluang, bukan masalah | Dipakai sekali untuk provisioning database/user di Step 0.1 |
| T-4 | Repositori Git **belum diinisialisasi** | Tidak ada jejak perubahan, tidak ada rollback | Step 0.2 — `git init` + baseline commit |
| T-5 | `.env.example` masih memakai `DB_CONNECTION=sqlite` | Onboarding tidak akurat | Step 0.1 — perbarui template MySQL/MariaDB |
| T-6 | Seluruh aplikasi masih skeleton bawaan (1 route `/welcome`) | — | Dieksekusi bertahap sesuai roadmap §5 |

### 1.4 Prinsip pelaksanaan

1. **Vertical slice, satu step satu waktu** (PRD §22.2): migration → model → domain/action/service →
   validasi → otorisasi → UI → pengujian → dokumentasi. Jangan membangun semua tabel sekaligus.
2. **Milestone pertama bukan dashboard yang indah** (Lampiran B PRD): fondasi keuangan yang benar
   dan teruji lebih dulu; dashboard dibangun di atas fondasi yang sudah lulus rekonsiliasi.
3. **Semua jalur masuk memakai operasi domain yang sama** (PRD §11.2): Blade, Filament, dan command
   memanggil Action/Service yang sama. Tidak ada logika keuangan di controller, Blade, callback
   Filament, observer, atau JavaScript.

---

## 2. Keputusan yang Dikunci

Keputusan diambil dari rekomendasi bawaan PRD §21 dan diperkuat dengan temuan lingkungan §1.2–1.3.
Keputusan yang masih membutuhkan konfirmasi pemilik ditandai **[Perlu Konfirmasi]** dan dirangkum
di §8.

### 2.1 Keputusan produk (OD-01 … OD-12)

| ID | Keputusan | Nilai yang ditetapkan | Rujukan |
|---|---|---|---|
| OD-01 | Model aplikasi | **Privat**: pendaftaran publik **dinonaktifkan**; akun pemilik dibuat via command provisioning + seeder dev | PRD §21, FR-001 |
| OD-02 | Target deployment | Hosting terkelola/VPS Linux dengan HTTPS & backup; **finalisasi ditunda ke Fase 4** (4.4) | PRD §18 |
| OD-03 | Pendekatan CSS | **Tailwind CSS 4** (sudah terpasang); dilarang mencampur Bootstrap | PRD §11.1 |
| OD-04 | Framework pengujian | **PHPUnit 12** (terpasang) + `laravel/pao` | PRD §11.1 |
| OD-05 | Model saldo akun | **Dihitung** dari `opening_balance` + dampak transaksi `posted`; tanpa saldo cache di MVP | PRD §9.2, §10.9 |
| OD-06 | Kartu kredit/utang | Ditunda; akun kewajiban masuk fase kekayaan bersih (P1). Utang tidak pernah direpresentasikan sebagai aset positif | PRD FR-003 |
| OD-07 | Transaksi berulang | **P1** — tidak dibangun di roadmap MVP ini | PRD §6.2 |
| OD-08 | Pengingat email/push | Ditunda; MVP memakai indikator jatuh tempo dalam aplikasi | PRD §6.2 |
| OD-09 | Snapshot kekayaan bersih | **P1** — model data disiapkan agar tidak menghalangi | PRD FR-013 |
| OD-10 | Kebijakan penghapusan data | Batalkan/arsipkan catatan keuangan (void/archive); hard delete hanya untuk konfigurasi tanpa referensi; soft delete bukan mekanisme utama transaksi | PRD §9.5, §10.9 |
| OD-11 | Konfigurasi tanggal gajian | Mendukung **dua-duanya**: `payday_day` bulanan (1–31) dan `next_payday_date` eksplisit; definisi bulan pendek: hari gajian > jumlah hari bulan → **hari terakhir bulan** (dokumentasi + unit test) | PRD FR-002, §9.6 |
| OD-12 | Mode offline | Tidak ada untuk MVP; sediakan keadaan error yang eksplisit + ekspor/backup | PRD §21 |

### 2.2 Keputusan teknis (DT-01 … DT-14)

| ID | Keputusan | Detail | Alasan / Rujukan |
|---|---|---|---|
| DT-01 | Autentikasi | **Laravel Breeze (Blade) dikustom** ke design token Monetra; registrasi publik dihapus; throttle login; reset kata sandi via email (mailer `log` di lokal) | FR-001; starter kit resmi, lebih aman daripada menulis auth manual |
| DT-02 | Design system | Token Tailwind via `@theme` (CSS variables): warna, radius, shadow, tipografi; komponen Blade `x-ui.*` sebagai satu-satunya sumber gaya | §7.3; mencegah duplikasi gaya antarpages |
| DT-03 | Database pengujian | **MySQL/MariaDB `mymoney_testing`** (bukan SQLite) — fidelity `DECIMAL(19,2)`, strict mode, dan index mendekati produksi | PRD §18 strategi lingkungan; **[Perlu Konfirmasi]** |
| DT-04 | Representasi uang | `DECIMAL(19,2)` di database; **value object `Money`** immutable di lapisan domain (berbasis `brick/math` yang sudah tersedia transitif — dideklarasikan eksplisit); **dilarang** float/double dalam jalur uang mana pun | PRD §9.1, §22.5; **[Perlu Konfirmasi]** |
| DT-05 | Skema `users` | Kolom preferensi (`timezone`, `locale`, `currency`, `payday_day`, `next_payday_date`) **ditambahkan ke migrasi awal** — aman karena belum ada data produksi | PRD §10.1; hindari migration churn pra-produksi |
| DT-06 | Siklus hidup transaksi | Status `posted` / `voided` + `voided_at` + `void_reason`; tanpa soft delete pada `transactions`; jejak audit via `audit_logs` | PRD §9.5 |
| DT-07 | Saldo akun | Dihitung on-the-fly via query agregat (`AccountBalanceService`); optimasi (cache/ledger) hanya setelah pengukuran | OD-05, PRD §14 |
| DT-08 | Penyesuaian rekonsiliasi | Transaksi `type=adjustment` + kolom baru `adjustment_direction` (`increase`/`decrease`), wajib untuk adjustment, `NULL` untuk tipe lain; dibuat **hanya** oleh `ReconcileAccount` | FR-003 aturan rekonsiliasi |
| DT-09 | Anti double-submit | Kolom `client_request_id` (UUID, unique, nullable) pada `transactions`; form mengirim UUID sekali pakai; duplikat mengembalikan hasil yang sama (idempoten) | PRD §13, §12.5 |
| DT-10 | Panel admin | Filament 5 di path `/admin`; akses hanya pemilik (`canAccessPanel` + gate eksplisit); resource terbatas (list/view + aksi void via Action domain); bulk delete dinonaktifkan | FR-016, PRD §11.5 |
| DT-11 | Grafik laporan | Tanpa library chart di MVP: bar chart HTML/CSS + **tabel alternatif aksesibel**; tambah library hanya bila terbukti perlu | FR-011, §15; hindari dependency yang tidak perlu |
| DT-12 | Bahasa | UI & pesan validasi **Bahasa Indonesia**; nama kelas/variabel/kode **Bahasa Inggris** (konvensi Laravel); format IDR | §7.3, §15 |
| DT-13 | Waktu & periode | Simpan timestamp UTC; `transaction_date` sebagai `DATE` lokal pengguna; semua rentang **setengah terbuka** `[start, end)` lewat value object `PlanningPeriod` | PRD §9.6, §15 |
| DT-14 | Audit trail | Tabel `audit_logs` (aktor, aksi, entitas, timestamp, request_id, metadata aman) — ditulis via event listener; **tidak pernah** mencatat kata sandi/token/payload sensitif | PRD §10.8, §12.3 |

### 2.3 Rencana dependency (butuh persetujuan eksplisit sebelum instalasi)

Sesuai `AGENTS.md` ("Do not change the application's dependencies without approval"):

| Paket | Usulan versi | Jenis | Tujuan | Rujukan PRD |
|---|---|---|---|---|
| `laravel/breeze` | `^2.4` | dev | Scaffolding login/logout/reset/profil yang dikustom | FR-001 |
| `filament/filament` | `^5.10` | prod | Panel pemeliharaan terproteksi | FR-016 |
| `brick/math` | `^1.0` | prod | Fondasi presisi desimal `Money` VO (sudah terpasang transitif — dijadikan dependensi eksplisit) | §9.1 |
| `larastan/larastan` | versi terbaru kompatibel | dev | Analisis statis (opsional, direkomendasikan PRD §11.1) | §11.1 |

---

## 3. Arsitektur Aplikasi

### 3.1 Pola arsitektur: Monolith Modular (PRD §11.2)

```
┌─────────────────────────────────────────────────────────────────────┐
│ HTTP Layer          Route → Middleware (auth/throttle) → FormRequest│
│ (thin)              → Controller (≤ 10 baris aksi) → Policy          │
├─────────────────────────────────────────────────────────────────────┤
│ Application Layer   Actions  = perintah tulis (write/command)        │
│                     Services = kalkulasi & query (read)              │
├─────────────────────────────────────────────────────────────────────┤
│ Domain Layer        Money VO, PlanningPeriod, PaydaySchedule, Enums  │
│ (framework-light)   Exceptions domain, aturan invarian keuangan      │
├─────────────────────────────────────────────────────────────────────┤
│ Persistence Layer   Eloquent Models + scopes + casts + migrations    │
├─────────────────────────────────────────────────────────────────────┤
│ Presentation Layer  Blade (layouts, components x-ui.*), Alpine.js    │
├─────────────────────────────────────────────────────────────────────┤
│ Admin Layer         Filament 5 — memanggil Action/Service yang sama  │
├─────────────────────────────────────────────────────────────────────┤
│ Cross-cutting       Events + AuditListener, Exceptions, Logging,     │
│                     Policies, Casts, Helpers                         │
└─────────────────────────────────────────────────────────────────────┘
```

**Aturan aliran data (tidak boleh dilanggar):**

| Lapisan | Boleh melakukan | Dilarang |
|---|---|---|
| Controller | Otorisasi ringan, memanggil Action/Service, mengembalikan view/redirect | Query keuangan langsung, perhitungan saldo, `DB::transaction` |
| FormRequest | Validasi input + `authorize()` | Logika bisnis |
| Action | Orkestrasi penulisan: validasi domain, `DB::transaction`, persist, event, audit | Query baca berat, format tampilan |
| Service | Query agregat & kalkulasi murni; mengembalikan DTO/value object | Menulis database (kecuali catatan teknis terdokumentasi) |
| Domain | Aturan murni: uang, periode, jadwal gajian, invarian | Akses HTTP, session, view |
| Model | Relasi, scope, cast, accessor ringan | Perhitungan keuangan lintas-entitas, efek samping tersembunyi |
| Blade | Menampilkan data yang sudah dihitung; format via helper | Query database, keputusan bisnis |
| Filament | Kembali ke Action/Service yang sama | Callback yang menulis langsung ke model keuangan |

### 3.2 Struktur direktori target (`app/`)

```
app/
├── Actions/                          # Satu operasi tulis = satu kelas
│   ├── Accounts/                     # CreateAccount, UpdateAccount, ArchiveAccount, ReconcileAccount
│   ├── Transactions/                 # PostTransaction, PostTransfer, VoidTransaction
│   ├── Budgets/                      # CreateBudget, UpdateBudget, ArchiveBudget
│   ├── Bills/                        # CreateBill, UpdateBill, PayBill, CancelBillPayment, SkipBill
│   └── SavingsGoals/                 # CreateSavingsGoal, UpdateSavingsGoal, RecordContribution, RecordWithdrawal
├── Domain/                           # Aturan bisnis murni, framework-agnostic
│   ├── Money/
│   │   ├── Money.php                 # Value object immutable (brick/math)
│   │   ├── MoneyFormatter.php        # Format id-ID (NumberFormatter intl)
│   │   └── RoundingPolicy.php        # Satu-satunya tempat aturan pembulatan
│   └── Finance/
│       ├── PlanningPeriod.php        # Rentang setengah terbuka [start, end)
│       └── PaydaySchedule.php        # Tanggal gajian berikutnya (OD-11)
├── Enums/                            # TransactionType, TransactionStatus, AccountType,
│                                     # AccountStatus, CategoryType, BudgetStatus, BillStatus,
│                                     # BillScope, SavingsGoalStatus, SavingsEntryDirection,
│                                     # AdjustmentDirection
├── Exceptions/                       # Base DomainException + exception domain spesifik
├── Http/
│   ├── Controllers/                  # Tipis; satu controller per resource
│   ├── Requests/                     # FormRequest per aksi (Store/Update/…)
│   └── Middleware/                   # Security headers, request-id (korelasi log)
├── Models/                           # User, Account, Category, Transaction, Budget, Bill,
│                                     # SavingsGoal, SavingsGoalEntry, AuditLog
├── Policies/                         # Satu policy per model keuangan (ownership)
├── Providers/                        # AppServiceProvider, EventServiceProvider (jika perlu)
├── Services/                         # Mesin kalkulasi & query (read)
│   ├── Accounts/AccountBalanceService.php
│   ├── Budgets/BudgetUsageService.php
│   ├── Bills/BillStatusService.php
│   ├── SavingsGoals/SavingsGoalProgressService.php
│   ├── SafeToSpend/
│   │   ├── SafeToSpendService.php
│   │   └── SafeToSpendResult.php     # DTO readonly + rincian komponen
│   ├── Dashboard/DashboardMetricsService.php
│   ├── Reporting/CashFlowReportService.php
│   ├── Reporting/CategoryBreakdownService.php
│   └── Exports/TransactionCsvExporter.php
├── Support/                          # AuditLogger, AmountSanitizer (CSV), Clock
└── Filament/                         # Resources, Pages, Widgets (Fase 3, step 3.7)
```

> Folder dibuat **saat tanggung jawabnya jelas** (PRD §11.3) — bukan sekali jadi.

### 3.3 Alur request contoh (POST /transactions)

```
1. Route (auth + throttle) → TransactionController@store
2. StoreTransactionRequest → validasi input (amount, date, akun, kategori) + authorize()
3. Controller → PostTransaction::handle($user, $request->toTransactionData())
4. Action:
   a. Money::of($amount) → validasi > 0 dan batas wajar
   b. Verifikasi kepemilikan akun/kategori (kembali dari DB, bukan dari ID request)
   c. DB::transaction → Transaction::create(...) [status=posted]
   d. event(new TransactionPosted($transaction))
5. Listener AuditLogger → audit_logs (actor, action, entity, request_id)
6. Redirect ke daftar aktivitas + flash sukses (Bahasa Indonesia)
```

### 3.4 Service Layer & Action Layer — inti rekayasa aplikasi

#### 3.4.1 Service (baca/kalkulasi — tanpa efek samping)

| Service | Tanggung jawab | Metode kunci (sketsa) |
|---|---|---|
| `AccountBalanceService` | Saldo satu akun & agregat (spendable, total aset) dari `opening_balance` + transaksi `posted` | `balanceOf(Account): Money` · `spendableTotal(User): Money` · `assetsTotal(User): Money` |
| `BudgetUsageService` | Pemakaian anggaran = Σ expense `posted` yang cocok kategori & periode | `usageFor(Budget): BudgetUsage` · `usageForPeriod(User, PlanningPeriod): Collection` |
| `BillStatusService` | Status turunan tagihan (upcoming/overdue) + tagihan mendatang N hari (zona waktu pengguna) | `derivedStatus(Bill): BillStatus` · `upcoming(User, PlanningPeriod): Collection` |
| `SavingsGoalProgressService` | Progres target = Σ kontribusi − Σ penarikan (ledger) | `progressOf(SavingsGoal): SavingsProgress` |
| `SafeToSpendService` | Kalkulasi Safe-to-Spend + rincian komponen (transparan, anti hitung ganda) | `calculate(User, ?CarbonImmutable $planningDate): SafeToSpendResult` |
| `DashboardMetricsService` | Komposisi seluruh metrik dashboard untuk periode terpilih | `for(User, PlanningPeriod): DashboardMetrics` |
| `CashFlowReportService` | Arus kas: pemasukan/pengeluaran/neto memenuhi syarat (transfer & adjustment dikecualikan) | `forPeriod(User, PlanningPeriod): CashFlowReport` |
| `CategoryBreakdownService` | Rincian pengeluaran per kategori + perbandingan periode sebelumnya | `expenseByCategory(User, PlanningPeriod): Collection` |
| `TransactionCsvExporter` | Stream CSV aman (escape formula injection) sesuai filter & otorisasi | `stream(User, TransactionFilter): StreamedResponse` |

**Sketsa kontrak service (contoh — `SafeToSpendService`):**

```php
final class SafeToSpendService
{
    public function __construct(
        private readonly AccountBalanceService $balances,
        private readonly BillStatusService $bills,
        private readonly PaydaySchedule $paydaySchedule,
    ) {}

    public function calculate(User $user, ?CarbonImmutable $planningDate = null): SafeToSpendResult
    {
        // 1. dasar  = total saldo akun include_in_safe_to_spend
        // 2. kurang = kewajiban belum dibayar s.d. planningDate (sekali saja)
        // 3. kurang = alokasi target BELUM dieksekusi (yang sudah ditransfer tidak dikurangi lagi)
        // 4. hasil  = SafeToSpendResult (setiap komponen + asumsi + tanggal rencana)
    }
}
```

**Sketsa DTO hasil (immutable, tanpa logika):**

```php
final readonly class SafeToSpendResult
{
    public function __construct(
        public Money $spendableBalance,
        public Money $unpaidBills,
        public Money $pendingAllocations,
        public Money $safeToSpend,
        public CarbonImmutable $planningDate,
        public bool $isEstimate,           // true bila data belum lengkap
        public array $assumptions,         // daftar asumsi berbahasa Indonesia utk UI
    ) {}
}
```

#### 3.4.2 Action (tulis — satu operasi = satu kelas, `handle()` tunggal)

| Action | Invarian yang ditegakkan |
|---|---|
| `PostTransaction` (income/expense) | `amount > 0`; kategori wajib & tipenya cocok; akun milik user; status `posted`; event + audit |
| `PostTransfer` | Akun sumber ≠ tujuan; keduanya milik user; **satu** `DB::transaction`; tanpa kategori; total agregat tidak berubah |
| `VoidTransaction` | Hanya `posted → voided`; idempoten-tolak ganda; menolak void transaksi tertaut pembayaran tagihan aktif; `voided_at` + alasan + audit |
| `ReconcileAccount` | Membuat `adjustment` + `adjustment_direction` + alasan; hanya dari saldo sistem terkini; audit |
| `PayBill` | Menautkan **tepat satu** transaksi expense (`paid_transaction_id` unique); menolak bayar ganda; audit |
| `CancelBillPayment` | Void transaksi tertaut + mengembalikan status tagihan; audit |
| `RecordContribution` / `RecordWithdrawal` | Ledger `savings_goal_entries`; opsional menaut transfer; mencegah hitung ganda Safe-to-Spend |
| `CreateBudget` / `UpdateBudget` | `limit_amount > 0`; kategori pengeluaran; tidak ada anggaran aktif ganda (user+kategori+periode) |
| `CreateAccount` / `ArchiveAccount` | `opening_balance` valid; arsip ≠ hapus; transaksi historis tetap utuh |

**Kontrak Action (contoh — `PostTransfer`):**

```php
final class PostTransfer
{
    public function handle(User $user, TransferData $data): Transaction
    {
        return DB::transaction(function () use ($user, $data) {
            // 1. Money::of($data->amount) > 0
            // 2. akun sumber & tujuan: where('user_id', $user->id)->findOrFail() → 404 jika bukan miliknya
            // 3. guard: source !== destination
            // 4. Transaction::create(type: Transfer, ...)
            // 5. event(new TransactionPosted($transaction))
        });
    }
}
```

#### 3.4.3 Aturan Action vs Service (kontrak disiplin)

1. **Action hanya untuk tulis.** Selalu membungkus operasi multi-baris dalam `DB::transaction`.
2. **Service tidak menulis.** Jika suatu service tampak perlu menulis, itu tanda harus menjadi Action.
3. **DTO antar-lapisan.** Service/Action menerima dan mengembalikan DTO atau Model — bukan `array`
   mentah, bukan `Request`.
4. **Controller tidak tahu Eloquent untuk operasi domain.** Ia memanggil Action/Service.
5. **Interface/kontrak hanya dibuat bila ada ≥ 2 implementasi nyata atau kebutuhan seam pengujian**
   yang jelas — hindari abstraksi spekulatif (`AGENTS.md`, PRD §22.10).

### 3.5 Value Object & DTO

| Kelas | Jenis | Invarian |
|---|---|---|
| `Money` | Value object | Immutable; desimal presisi 2; `> 0` untuk nominal transaksi; pembulatan HALF_UP terpusat di `RoundingPolicy` |
| `PlanningPeriod` | Value object | Setengah terbuka `[start, end)`; `start < end`; helper `currentMonth(User)` (zona waktu pengguna) |
| `PaydaySchedule` | Domain service | `nextPayday(User, from): CarbonImmutable` — `payday_day` > hari bulan → hari terakhir bulan (OD-11) |
| `TransactionData`, `TransferData`, `BillData`, … | DTO input Action | Readonly; sudah tervalidasi oleh FormRequest; angka mentah sebagai string desimal |

**Sketsa `Money`:**

```php
final readonly class Money
{
    private function __construct(private BigDecimal $amount) {}

    public static function of(string|int $decimal): self;      // "12500.00"
    public static function zero(): self;
    public function add(self $other): self;
    public function subtract(self $other): self;
    public function isZero(): bool;
    public function isNegative(): bool;
    public function greaterThan(self $other): bool;
    public function toDecimalString(): string;                  // untuk kolom DECIMAL(19,2)
    public function formatted(): string;                        // "Rp12.450.000" (intl, id-ID)
}
```

> `Money` **tidak boleh** memiliki `toFloat()`. Cast `MoneyCast` di model menangani konversi
> string ↔ `Money` untuk kolom DECIMAL.

### 3.6 Event & Listener (jejak audit)

| Event | Dipicu oleh | Listener |
|---|---|---|
| `TransactionPosted` | `PostTransaction`, `PostTransfer`, `ReconcileAccount`, `PayBill` | `WriteAuditLog` |
| `TransactionVoided` | `VoidTransaction`, `CancelBillPayment` | `WriteAuditLog` |
| `AccountArchived` | `ArchiveAccount` | `WriteAuditLog` |
| `UserPreferencesUpdated` | Profil | `WriteAuditLog` (metadata aman saja) |

`audit_logs`: `actor_id`, `action` (string terpusat di enum `AuditAction`), `entity_type`,
`entity_id`, `request_id`, `metadata` (JSON aman — tanpa data sensitif), `created_at`.
**Dilarang** mencatat kata sandi, token, cookie, atau payload transaksi penuh (PRD §12.3).

### 3.7 Otorisasi (Policies & kepemilikan)

- Satu policy per model keuangan: `AccountPolicy`, `CategoryPolicy`, `TransactionPolicy`,
  `BudgetPolicy`, `BillPolicy`, `SavingsGoalPolicy`.
- Aturan emas: **setiap query resource milik pengguna selalu diawali `where('user_id', $user->id)`**
  atau route-model-binding yang sudah di-scope; ID dari browser tidak pernah dipercaya (IDOR).
- `authorizeResource()` pada controller + `$this->authorize()` untuk aksi khusus (void, archive, pay).
- Pengujian negatif wajib: user lain → 404/403 untuk **setiap** endpoint resource (PRD §16.2).
- Filament memakai policy yang sama — tidak ada jalur otorisasi kedua (PRD §11.5).

---

## 4. Standar Engineering (Clean Code Enterprise)

Standar ini berlaku wajib untuk seluruh kontribusi (manusia maupun agen). Pelanggaran standar =
perubahan tidak boleh di-merge.

### 4.1 Prinsip dasar

1. **SOLID** dengan penerapan pragmatis; **DRY** untuk pengetahuan bisnis (bukan untuk kebetulan
   sintaks); **KISS**; **YAGNI** — jangan bangun untuk kebutuhan hipotetis.
2. **Explicit over implicit**: tidak ada magic string; gunakan enum, konstanta, dan tipe.
3. **Fail fast**: validasi di batas sistem (FormRequest), invarian di domain (exception), data
   korup tidak pernah sampai ke database.
4. **Satu kelas = satu tanggung jawab**; jika deskripsi kelas membutuhkan kata "dan", pecah.
5. **Aturan PRD §22 berlaku mutlak**: tidak ada perhitungan keuangan di Blade/JS/controller/
   callback Filament; tidak ada float untuk uang; tidak ada mass assignment tanpa kebijakan;
   jangan menonaktifkan CSRF/otorisasi demi tes.

### 4.2 Konvensi PHP & Laravel

| Aturan | Detail |
|---|---|
| `declare(strict_types=1);` | Wajib di setiap file `app/`, `tests/`, `database/` |
| Kelas | `final` secara bawaan; `final readonly` untuk DTO/value object |
| Properti | Constructor property promotion; `readonly` bila tidak bermutasi |
| Tipe | Parameter & return type eksplisit selalu; PHPDoc `array{...}` untuk struktur array |
| Enum | PHP native enum; case **TitleCase** (`TransactionType::Transfer`); method `label()` Bahasa Indonesia untuk UI |
| Match | Gunakan `match` alih-alih `switch`; `never` untuk cabang yang selalu melempar |
| Early return | Hindari `else` setelah `return`; kedalaman nesting maksimum 2 |
| Panjang metode | Target ≤ 20 baris; sinyal untuk ekstraksi, bukan aturan buta |
| Naming | Deskriptif & bermakna: `isRegisteredForDiscounts`, bukan `discount()`. Action = `VerbNoun`; Service = `NounService`; DTO = `NounData`/`NounResult` |
| Magic number | Dilarang; konstanta bernama atau konfigurasi |
| Komentar | PHPDoc untuk kontrak publik; komentar inline hanya untuk logika yang benar-benar rumit |
| Exception | Satu hierarki: `DomainException` (abstrak) → exception spesifik per aturan; pesan Bahasa Indonesia siap tampil untuk pengguna |
| Helper global | Hanya lewat `App\Support\*`; jangan menambah helper global baru |

### 4.3 Konvensi database

1. Nama tabel jamak `snake_case`; foreign key `{tabel_tunggal}_id`; index deskriptif.
2. Semua kolom uang: `DECIMAL(19,2)`; tanggal transaksi: `DATE`; timestamp: `TIMESTAMP` (UTC).
3. Setiap migrasi wajib punya `down()` yang benar; migrasi tidak boleh diedit setelah dijalankan di
   lingkungan bersama — buat migrasi baru.
4. Foreign key dengan perilaku sengaja: **`RESTRICT`** untuk entitas keuangan yang masih dirujuk;
   jangan `CASCADE` pada data keuangan.
5. Index wajib sesuai jalur query (PRD §10.4): `(user_id, transaction_date)`,
   `(source_account_id, transaction_date)`, `(category_id, transaction_date)`, `(user_id, status)`.
6. Unique constraint untuk invarian: `bills.paid_transaction_id`, keunikan anggaran aktif
   (ditegakkan aplikasi + index), `transactions.client_request_id`.
7. Seeder hanya kategori sistem + data sintetis dev; **tidak pernah** data keuangan pribadi.
8. Query: parameter binding selalu; dilarang interpolasi string ke SQL; agregasi via SQL (bukan
   memuat semua baris ke PHP); pantau N+1 dengan eager loading + review.

### 4.4 Konvensi frontend (Blade + Tailwind + Alpine)

**Design tokens** (dari mockup `Monetra Finance App UI Showcase.png`, difinalisasi di Step 0.4):

| Token | Nilai awal | Pemakaian |
|---|---|---|
| `--color-primary` | `#2563EB` (biru) | Aksi utama, nav aktif, kartu saldo |
| `--color-accent` | `#F59E0B` (oranye) | Aksen brand, badge "Mendatang" |
| `--color-success` | `#16A34A` | Pemasukan — **selalu dengan ikon/tanda +**, bukan warna saja |
| `--color-danger` | `#DC2626` | Pengeluaran — **selalu dengan ikon/tanda −** |
| `--color-surface` | `#FFFFFF` | Kartu |
| `--color-background` | `#F1F5F9` | Latar halaman |
| Radius | `rounded-2xl` untuk kartu, `rounded-xl` tombol/input | Konsisten |
| Tipografi | Sans (Inter/Plus Jakarta Sans, diputuskan di 0.4) | Skala 12–24px |

**Komponen `x-ui.*` (inventaris awal):**

```
layouts/app.blade.php        — shell utama + bottom nav
layouts/guest.blade.php      — shell halaman auth
x-ui.bottom-nav              — 5 tab: Beranda, Aktivitas, Tambah (+), Anggaran, Aset
x-ui.page-header             — judul + aksi kanan
x-ui.card                    — kontainer dasar
x-ui.stat-card               — kartu metrik (label + nominal + delta)
x-ui.button                  — varian: primary, secondary, danger, ghost (target ≥ 44px)
x-ui.input / x-ui.select     — label, error di dekat field, nilai lama dipertahankan
x-ui.currency-input          — input nominal dengan format ribuan (server tetap sumber validasi)
x-ui.date-field              — date picker
x-ui.badge                   — varian: success, danger, warning, neutral (teks + warna)
x-ui.progress-bar            — progres anggaran/target + label persentase
x-ui.empty-state             — ikon + penjelasan cara mulai
x-ui.skeleton                — keadaan memuat
x-ui.modal-confirm           — konfirmasi aksi destruktif (menjelaskan dampak)
x-ui.alert                   — sukses / gagal / info
```

**Aturan frontend:**

1. Mobile-first: semua layar inti berfungsi pada **360px**; target sentuh ≥ 44×44px.
2. Aksesibilitas: label form, focus-visible, kontras aksesibel, status tidak disampaikan warna
   saja, grafik punya alternatif tabel, `aria-live` untuk flash (PRD §7.3, §15).
3. Alpine.js hanya untuk interaksi lokal ringan (toggle visibilitas saldo, konfirmasi modal);
   **keuangan tidak pernah dihitung di browser**.
4. Progressive disclosure: catatan/referensi/tag tersembunyi di balik "Lainnya".
5. Setiap form destruktif memakai `x-ui.modal-confirm` dengan konsekuensi eksplisit.
6. Tidak ada CSS inline ad-hoc; semua via token & komponen.

### 4.5 Error handling & logging

1. Exception domain dirender menjadi pesan Indonesia yang aman untuk pengguna (422/redirect +
   error di field terkait); exception tak terduga → halaman error + log dengan konteks.
2. Semua log memuat `request_id` (middleware menghasilkan UUID per request) — korelasi satu
   request penuh.
3. **Larangan log**: kata sandi, token, cookie, `Authorization` header, payload uang penuh.
4. Channel terpisah untuk peristiwa keamanan (`security`): login gagal, throttle, perubahan
   kredensial.
5. Produksi: `APP_DEBUG=false`; error detail tidak pernah tampil ke pengguna.

### 4.6 Git workflow & konvensi commit

1. Repository Git dengan branch `main` sebagai baseline yang selalu hijau.
2. **Satu step = satu branch** `step/NN-slug` (contoh: `step/1-5-transactions-income-expense`);
   di-merge ke `main` hanya setelah seluruh gate §4.7 lulus.
3. Commit kecil dan tematik dengan **Conventional Commits**:
   `feat(transactions): post income/expense`, `fix(accounts): reject negative opening balance`,
   `test(safe-to-spend): prevent double counting`, `chore(deps): add filament v5`,
   `docs(adr): record balance computation decision`.
4. Setiap commit: `vendor/bin/pint --dirty` bersih dan tes terkait hijau.
5. Dilarang meng-commit `.env`, dump database, export CSV pribadi, atau kredensial.

### 4.7 Gate kualitas per step (wajib, semua harus lulus sebelum merge)

| Gate | Isi | Perintah/Alat |
|---|---|---|
| G1 — Test | Tes terkait hijau, lalu suite penuh | `php artisan test --compact` |
| G2 — Format | Pint bersih | `vendor/bin/pint --dirty --format agent` |
| G3 — Statik | Larastan tanpa error baru (setelah terpasang) | `vendor/bin/phpstan analyse` |
| G4 — Kriteria | Kriteria penerimaan step terbukti (bukti: test + manual) | Checklist step |
| G5 — Review | Review agen `php-reviewer`; `security-reviewer` untuk step bertanda 🔐 | Subagent review |
| G6 — Kebersihan | Tidak ada kredensial/`.env` di diff; tidak ada `dd()`/`dump()` tertinggal | `git diff` + grep |

### 4.8 Standar pengujian

1. **Piramida**: unit (domain/kalkulasi, tanpa DB) → feature (HTTP + database + otorisasi) →
   manual/browser (viewport & alur) sesuai PRD §16.
2. Unit wajib untuk: `Money` (presisi & pembulatan), `PlanningPeriod`, `PaydaySchedule` (akhir
   bulan, kabisat, batas zona waktu), semua kalkulator (saldo, anggaran, progres target,
   Safe-to-Spend termasuk kasus positif/nol/negatif & anti hitung ganda).
3. Feature wajib untuk: auth & throttle, CRUD + otorisasi negatif tiap resource, atomicity
   transfer (rollback), pembayaran tagihan (anti tautan ganda), rekonsiliasi dashboard/laporan,
   filter ekspor + mitigasi formula injection, kontrol akses Filament.
4. Gunakan **factory** semua model (dengan state bila perlu); `RefreshDatabase`; `$this->travelTo()`
   untuk waktu; jangan pernah membuat model manual berulang-ulang.
5. Nama tes = perilaku yang diharapkan: `test_transfer_moves_money_atomically_between_owned_accounts`.
6. Setiap bug fix menyertakan **regression test** yang gagal sebelum fix.
7. Target cakupan: domain ≥ 95%, service ≥ 90%, overall mengikuti PRD §16 (ukur via
   `php artisan test --coverage` bila diaktifkan di CI lokal).

### 4.9 Definition of Done (global, PRD §19)

Sebuah step selesai hanya jika: kebutuhan & kriteria penerimaan terpenuhi; validasi + otorisasi
server lengkap; tes otomatis mencakup perilaku & kasus tepi; UI berfungsi di seluler + desktop;
keadaan kosong/error/sukses tertangani; tidak ada data sensitif di log; dokumentasi diperbarui bila
perilaku berubah; kode diformat & ditinjau; ADR diperbarui bila keputusan berubah.

---

## 5. Roadmap Fase & Step

**Konvensi kartu step:**

- **Status**: `⬜ Belum` · `🟨 Berjalan` · `✅ Selesai` (perbarui saat eksekusi).
- **Ukuran** (indikatif untuk 1 developer + asistensi AI): S ≤ 0,5 hari · M 0,5–1,5 hari · L 1,5–3 hari.
  Estimasi bukan janji; ukur dan sesuaikan.
- **🔐** = step sensitif keamanan → wajib review `security-reviewer` (Gate G5).
- **Rantai agen disarankan** = peran yang dipakai saat eksekusi berbantuan agen
  (mis. `tdd-guide → php-reviewer`); semua peran tersedia sebagai subagent.

**Ringkasan fase:**

| Fase | Nama | Jumlah step | Fokus |
|---|---|---|---|
| 0 | Fondasi Proyek | 5 | DB, Git, standar kualitas, design system, ADR |
| 1 | Autentikasi & Fondasi Keuangan | 7 | Auth privat, Money, akun, kategori, transaksi, transfer, saldo |
| 2 | Dashboard & Aktivitas | 4 | Dashboard, filter lanjutan, idempotensi, QA responsif |
| 3 | Perencanaan & Laporan | 7 | Anggaran, tagihan, target, Safe-to-Spend, laporan, ekspor, Filament |
| 4 | Kualitas, Operasional & Rilis | 5 | Keamanan, aksesibilitas, backup, deployment, acceptance |

---

### FASE 0 — Fondasi Proyek

Kriteria keluar fase (PRD §19): aplikasi berjalan lokal; migrasi bisa dijalankan dari database
kosong; perintah pengujian otomatis terdokumentasi; rahasia tidak masuk Git.

---

#### Step 0.1 — Stabilkan koneksi database lokal 🔐

- **Status**: ⬜ Belum · **Ukuran**: S
- **Tujuan**: Database `mymoney` (dev) dan `mymoney_testing` (test) tersedia, user aplikasi
  khusus dengan least privilege, `.env` tersambung, migrasi dasar berjalan.
- **Rujukan**: PRD §11.1, §13, §18, temuan T-1/T-2/T-3.
- **Deliverable**:
  - Database `mymoney` & `mymoney_testing` (charset `utf8mb4`, collation `utf8mb4_unicode_ci`).
  - User MariaDB `monetra@localhost` + `monetra@127.0.0.1` (password kuat, **bukan** root) dengan
    grant hanya pada kedua schema tersebut.
  - `.env` diperbarui (DB_USERNAME/DB_PASSWORD) dan `.env.example` menjadi template MySQL.
- **Pekerjaan utama**:
  1. Provisioning via akun OS yang sudah terverifikasi punya hak:
     ```sql
     CREATE DATABASE IF NOT EXISTS mymoney CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
     CREATE DATABASE IF NOT EXISTS mymoney_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
     CREATE USER IF NOT EXISTS 'monetra'@'localhost' IDENTIFIED BY '<password-kuat>';
     CREATE USER IF NOT EXISTS 'monetra'@'127.0.0.1' IDENTIFIED BY '<password-kuat>';
     GRANT ALL PRIVILEGES ON mymoney.* TO 'monetra'@'localhost';
     GRANT ALL PRIVILEGES ON mymoney.* TO 'monetra'@'127.0.0.1';
     GRANT ALL PRIVILEGES ON mymoney_testing.* TO 'monetra'@'localhost';
     GRANT ALL PRIVILEGES ON mymoney_testing.* TO 'monetra'@'127.0.0.1';
     FLUSH PRIVILEGES;
     ```
  2. Perbarui `.env`: `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`,
     `DB_DATABASE=mymoney`, `DB_USERNAME=monetra`, `DB_PASSWORD=<password>`.
  3. Perbarui `.env.example` ke template MySQL dengan placeholder (tanpa kredensial asli).
  4. Verifikasi: `php artisan migrate --no-interaction`, `php artisan migrate:status`,
     smoke koneksi via `php artisan about`.
  5. Catat kredensial dev di password manager pemilik (bukan di repo).
- **Kriteria penerimaan**:
  - `php artisan migrate:status` menampilkan seluruh migrasi bawaan berstatus `Ran` tanpa error.
  - Koneksi TCP `127.0.0.1:3306` sebagai user `monetra` berhasil; user `root` tidak dipakai aplikasi.
  - `.env` tidak tertangkap Git (diverifikasi di step 0.2).
- **Pengujian**: verifikasi manual terarah (bukan tes otomatis — lingkungan), lalu suite bawaan
  `php artisan test --compact` hijau di `mymoney_testing` setelah phpunit.xml disesuaikan.
- **Rantai agen**: `general` (eksekusi) → verifikasi mandiri.

---

#### Step 0.2 — Inisialisasi Git & baseline

- **Status**: ⬜ Belum · **Ukuran**: S
- **Tujuan**: Riwayat perubahan dimulai; baseline skeleton + dokumen ter-commit.
- **Rujukan**: PRD §11.1 (version control), §22.11.
- **Deliverable**: repo Git lokal, branch `main`, commit baseline.
- **Pekerjaan utama**:
  1. `git init -b main`; verifikasi `.gitignore` mencakup `.env`, `/vendor`, `/node_modules`,
     `/public/build`, `/storage/*.key`, `/.phpunit.cache`, file dump/export.
  2. Tambahkan `.gitattributes` (normalisasi LF) bila belum memadai.
  3. Commit pertama: `chore: baseline laravel 13 skeleton with PRD and implementation plan`.
- **Kriteria penerimaan**: `git status` bersih; `git check-ignore .env` membuktikan `.env`
  terabaikan; `git log --oneline` memuat baseline.
- **Pengujian**: `git status`, `git check-ignore -v .env`.
- **Rantai agen**: `general`.

---

#### Step 0.3 — Standar mutu kode & perintah pengujian

- **Status**: ⬜ Belum · **Ukuran**: S
- **Tujuan**: Format, analisis statis (opsional), dan perintah pengujian terdokumentasi dan dapat
  dijalankan siapa pun (kriteria keluar Fase 0).
- **Rujukan**: PRD §11.1, §16; `AGENTS.md`.
- **Deliverable**:
  - `phpunit.xml` memakai `mymoney_testing` (DT-03).
  - Pint bersih pada baseline.
  - (Opsional, butuh persetujuan) Larastan level 6 + `phpstan.neon`.
  - Bagian "Pengembangan & Pengujian" di `README.md` (perintah: test, pint, build, dev).
- **Pekerjaan utama**:
  1. Ubah `phpunit.xml`: `DB_CONNECTION=mysql`, `DB_DATABASE=mymoney_testing`.
  2. Jalankan `vendor/bin/pint` baseline; `php artisan test --compact` hijau.
  3. Instal & konfigurasi Larastan bila disetujui; jalankan analisis baseline.
  4. Dokumentasikan perintah di README.
- **Kriteria penerimaan**: `php artisan test --compact` hijau pada `mymoney_testing`; `vendor/bin/pint --test`
  bersih; perintah terdokumentasi.
- **Pengujian**: suite bawaan (2 example tests) hijau; smoke `composer test`.
- **Rantai agen**: `general` → `php-reviewer`.

---

#### Step 0.4 — Design system & shell Blade mobile-first

- **Status**: ⬜ Belum · **Ukuran**: M
- **Tujuan**: Kerangka visual Monetra siap dipakai semua layar: token, layout, komponen `x-ui.*`,
  navigasi bawah 5 tab, halaman placeholder.
- **Rujukan**: PRD §7.1–7.3, §15, mockup `Monetra Finance App UI Showcase.png`.
- **Deliverable**:
  - Token Tailwind 4 via `@theme` di `resources/css/app.css` (tabel §4.4).
  - `layouts/app.blade.php`, `layouts/guest.blade.php`.
  - Komponen `x-ui.*` (§4.4) dengan varian lengkap + preview internal.
  - Route + halaman placeholder: Beranda, Aktivitas, Tambah, Anggaran, Aset (data statis).
  - Navigasi bawah 5 tab mengikuti mockup (tombol Tambah berbentuk aksen tengah).
- **Pekerjaan utama**:
  1. Definisikan token (warna/brand dari mockup, radius, shadow, tipografi, spacing 4pt).
  2. Bangun komponen satu per satu dengan contoh pemakaian; komponen butuh state interaktif
     memakai Alpine minimal.
  3. Halaman placeholder memakai komponen (membuktikan komposisi), termasuk `x-ui.empty-state`.
  4. Verifikasi 360px, 768px, 1280px; audit target sentuh ≥ 44px; focus ring terlihat.
- **Kriteria penerimaan**:
  - Semua halaman placeholder tampil tanpa scroll horizontal pada 360px.
  - Bottom nav 5 tab berfungsi, tab aktif terlihat (warna + indikator bentuk).
  - Tidak ada nilai CSS ad-hoc di luar token; tidak ada library CSS kedua.
- **Pengujian**: feature test ringan (route placeholder 200), inspeksi manual viewport.
- **Rantai agen**: `general` → `code-reviewer`.

---

#### Step 0.5 — ADR & keputusan terdokumentasi

- **Status**: ⬜ Belum · **Ukuran**: S
- **Tujuan**: Keputusan arsitektur tercatat agar tidak hilang antar sesi pengembangan.
- **Rujukan**: PRD §23.
- **Deliverable**: `docs/adr/ADR-001 … ADR-007` (format: konteks, keputusan, alternatif,
  konsekuensi, tanggal). Isi awal:
  - ADR-001: Laravel 13 + Blade monolit modular; MariaDB 11.8 sebagai rilis kompatibel.
  - ADR-002: Representasi uang DECIMAL(19,2) + `Money` VO (brick/math); larangan float.
  - ADR-003: Siklus hidup transaksi posted/voided, transfer, perhitungan saldo dihitung.
  - ADR-004: Provisioning akun privat (registrasi publik nonaktif) + throttle login.
  - ADR-005: Kontrol akses Filament & operasi pemeliharaan yang diizinkan.
  - ADR-006: Asumsi Safe-to-Spend & kebijakan akun yang dapat dibelanjakan.
  - ADR-007: Kebijakan backup, ekspor, pemulihan (diperbarui di step 4.3).
- **Kriteria penerimaan**: 7 ADR ada dengan format lengkap; dirujuk dari dokumen ini.
- **Pengujian**: review `architect`.
- **Rantai agen**: `architect` → `doc-updater`.

---

### FASE 1 — Autentikasi & Fondasi Keuangan

Kriteria keluar fase (PRD §19): tes unit/feature inti lulus; pembatasan kepemilikan diterapkan;
saldo awal dan transaksi dapat direkonsiliasi.

---

#### Step 1.1 — Autentikasi privat & preferensi profil 🔐

- **Status**: ⬜ Belum · **Ukuran**: L
- **Tujuan**: Login/logout/reset aman untuk aplikasi privat; preferensi pengguna (zona waktu,
  lokal, mata uang, aturan gajian) tersimpan dan dipakai sistem.
- **Rujukan**: FR-001, FR-002, OD-01, OD-11, DT-05, PRD §12.1.
- **Deliverable**:
  - Laravel Breeze (Blade) terpasang, view dikustomkan ke token Monetra (`layouts/guest`).
  - **Registrasi publik dihapus** (route + view + tes negatif).
  - Throttle login (5 percobaan/menit per kombinasi email+IP) + log keamanan.
  - Reset kata sandi via email (mailer `log` di lokal; dokumentasi konfigurasi produksi).
  - Migrasi `users` diperluas: `timezone` (default `Asia/Jakarta`), `locale` (default `id`),
    `currency` (default `IDR`), `payday_day` (TINYINT 1–31, nullable), `next_payday_date` (DATE,
    nullable).
  - Halaman Profil: nama, email, preferensi gajian, zona waktu; ganti kata sandi.
  - Command `php artisan monetra:create-owner` (idempoten, interaktif, password tidak muncul di
    argumen shell) untuk provisioning akun pemilik.
  - `PaydaySchedule` (domain): `nextPayday(User, from)` — `payday_day` melebihi hari bulan →
    hari terakhir bulan (OD-11) + dokumentasi.
- **Pekerjaan utama**:
  1. Instal Breeze (persetujuan dependency), kustom view login sesuai mockup #1.
  2. Hapus registrasi publik + tulis tes negatif (`GET /register` → 404).
  3. Implementasi throttle + test; event login sukses/gagal → log keamanan.
  4. Perluas migrasi `users`; command create-owner + test idempotensi.
  5. Halaman profil + FormRequest + policy; audit perubahan preferensi.
  6. `PaydaySchedule` + unit test (bulan 28/29/30/31 hari, kabisat, tanggal eksplisit).
- **Kriteria penerimaan** (FR-001/FR-002):
  - Permintaan tanpa autentikasi ke halaman terlindungi → redirect login.
  - Logout membatalkan sesi; sesi lama tidak dapat dipakai kembali.
  - Kata sandi tidak pernah disimpan/di-log sebagai teks biasa (tes + review kode).
  - Throttle login diuji feature test (permintaan ke-6 → 429).
  - Perubahan preferensi tersimpan dan tercermin pada format tanggal/nominal.
  - Definisi tanggal gajian didokumentasikan (docblock + ADR-006 terkait).
- **Pengujian**: feature auth suite; unit `PaydaySchedule`; tes negatif registrasi; tes audit.
- **Rantai agen**: `tdd-guide` → `php-reviewer` → `security-reviewer` 🔐

---

#### Step 1.2 — Domain Money & format IDR

- **Status**: ⬜ Belum · **Ukuran**: M
- **Tujuan**: Fondasi presisi uang untuk seluruh aplikasi: satu value object, satu jalur
  pembulatan, satu formatter — sebelum satu pun fitur keuangan dibangun.
- **Rujukan**: PRD §9.1, DT-04, DT-12.
- **Deliverable**:
  - `App\Domain\Money\Money` (immutable, brick/math) + `RoundingPolicy` + `MoneyFormatter`.
  - Cast Eloquent `MoneyCast` untuk kolom `DECIMAL(19,2)` (string desimal ↔ `Money`).
  - Directive/helper Blade `@money` (memakai `MoneyFormatter`).
  - Dokumentasi singkat aturan pembulatan di ADR-002.
- **Pekerjaan utama**:
  1. Deklarasikan `brick/math` sebagai dependensi eksplisit (persetujuan).
  2. Implementasi `Money` (lihat sketsa §3.5) + `RoundingPolicy` (HALF_UP, 2 desimal, satu tempat).
  3. Implementasi `MoneyFormatter` via `NumberFormatter('id_ID')`; tampilan IDR default **tanpa
     desimal** (mengikuti mockup), parameter untuk desimal bila diperlukan internal.
  4. `MoneyCast` + pemakaian di model (dimulai di step 1.3).
- **Kriteria penerimaan**:
  - Tidak ada `floatval`/`(float)` dalam jalur uang (dicek review + grep).
  - Format tampilan konsisten `Rp12.450.000`; nilai tersimpan tidak berubah oleh formatter.
- **Pengujian** (unit): tambah/kurang presisi 2 desimal; pembulatan 0,005; nilai 19 digit;
  nol/negatif; formatter (ribuan id-ID, nilai besar, nol).
- **Rantai agen**: `tdd-guide` → `php-reviewer`.

---

#### Step 1.3 — Akun keuangan

- **Status**: ⬜ Belum · **Ukuran**: L
- **Tujuan**: Pengguna dapat mengelola akun keuangan (tunai, bank, dompet digital, investasi,
  tabungan, lainnya) beserta saldo awal — dasar semua perhitungan.
- **Rujukan**: FR-003, PRD §10.2, OD-05.
- **Deliverable**:
  - Migrasi `accounts` (kolom §10.2 + index `(user_id, status)` + FK `RESTRICT`).
  - Enum `AccountType`, `AccountStatus`; model `Account` + casts + relasi.
  - `AccountPolicy`; `CreateAccount`, `UpdateAccount`, `ArchiveAccount` actions.
  - Controller + FormRequest + view: daftar akun (gaya mockup #8), form buat/edit, arsip dengan
    konfirmasi.
  - Default flag per tipe: investasi → `include_in_total_assets=true`,
    `include_in_safe_to_spend=false` (PRD FR-006); dapat diubah pengguna.
- **Pekerjaan utama**:
  1. Migrasi + model + enum + factory (dengan state per tipe).
  2. Actions + policy + FormRequest (nama ≤ 100, tipe valid, `opening_balance` valid ≥ 0,
     `opening_date` tidak di masa depan).
  3. UI index/create/edit/archive + empty state.
  4. Guard: akun dengan transaksi tidak dapat di-hard-delete (hanya arsip).
- **Kriteria penerimaan** (FR-003):
  - Akun dibuat dengan saldo awal → saldo dasar tercatat dan dapat direkonsiliasi manual.
  - Pengarsipan tidak menghapus transaksi historis; akun arsip tidak muncul di pilihan baru.
  - User lain tidak dapat membaca/mengubah (404 pada ID milik orang lain).
- **Pengujian**: feature CRUD + negatif otorisasi; unit default flag per tipe; factory states.
- **Rantai agen**: `tdd-guide` → `database-reviewer` → `php-reviewer`.

---

#### Step 1.4 — Kategori pemasukan & pengeluaran

- **Status**: ⬜ Belum · **Ukuran**: M
- **Tujuan**: Struktur kategori sistem + kategori pengguna, dengan aturan arsip (bukan hapus).
- **Rujukan**: FR-004, PRD §10.3.
- **Deliverable**:
  - Migrasi `categories` (+ index/unique yang disepakati; strategi keunikan sistem vs pengguna
    ditegakkan aplikasi karena `user_id` NULL).
  - Enum `CategoryType`; model + `CategoryPolicy`; actions create/update/archive.
  - Seeder `CategorySeeder`: kategori sistem (expense: Makanan, Transportasi, Tempat Tinggal,
    Tagihan, Kesehatan, Belanja, Hiburan, Pendidikan, Keluarga, Biaya, Lainnya; income: Gaji,
    Freelance, Bisnis, Bunga/Dividen, Hadiah, Pengembalian Dana, Lainnya) + ikon whitelist
    (heroicons) + warna token.
  - UI kelola kategori (di area Pengaturan) + arsip + indikator "Sistem".
- **Pekerjaan utama**:
  1. Migrasi + model + enum + factory.
  2. Seeder idempoten (upsert by type+name, `user_id=null`, `is_system=true`).
  3. Policy + action; kategori sistem tidak dapat diubah/dihapus pengguna.
  4. Validasi: kategori hanya dapat dipakai oleh jenis transaksi yang sesuai.
- **Kriteria penerimaan** (FR-004):
  - Pilihan kategori difilter berdasarkan jenis transaksi pada form transaksi (dipakai di 1.5).
  - Kategori arsip tidak muncul pada transaksi baru tetapi tetap terbaca pada riwayat.
  - Seeder aman dijalankan berulang.
- **Pengujian**: feature CRUD + arsip + akses kategori sistem; unit aturan type matching.
- **Rantai agen**: `tdd-guide` → `database-reviewer` → `php-reviewer`.

---

#### Step 1.5 — Transaksi: pemasukan & pengeluaran

- **Status**: ⬜ Belum · **Ukuran**: L
- **Tujuan**: Alur pencatatan cepat (< 30 detik, metrik PRD §3.3) untuk pemasukan/pengeluaran +
  daftar aktivitas dasar.
- **Rujukan**: FR-005 (bagian income/expense), mockup #3 & #4, PRD §10.4.
- **Deliverable**:
  - Migrasi `transactions` (kolom §10.4 + kolom DT-08 `adjustment_direction` + DT-09
    `client_request_id` + index `(user_id, transaction_date)`, `(source_account_id, transaction_date)`,
    `(category_id, transaction_date)`).
  - Enum `TransactionType`, `TransactionStatus`, `AdjustmentDirection`.
  - `PostTransaction` action (income/expense) + `TransactionPolicy`.
  - Form Tambah (tab Pemasukan | Pengeluaran | Transfer-disabled sementara) sesuai mockup #3:
    jumlah, kategori (terfilter jenis), akun, tanggal (default hari ini, zona waktu pengguna),
    catatan opsional (progressive disclosure).
  - Halaman Aktivitas dasar: daftar dengan pagination, badge tipe, nominal dengan tanda/ikon.
- **Pekerjaan utama**:
  1. Migrasi + model + enum + factory (state: income, expense, voided).
  2. `PostTransaction` (invarian §3.4.2) + event `TransactionPosted` + audit listener.
  3. FormRequest dengan pesan Bahasa Indonesia; validasi `amount > 0`, batas wajar maksimum,
     kategori milik jenis yang benar, akun milik pengguna.
  4. UI form + daftar; empty state aktivitas.
- **Kriteria penerimaan** (FR-005):
  - Pemasukan menambah dan pengeluaran mengurangi saldo akun yang benar (diuji setelah 1.7,
    di sini diuji melalui data tersimpan + query dasar).
  - Nominal negatif, nol, format salah, atau melebihi batas wajar ditolak.
  - ID akun/kategori milik pengguna lain tidak dapat dipakai (404/422).
  - Transfer tidak diklasifikasikan sebagai pemasukan/pengeluaran (placeholder tab transfer).
- **Pengujian**: feature post income/expense + validasi + otorisasi; unit data mapping.
- **Rantai agen**: `tdd-guide` → `php-reviewer` 🔐

---

#### Step 1.6 — Transfer atomik & pembatalan transaksi 🔐

- **Status**: ⬜ Belum · **Ukuran**: L
- **Tujuan**: Transfer antar-akun milik sendiri yang atomik dan pembatalan (void) dengan jejak
  audit — dua jalur paling rawan bug keuangan.
- **Rujukan**: FR-005 (transfer + void), PRD §9.3, §9.5, §13.
- **Deliverable**:
  - `PostTransfer` action (satu `DB::transaction`; sumber ≠ tujuan; tanpa kategori; total agregat
    tetap).
  - `VoidTransaction` action (`posted → voided`, alasan, `voided_at`; tolak void ganda; tolak void
    transaksi yang tertaut pembayaran tagihan aktif).
  - Tab Transfer pada form Tambah (aktif) + UI Batalkan dengan `x-ui.modal-confirm` menjelaskan
    dampak.
  - Event `TransactionVoided` + audit.
- **Pekerjaan utama**:
  1. Action + invarian + event; guard ulang kepemilikan kedua akun.
  2. UI transfer + void; pesan error ramah.
  3. Tes atomicity: kegagalan di tengah transaksi → rollback penuh (tidak ada efek parsial).
  4. Kebijakan biaya transfer: dicatat sebagai pengeluaran terpisah (dokumentasikan; tidak ada
     selisih tersembunyi).
- **Kriteria penerimaan** (FR-005):
  - Transfer mengubah saldo kedua akun tetapi tidak mengubah total agregat dana.
  - Sumber = tujuan ditolak; transfer tanpa tujuan ditolak.
  - Void mengembalikan seluruh metrik turunan; void ganda ditolak; audit mencatat aktor & alasan.
- **Pengujian**: unit dampak transfer; feature transfer + void + rollback; negatif otorisasi.
- **Rantai agen**: `tdd-guide` → `php-reviewer` → `security-reviewer` 🔐

---

#### Step 1.7 — Kalkulator saldo & rekonsiliasi

- **Status**: ⬜ Belum · **Ukuran**: M
- **Tujuan**: Satu-satunya sumber kebenaran saldo (`AccountBalanceService`) + jalur resmi
  penyesuaian rekonsiliasi.
- **Rujukan**: FR-003 (rekonsiliasi), PRD §9.2, OD-05.
- **Deliverable**:
  - `AccountBalanceService` (`balanceOf`, `spendableTotal`, `assetsTotal`) dengan query agregat
    efisien (tanpa N+1; `SUM CASE` per akun).
  - `ReconcileAccount` action → transaksi `adjustment` + `adjustment_direction` + alasan + audit.
  - UI: saldo per akun di halaman Aset + aksi "Rekonsiliasi" (input saldo aktual → selisih
    ditampilkan sebelum konfirmasi).
- **Pekerjaan utama**:
  1. Service + query + unit test lintas tipe transaksi (income/expense/transfer/voided/arsip).
  2. Action rekonsiliasi (validasi selisih ≠ 0; hanya `posted` dihitung).
  3. UI rekonsiliasi + konfirmasi selisih.
- **Kriteria penerimaan** (FR-003):
  - Total akun = `opening_balance` + seluruh dampak transaksi `posted`; transaksi `voided`
    dikecualikan.
  - Rekonsiliasi mengubah saldo ke nilai target dengan jejak audit (siapa, kapan, alasan).
  - Sistem tidak pernah mengubah riwayat secara diam-diam.
- **Pengujian**: unit kalkulator (semua kombinasi); feature rekonsiliasi + audit; rekonsiliasi
  dashboard nanti diuji di 2.1.
- **Rantai agen**: `tdd-guide` → `php-reviewer`.

---

### FASE 2 — Dashboard & Aktivitas

Kriteria keluar fase (PRD §19): pengujian transfer & arus kas lulus; total dashboard cocok dengan
catatan sumber; alur inti berfungsi pada lebar 360px.

---

#### Step 2.1 — Dashboard inti

- **Status**: ⬜ Belum · **Ukuran**: L
- **Tujuan**: Halaman Beranda menjawab "berapa total uang saya" dan "bagaimana bulan ini berjalan"
  — sesuai mockup #2 — dengan kartu area Fase 3 ditampilkan sebagai placeholder jujur.
- **Rujukan**: FR-006, §7.2, §9.3.
- **Deliverable**:
  - `DashboardMetricsService` (DTO `DashboardMetrics`): total saldo (flag inklusi), pemasukan &
    pengeluaran periode berjalan, arus kas neto, transaksi terbaru (N=5).
  - UI Beranda: sapaan, kartu Total Saldo (+ toggle sembunyikan angka sesi), kartu pemasukan/
    pengeluaran, section Tagihan Terdekat & Aktivitas Terbaru (state "belum tersedia" untuk
    bagian yang menunggu Fase 3), tombol Tambah.
  - Pemilih periode bulan (mempengaruhi metrik berbasis periode tanpa mengubah data historis).
- **Pekerjaan utama**:
  1. Service + query agregat periode setengah terbuka (`PlanningPeriod`) + unit test batas bulan &
     zona waktu.
  2. UI + skeleton loading + empty/error state.
  3. Integrasi `AccountBalanceService` untuk total saldo.
- **Kriteria penerimaan** (FR-006):
  - Total dashboard dapat direkonsiliasi dengan daftar transaksi & akun untuk periode yang sama
    (feature test eksplisit).
  - Transfer & adjustment tidak masuk pemasukan/pengeluaran.
  - Dashboard tidak menampilkan presisi palsu untuk fitur yang belum ada (placeholder jelas).
- **Pengujian**: feature rekonsiliasi metrik vs query sumber; unit periode; N+1 check (query count).
- **Rantai agen**: `tdd-guide` → `php-reviewer`.

---

#### Step 2.2 — Aktivitas lanjutan (cari, filter, detail, void)

- **Status**: ⬜ Belum · **Ukuran**: M
- **Tujuan**: Halaman Aktivitas lengkap sesuai mockup #4: pencarian, filter, detail, aksi void.
- **Rujukan**: FR-005, persiapan FR-011/FR-012.
- **Deliverable**:
  - Filter: rentang tanggal, akun (chips "Semua Akun"), kategori, tipe; pencarian deskripsi
    (LIKE ter-escape); urutan default terbaru; pagination 25 dengan pengingat filter aktif.
  - Halaman detail transaksi (termasuk `voided` dengan alasan & waktu).
  - Aksi void dari daftar/detail dengan konfirmasi.
- **Pekerjaan utama**:
  1. `TransactionFilter` DTO + query scopes; index database sesuai pola filter.
  2. UI filter (sheet/chips di mobile) + mempertahankan filter saat paginasi.
  3. Tes: kombinasi filter, filter + pagination, otorisasi, performa dasar (query count).
- **Kriteria penerimaan**:
  - Filter bertahan antar halaman; hasil cocok dengan data yang difilter.
  - Tidak ada N+1 (diverifikasi feature test query count).
- **Pengujian**: feature filter/cari/paginasi + negatif otorisasi.
- **Rantai agen**: `tdd-guide` → `php-reviewer`.

---

#### Step 2.3 — Anti double-submit & idempotensi

- **Status**: ⬜ Belum · **Ukuran**: M
- **Tujuan**: Klik ganda / pengiriman ulang tidak pernah menghasilkan transaksi dobel (PRD §12.5,
  §13).
- **Rujukan**: DT-09, PRD §13.
- **Deliverable**:
  - `client_request_id` (UUID v4 dari form, unique) diterapkan pada form transaksi/transfer.
  - Handler idempoten: duplikat → kembalikan hasil yang sudah ada (redirect dengan pesan
    "transaksi sudah tercatat"), bukan error keras.
  - Tombol submit nonaktif saat mengirim (Alpine) sebagai lapisan UX, bukan pengaman utama.
- **Pekerjaan utama**:
  1. Migrasi penambahan kolom (pra-produksi) + unique index.
  2. `PostTransaction`/`PostTransfer` menangani `client_request_id`.
  3. Tes: dua request identik paralel-berurutan → satu baris.
- **Kriteria penerimaan**: double-submit menghasilkan tepat satu transaksi; bukti via feature test.
- **Pengujian**: feature idempotensi; unit guard.
- **Rantai agen**: `tdd-guide` → `php-reviewer` 🔐

---

#### Step 2.4 — QA responsif & aksesibilitas dasar (Fase 2)

- **Status**: ⬜ Belum · **Ukuran**: M
- **Tujuan**: Memvalidasi PRD §7.3/§15 pada seluruh alur yang sudah dibangun sebelum masuk Fase 3.
- **Rujukan**: PRD §16.3, §15.
- **Deliverable**: laporan QA singkat (di deskripsi PR/commit) + perbaikan yang ditemukan.
- **Pekerjaan utama**:
  1. Checklist manual: viewport 360px/ponsel umum/tablet/desktop; navigasi keyboard; validasi
     form; empty/loading/error/success; nama kategori panjang; nominal besar
     (Rp999.999.999.999); pergantian bulan; klik ulang form.
  2. Perbaikan temuan + regression test bila relevan.
  3. (Opsional) smoke E2E Playwright untuk alur: login → tambah transaksi → lihat aktivitas.
- **Kriteria penerimaan**: seluruh item checklist lulus atau tercatat sebagai known-issue dengan
  rencana; tidak ada scroll horizontal di 360px.
- **Pengujian**: manual matrix + opsional `e2e-runner`.
- **Rantai agen**: `e2e-runner` (opsional) → `code-reviewer`.

---

### FASE 3 — Perencanaan & Laporan

Kriteria keluar fase (PRD §19): tidak ada hitung ganda pada skenario yang ditentukan; ekspor sesuai
catatan yang difilter; pengujian batas periode lulus.

---

#### Step 3.1 — Anggaran bulanan

- **Status**: ⬜ Belum · **Ukuran**: L
- **Tujuan**: Anggaran per kategori pengeluaran per periode bulanan dengan peringatan ambang.
- **Rujukan**: FR-007, PRD §10.5, mockup #5.
- **Deliverable**:
  - Migrasi `budgets` (kolom §10.5: `period_start` inklusif, `period_end` eksklusif,
    `limit_amount > 0`, `warning_threshold` default 80, status active/archived).
  - Keunikan anggaran aktif per `(user, category, period_start)` — ditegakkan validasi aplikasi +
    index pendukung.
  - `BudgetUsageService`: terpakai = Σ expense `posted` kategori & periode cocok.
  - UI mockup #5: ringkasan (total anggaran/terpakai/sisa) + daftar kategori dengan progress bar,
    persentase, warna ambang + label teks (80%/100%).
- **Pekerjaan utama**:
  1. Migrasi + model + enum + factory (state: active, archived, exhausted).
  2. Service + unit test periode setengah terbuka (batas hari pertama/terakhir bulan).
  3. Action create/update/archive + FormRequest + policy.
  4. UI + warning ambang (bawaan 80% & 100%, dikonfigurasi per anggaran).
- **Kriteria penerimaan** (FR-007):
  - Transaksi di luar periode tidak memengaruhi anggaran; transfer tidak memengaruhi anggaran.
  - Batas tidak valid (≤ 0) ditolak; pemakaian & sisa konsisten dengan daftar transaksi.
  - Tidak mungkin ada dua anggaran aktif untuk kategori+periode yang sama.
- **Pengujian**: unit usage & batas periode; feature CRUD + duplikasi + otorisasi.
- **Rantai agen**: `tdd-guide` → `database-reviewer` → `php-reviewer`.

---

#### Step 3.2 — Tagihan & pelacakan jatuh tempo

- **Status**: ⬜ Belum · **Ukuran**: L
- **Tujuan**: Kewajiban mendatang dengan status, jatuh tempo (zona waktu pengguna), dan tautan
  pembayaran tunggal ke transaksi pengeluaran.
- **Rujukan**: FR-008, PRD §10.6, mockup #6.
- **Deliverable**:
  - Migrasi `bills` (+ `paid_transaction_id` UNIQUE; `recurrence_rule` JSON dibiarkan kosong —
  fitur P1).
  - Enum `BillStatus` (`upcoming`, `paid`, `skipped`); **overdue sebagai status turunan**
    (`BillStatusService`), bukan tersimpan.
  - `CreateBill`, `UpdateBill`, `PayBill`, `CancelBillPayment`, `SkipBill` actions.
  - UI mockup #6: tab Semua | Mendatang | Terlambat | Lunas; form tambah; aksi "Tandai Lunas"
    (memilih akun + kategori → membuat expense tertaut).
- **Pekerjaan utama**:
  1. Migrasi + model + enum + factory (state: overdue, paid, skipped).
  2. `PayBill`: membuat expense via jalur domain yang sama + menautkan; guard anti ganda
     (sudah `paid` → tolak; `paid_transaction_id` unique).
  3. `CancelBillPayment`: void transaksi tertaut + kembali `upcoming`; audit.
  4. `BillStatusService.upcoming()` untuk dashboard (dan nanti Safe-to-Spend).
- **Kriteria penerimaan** (FR-008):
  - Tagihan dapat ditandai lunas dan ditautkan ke **tepat satu** transaksi.
  - Pembayaran ganda ditolak (tes: pay dua kali → gagal konsisten).
  - "Terlambat" dihitung dari `due_date` + zona waktu pengguna; tidak pernah tersimpan usang.
- **Pengujian**: feature pay/cancel/skip + double-pay; unit overdue (batas hari, zona waktu).
- **Rantai agen**: `tdd-guide` → `php-reviewer`.

---

#### Step 3.3 — Target tabungan

- **Status**: ⬜ Belum · **Ukuran**: L
- **Tujuan**: Target dengan ledger kontribusi/penarikan yang dapat diaudit dan tidak menghitung
  ganda saat dana benar-benar dipindahkan.
- **Rujukan**: FR-009, PRD §10.7, mockup #7.
- **Deliverable**:
  - Migrasi `savings_goals` + `savings_goal_entries` (ledger: arah, nominal, tanggal, transaksi
    tertaut opsional, catatan).
  - Enum `SavingsGoalStatus` (`active`, `paused`, `completed`, `cancelled`),
    `SavingsEntryDirection` (`contribution`, `withdrawal`).
  - Actions: `CreateSavingsGoal`, `UpdateSavingsGoal`, `RecordContribution`, `RecordWithdrawal`.
  - `SavingsGoalProgressService`: progres = Σ kontribusi − Σ penarikan.
  - UI mockup #7: kartu target, progress bar, target date, tombol Tambah Kontribusi.
- **Pekerjaan utama**:
  1. Migrasi + model + factory.
  2. Mode kontribusi: (a) alokasi murni (hanya ledger) atau (b) dengan transfer nyata
     (membuat transfer antar-akun + entry tertaut) — mencegah hitung ganda Safe-to-Spend.
  3. Over-target ditampilkan sebagai status kelebihan dana eksplisit (tidak error).
  4. Target selesai/dijeda/dibatalkan tetap tampil di riwayat.
- **Kriteria penerimaan** (FR-009):
  - Kontribusi memperbarui progres tepat satu kali; penarikan mengurangi dengan benar.
  - Kontribusi dengan transfer tertaut tidak dihitung dua kali (unit test eksplisit).
  - Status akhir tetap tersedia dalam riwayat.
- **Pengujian**: unit progres & anti-hitung-ganda; feature ledger + opsional transfer tertaut.
- **Rantai agen**: `tdd-guide` → `php-reviewer`.

---

#### Step 3.4 — Safe-to-Spend 🔐

- **Status**: ⬜ Belum · **Ukuran**: L
- **Tujuan**: Estimasi dana diskresioner yang transparan sampai tanggal perencanaan, dengan
  rincian asumsi dan pencegahan hitung ganda — fitur pembeda utama produk.
- **Rujukan**: FR-010, ADR-006, PRD §5.4.
- **Deliverable**:
  - `SafeToSpendService` + `SafeToSpendResult` (rincian per komponen + asumsi + tanggal rencana).
  - Rumus MVP terdokumentasi:
    `safe = saldo spendable − tagihan belum dibayar s.d. planningDate − alokasi target yang belum dieksekusi`
    (dana yang sudah keluar dari saldo dasar tidak dikurangi lagi).
  - Tanggal perencanaan: `next_payday_date` → `payday_day` (via `PaydaySchedule`) → pilihan manual.
  - UI: kartu di dashboard → halaman rincian (rincian masukan, asumsi, disclaimer "estimasi,
    bukan nasihat keuangan"); state "belum lengkap" bila data penting kosong.
- **Pekerjaan utama**:
  1. Service + DTO + unit test lengkap (positif, nol, negatif; eksklusi investasi; tagihan dihitung
     sekali; alokasi sudah-ditransfer tidak dikurangi lagi).
  2. UI rincian + pengguna dapat mengubah asumsi (mis. mengecualikan komponen tertentu) — perubahan
     asumsi bersifat sesi tampilan, tidak mengubah data.
  3. Terminologi hati-hati: tidak menyiratkan jaminan kemampuan membayar.
- **Kriteria penerimaan** (FR-010):
  - Unit test membuktikan: tagihan & tabungan cadangan tidak dihitung ganda.
  - Akun yang dikecualikan (investasi default) tidak menambah saldo spendable.
  - UI menampilkan tanggal rencana + asumsi; state belum lengkap jujur.
- **Pengujian**: unit suite khusus Safe-to-Spend (wajib, PRD §16.1); feature rincian UI.
- **Rantai agen**: `tdd-guide` → `php-reviewer` → `security-reviewer` (review data pribadi UI) 🔐

---

#### Step 3.5 — Laporan & analitik

- **Status**: ⬜ Belum · **Ukuran**: L
- **Tujuan**: Tren keuangan per periode dengan pemisahan tegas konsumsi vs transfer/penyesuaian.
- **Rujukan**: FR-011, mockup #8, PRD §15.
- **Deliverable**:
  - `CashFlowReportService` (pemasukan/pengeluaran/neto memenuhi syarat) +
    `CategoryBreakdownService` (rincian per kategori + perbandingan periode sebelumnya).
  - UI Laporan: pemilih periode, kartu ringkasan, bar chart HTML/CSS arus kas bulanan (12 bulan),
    rincian kategori (bar + tabel aksesibel), perbandingan bulan berjalan vs sebelumnya.
- **Pekerjaan utama**:
  1. Services + unit test totals vs query sumber; eksklusi transfer & adjustment.
  2. UI + tabel alternatif semua grafik (wajib aksesibel) + empty state periode kosong.
  3. Perubahan periode memperbarui seluruh nilai konsisten.
- **Kriteria penerimaan** (FR-011):
  - Total laporan cocok dengan daftar transaksi periode yang sama (feature test rekonsiliasi).
  - Periode kosong menampilkan keadaan kosong informatif.
  - Grafik punya label + alternatif teks/tabel.
- **Pengujian**: unit/feature rekonsiliasi laporan; feature periode & empty state.
- **Rantai agen**: `tdd-guide` → `php-reviewer`.

---

#### Step 3.6 — Ekspor CSV 🔐

- **Status**: ⬜ Belum · **Ukuran**: M
- **Tujuan**: Ekspor transaksi terfilter yang aman (formula injection) dan sesuai otorisasi.
- **Rujukan**: FR-012, PRD §12.5.
- **Deliverable**:
  - `TransactionCsvExporter` (streamed) dengan kolom & format terdokumentasi: `id`, `tanggal`
    (YYYY-MM-DD), `jenis`, `deskripsi`, `kategori`, `akun_sumber`, `akun_tujuan`, `nominal`
    (string desimal 2 digit, titik), `status`, `catatan`, `dibuat_pada` (ISO-8601).
  - Sanitasi formula injection: sel berawalan `=`, `+`, `-`, `@`, TAB, CR diberi prefix `'`.
  - Filter sama dengan halaman Aktivitas + otorisasi kepemilikan + rate limit ekspor.
  - Tombol "Ekspor CSV" (mockup #8) dengan filter aktif ditampilkan.
- **Pekerjaan utama**:
  1. Exporter + unit test sanitizer (semua prefix berbahaya).
  2. Feature test: jumlah baris & total cocok dengan kueri terfilter; user lain tidak bisa.
  3. Rate limit endpoint ekspor.
- **Kriteria penerimaan** (FR-012):
  - Baris & total ekspor cocok dengan kueri terfilter.
  - Teks menyerupai formula ditangani aman (bukti tes).
  - Hanya pemilik data yang dapat mengekspor.
- **Pengujian**: unit sanitizer; feature ekspor + filter + otorisasi + throttle.
- **Rantai agen**: `tdd-guide` → `security-reviewer` → `php-reviewer` 🔐

---

#### Step 3.7 — Panel Filament terproteksi 🔐

- **Status**: ⬜ Belum · **Ukuran**: L
- **Tujuan**: Panel pemeliharaan tepercaya di `/admin` yang **tidak bisa** melewati aturan domain.
- **Rujukan**: FR-016, PRD §11.5, ADR-005.
- **Deliverable**:
  - Filament 5 terpasang (persetujuan dependency); panel `/admin`.
  - `User implements FilamentUser` → `canAccessPanel()` hanya untuk pemilik (akun provisioning
    step 1.1); non-owner → 403.
  - Resource terbatas: Users (read-only), Accounts & Categories (view/list + arsip via action),
    Transactions (list/view + aksi Void via `VoidTransaction`), Audit Logs (read-only).
  - Bulk delete & create langsung transaksi **dinonaktifkan**; aksi memakai Action/Service yang sama.
  - Verifikasi command Filament resmi dari dokumentasi versi terpasang sebelum instalasi
    (PRD §11.1 catatan versi).
- **Pekerjaan utama**:
  1. Instalasi + panel provider + gate akses + tes 403 untuk non-owner.
  2. Resources + policy terpasang (policy yang sama dengan Blade).
  3. Aksi void dari panel → `VoidTransaction` → audit yang sama.
- **Kriteria penerimaan** (FR-016):
  - Permintaan panel dari pengguna tidak terautentikasi/tidak berwenang ditolak.
  - Kueri resource dibatasi izin; aksi tidak dapat melewati validasi transaksi/aturan saldo.
  - Aksi admin berdampak penting tercatat (audit).
- **Pengujian**: feature akses panel (200/403), aksi void via panel memakai service, pembatasan bulk.
- **Rantai agen**: `tdd-guide` → `security-reviewer` → `php-reviewer` 🔐

---

### FASE 4 — Kualitas, Operasional & Rilis

Kriteria keluar fase (PRD §19): daftar periksa rilis selesai; tidak ada defect kritis/tinggi
terbuka; simulasi restore berhasil; keterbatasan yang diketahui terdokumentasi.

---

#### Step 4.1 — Audit keamanan menyeluruh & perbaikan 🔐

- **Status**: ⬜ Belum · **Ukuran**: L
- **Tujuan**: Menutup seluruh skenario ancaman PRD §12.5 dengan bukti uji.
- **Rujukan**: PRD §12, §12.5, §16.2.
- **Deliverable**: laporan temuan (severity-ranked) + perbaikan + regression test untuk setiap fix.
- **Pekerjaan utama** (checklist minimum):
  1. IDOR: uji negatif menyeluruh untuk setiap resource (ubah ID → 404/403).
  2. CSRF aktif; request perubahan data tanpa token ditolak.
  3. XSS: tidak ada `{!! !!}` dengan input pengguna tanpa sanitasi.
  4. SQL injection pada pencarian/filter (binding + LIKE escape).
  5. Formula injection CSV (sudah diuji di 3.6; buktikan ulang end-to-end).
  6. Double-post (2.3) dan race condition transfer/pembayaran (lock + transaction).
  7. Kebocoran `.env`/log/dump; `APP_DEBUG=false` produksi; cookie aman; HTTPS/trusted proxy
     (konfigurasi didokumentasikan, penuh di 4.4).
  8. Rate limit: login & ekspor.
  9. Dependency audit: `composer audit`.
- **Kriteria penerimaan**: 0 temuan kritis/tinggi terbuka; setiap temuan tinggi punya tes regresi.
- **Pengujian**: suite penuh + tes regresi keamanan baru.
- **Rantai agen**: `security-reviewer` → `php-reviewer` 🔐

---

#### Step 4.2 — Audit aksesibilitas & responsif final

- **Status**: ⬜ Belum · **Ukuran**: M
- **Tujuan**: Memastikan standar §15 terpenuhi di seluruh layar (bukan hanya Fase 2).
- **Deliverable**: hasil checklist + perbaikan.
- **Pekerjaan utama**: landmark/heading semantik; label & deskripsi form; navigasi keyboard seluruh
  alur; kontras; status tidak bergantung warna; grafik punya ringkasan/tabel; viewport 360–desktop
  seluruh layar inti.
- **Kriteria penerimaan**: checklist §15 lulus; known-issue (jika ada) terdokumentasi dengan
  mitigasi.
- **Rantai agen**: `code-reviewer` + inspeksi manual.

---

#### Step 4.3 — Backup, restore & dokumentasi operasional

- **Status**: ⬜ Belum · **Ukuran**: M
- **Tujuan**: Kehilangan data adalah risiko tinggi PRD §20 — backup baru dianggap andal setelah
  restore terbukti.
- **Rujukan**: PRD §12.4, §13, §17, ADR-007.
- **Deliverable**:
  - `docs/operations/backup-restore.md`: frekuensi, retensi, enkripsi, keamanan file backup,
    prosedur restore langkah demi langkah (mariadb-dump/mariadb).
  - Script operasional opsional `scripts/backup-db.sh` + `scripts/restore-db.sh` (mode dry-run).
  - Bukti simulasi restore di database uji (`mymoney_restore_test`).
- **Pekerjaan utama**:
  1. Tulis prosedur + script; jalankan simulasi restore penuh (backup → restore → verifikasi
     hitungan rekonsiliasi).
  2. Catat hasil verifikasi (tanggal, ukuran, checksum).
- **Kriteria penerimaan**: restore berhasil diuji di lingkungan uji; prosedur dapat diikuti orang
  lain tanpa pengetahuan tersembunyi.
- **Pengujian**: simulasi manual terdokumentasi.
- **Rantai agen**: `general` → `architect` (review ADR-007).

---

#### Step 4.4 — Persiapan deployment & daftar periksa rilis

- **Status**: ⬜ Belum · **Ukuran**: M
- **Tujuan**: Checklist rilis PRD §18 terisi dengan bukti; keputusan OD-02 difinalisasi.
- **Deliverable**: `docs/operations/deployment.md` + checklist rilis terisi.
- **Pekerjaan utama**:
  1. Konfigurasi produksi: `APP_DEBUG=false`, `APP_ENV=production`, HTTPS paksa, cookie
     `secure`+`http_only`+`same_site`, trusted proxies/hosts, security headers, CSP dasar.
  2. `php artisan config:cache route:cache view:cache` dan verifikasi.
  3. Scheduler/queue: MVP tanpa task wajib; dokumentasikan cara menyalakan queue bila dipakai.
  4. Health check `/up` + langkah smoke pasca-deploy.
  5. Finalisasi OD-02 (hosting) bersama pemilik.
- **Kriteria penerimaan**: seluruh item checklist rilis §18 terisi/terverifikasi.
- **Rantai agen**: `architect` → `security-reviewer` 🔐

---

#### Step 4.5 — Uji penerimaan akhir (Lampiran A)

- **Status**: ⬜ Belum · **Ukuran**: M
- **Tujuan**: Membuktikan MVP memenuhi daftar periksa penerimaan PRD Lampiran A sebelum dianggap
  selesai.
- **Deliverable**: checklist Lampiran A tercentang + catatan keterbatasan yang diketahui + tag
  rilis `v1.0.0-mvp` (setelah disetujui).
- **Pekerjaan utama**:
  1. Jalankan suite penuh di database bersih + seed kategori; semua hijau.
  2. Jalankan smoke manual alur inti 360px: login → akun → kategori → pemasukan → pengeluaran →
     transfer → anggaran → tagihan → target → Safe-to-Spend → laporan → ekspor → Filament.
  3. Verifikasi ulang butir Lampiran A satu per satu (mis. "transfer dikecualikan dari total
     pemasukan/pengeluaran", "pembayaran tagihan tidak membuat transaksi ganda", "Safe-to-Spend
     menampilkan asumsi").
  4. Dokumentasikan keterbatasan diketahui.
- **Kriteria penerimaan**: Lampiran A selesai; tidak ada defect kritis/tinggi; tag rilis disetujui
  pemilik.
- **Rantai agen**: `general` → `code-reviewer`.

---

## 6. Matriks Ketertelakan Requirement → Step

| Requirement (PRD) | Prioritas | Step pelaksana | Bukti verifikasi |
|---|---|---|---|
| FR-001 Autentikasi & sesi | P0 | 1.1 | Feature test auth, throttle, log keamanan |
| FR-002 Profil & preferensi | P0 | 1.1 (+ `PaydaySchedule`) | Feature profil; unit gajian |
| FR-003 Akun + saldo + rekonsiliasi | P0 | 1.3, 1.7 | Feature CRUD/arsip/rekonsiliasi; unit kalkulator |
| FR-004 Kategori | P0 | 1.4 | Feature CRUD/arsip; seeder idempoten |
| FR-005 Transaksi & transfer & void | P0 | 1.5, 1.6, 1.7, 2.2, 2.3 | Unit dampak; feature transfer rollback; idempotensi |
| FR-006 Dashboard | P0 | 2.1 | Feature rekonsiliasi metrik |
| FR-007 Anggaran | P0 | 3.1 | Unit periode; feature CRUD & duplikasi |
| FR-008 Tagihan | P0 | 3.2 | Feature pay/cancel; unit overdue |
| FR-009 Target tabungan | P0 | 3.3 | Unit progres & anti-hitung-ganda |
| FR-010 Safe-to-Spend | P0 | 3.4 | Unit positif/nol/negatif + double-count |
| FR-011 Laporan | P0 | 3.5 | Feature/unit rekonsiliasi laporan |
| FR-012 Ekspor CSV | P0 | 3.6 | Feature ekspor; unit sanitizer |
| FR-013 Aset & kekayaan bersih | P1 | — (di luar MVP) | Model data tidak menghalangi |
| FR-014 Transaksi berulang | P1 | — (di luar MVP) | — |
| FR-015 Impor CSV | P1 | — (di luar MVP) | — |
| FR-016 Panel Filament | P0 (terbatas) | 3.7 | Feature akses + aksi domain |
| §7.3 UX mobile-first | P0 | 0.4, 2.4, 4.2 | Checklist QA |
| §12 Keamanan | P0 | 1.1, 1.6, 2.3, 3.6, 3.7, 4.1 | Audit + regression test |
| §13 Keandalan data | P0 | 1.6, 2.3, 3.2 | Test atomicity & idempotensi |
| §16 Strategi pengujian | P0 | Semua step | Gate G1 |
| Lampiran A Acceptance | P0 | 4.5 | Checklist final |

---

## 7. Definition of Done & Gate Kualitas

### 7.1 DoD per step (ringkas — detail §4.9)

- [ ] Kriteria penerimaan step terbukti (test + bukti manual bila relevan)
- [ ] Gate G1–G6 lulus (test, pint, statik, kriteria, review, kebersihan diff)
- [ ] Kasus tepi & negatif diuji (otorisasi, batas waktu, nilai ekstrem)
- [ ] Keadaan kosong/memuat/error/sukses tertangani di UI
- [ ] Tidak ada data sensitif di log; tidak ada `dd()`/`dump()` tertinggal
- [ ] ADR/PHPDoc diperbarui bila perilaku atau keputusan berubah
- [ ] Status step di dokumen ini diperbarui (`⬜ → 🟨 → ✅`)

### 7.2 Gate rilis MVP (sebelum tag `v1.0.0-mvp`)

- [ ] Seluruh suite hijau di database bersih (PRD Lampiran A)
- [ ] 0 temuan keamanan kritis/tinggi terbuka (4.1)
- [ ] Simulasi restore berhasil (4.3)
- [ ] Checklist deployment §18 lengkap (4.4)
- [ ] Keterbatasan diketahui terdokumentasi (4.5)

---

## 8. Perlu Konfirmasi Pemilik

Keputusan berikut sudah diberi rekomendasi bawaan; konfirmasi diperlukan sebelum step terkait
dieksekusi agar tidak ada asumsi diam-diam (PRD §21).

| # | Pertanyaan | Rekomendasi bawaan | Dipakai di step |
|---|---|---|---|
| Q1 | Adopsi seluruh keputusan OD-01…OD-12 & DT-01…DT-14 pada §2? | Ya — sesuai tabel | Semua |
| Q2 | Database pengujian: MySQL `mymoney_testing` atau SQLite in-memory? | **MySQL** (fidelity DECIMAL/JSON lebih tinggi) | 0.3 |
| Q3 | Scaffolding auth: Laravel Breeze dikustom atau auth manual? | **Breeze** (resmi, cepat, aman) | 1.1 |
| Q4 | Persetujuan dependency: `laravel/breeze`, `filament/filament`, `brick/math`, (opsional) `larastan/larastan`? | Setujui; `brick/math` sudah tersedia transitif | 1.2, 3.7, 1.1 |
| Q5 | Nama database: tetap `mymoney` atau diubah `monetra`? | Tetap `mymoney` (sesuai `.env` saat ini, mengurangi perubahan) | 0.1 |
| Q6 | Grafik laporan: tanpa library (HTML/CSS + tabel) dulu? | Ya — tambah library hanya bila perlu | 3.5 |

---

## 9. Risiko & Mitigasi Implementasi

Risiko produk sudah ada di PRD §20; berikut risiko tambahan spesifik **implementasi**:

| Risiko | Dampak | Mitigasi |
|---|---|---|
| MariaDB 11.8 berbeda perilaku halus dari MySQL 8 (JSON type, SQL mode) | Sedang | Uji di MariaDB lokal sejak Fase 1; hindari fitur eksklusif MySQL 8; ADR-001 mencatat |
| Tes SQLite memberi rasa aman palsu (strict mode/DECIMAL berbeda) | Tinggi | DT-03: test pada MariaDB `mymoney_testing` |
| Scope creep Filament (mengubah panel menjadi UI kedua) | Tinggi | ADR-005 membatasi resource & aksi; review 3.7 |
| Race condition transfer/pembayaran (klik/job paralel) | Tinggi | `DB::transaction` + unique constraint + idempotency key (2.3) + tes |
| Keputusan domain (rumus Safe-to-Spend, biaya transfer) berubah di tengah jalan | Tinggi | ADR-006 dikunci sebelum 3.4; perubahan harus lewat update ADR |
| Utang teknis menumpuk karena step terlalu besar | Sedang | Ukuran step S/M/L; pecah bila melebihi L |
| Kebocoran kredensial ke repo/log | Tinggi | Gate G6; larangan log §4.5; `.env` di `.gitignore` (0.2) |
| "Vibe coding" tanpa tes pada jalur uang | Tinggi | Gate G1 wajib; jalur uang wajib unit test sebelum UI (Fase 1) |
| Timezone/late-month bug | Sedang | `PlanningPeriod` + `PaydaySchedule` terpusat + tes batas (1.1, 2.1, 3.1) |
| Dependency approval tertunda | Rendah | Fase 0 tidak bergantung dependency baru; pengajuan dilakukan sebelum 1.1 |

---

## 10. Milestone

| Milestone | Isi | Definisi tercapai |
|---|---|---|
| **M1 — Fondasi benar & teruji** | Fase 0 + Fase 1 selesai | Semua tes hijau; auth privat; saldo awal & transaksi dapat direkonsiliasi; tabel & service keuangan inti terbentuk |
| **M2 — Penggunaan harian** | Fase 2 selesai | Dashboard dapat direkonsiliasi; alur tambah → aktivitas nyaman di 360px; anti double-submit terbukti |
| **M3 — Perencanaan lengkap** | Fase 3 selesai | Anggaran, tagihan, target, Safe-to-Spend (tanpa hitung ganda), laporan & ekspor, panel admin terproteksi |
| **M4 — Rilis MVP** | Fase 4 selesai | Checklist Lampiran A selesai; 0 temuan kritis/tinggi; restore terbukti; tag `v1.0.0-mvp` |

> Kutipan penutup dari PRD Lampiran B:
> **"Milestone pertama bukan dashboard yang indah. Milestone pertama adalah fondasi keuangan yang
> benar dan teruji, yang dapat dipercaya oleh dashboard."**

---

*Dokumen ini adalah baseline hidup: perbarui status step, ADR, dan keputusan saat eksekusi.
Perubahan scope wajib melalui PRD terlebih dahulu.*
