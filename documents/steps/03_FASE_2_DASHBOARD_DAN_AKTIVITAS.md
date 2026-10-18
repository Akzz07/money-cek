# Fase 2: Dashboard & Aktivitas Harian
**Status**: ✅ Selesai (100%) · **Target Waktu**: 2–3 Hari Kerja

Fase 2 menghubungkan fondasi keuangan Fase 1 ke pengalaman pengguna harian yang responsif, nyaman, dan bebas dari duplikasi input data.

---

## Step 2.1 — Dashboard Inti & Ringkasan Keuangan
- **Ukuran**: L (1–1.5 hari)
- **Status**: ✅ Selesai
- **Tujuan**: Menghadirkan layar Beranda persis [Monetra Finance App UI Showcase.png](file:///Users/akmalzulfikar/Testing/documents/Monetra%20Finance%20App%20UI%20Showcase.png) Layar #2 yang menjawab: *"Berapa total uang saya?"* dan *"Bagaimana arus kas bulan ini berjalan?"*.
- **Rujukan**: PRD FR-006, §7.2, §9.3; Layar #2 mockup.

### 1. Spesifikasi Teknis
- Service: `App\Services\Dashboard\DashboardMetricsService`:
  - DTO: `DashboardMetrics` (immutable):
    - `totalBalance`: Money (saldo dari akun dengan flag `include_in_total_assets = true`).
    - `monthlyIncome`: Money (Σ income `posted` dalam periode bulan berjalan).
    - `monthlyExpense`: Money (Σ expense `posted` dalam periode bulan berjalan).
    - `netCashFlow`: Money (`monthlyIncome` − `monthlyExpense`).
    - `recentTransactions`: Collection (5 transaksi `posted` terbaru).
    - `upcomingBillsPlaceholder`: Array (placeholder jujur sebelum Fase 3 selesai).
    - `safeToSpendPlaceholder`: Array (placeholder banner biru sebelum Fase 3 selesai).
- Komponen UI:
  - Header: Sapaan nama pengguna ("Halo, {{ Auth::user()->name }} 👋 Semangat mengelola keuanganmu hari ini!").
  - Kartu Total Saldo (Warna biru `--color-brand-primary`):
    - Nilai saldo utama + ikon toggle sembunyikan nominal (Alpine.js state).
  - Sub-Kartu Berdampingan:
    - **Pemasukan Bulan Ini** (ikon panah hijau naik): live query.
    - **Pengeluaran Bulan Ini** (ikon panah merah turun): live query.
  - Banner Safe-to-Spend (dengan penanda jelas jika masih dalam kalkulasi dasar likuiditas kas).
  - Section Tagihan Terdekat & Aktivitas Terbaru lengkap dengan tombol "Lihat Semua".

### 2. Kriteria Penerimaan & Verifikasi
- [x] Total Saldo di Dashboard terbukti identik dengan penjumlahan saldo akun pada `AccountBalanceService`.
- [x] Transfer antar-akun dan transaksi `adjustment` tidak masuk ke dalam perhitungan pemasukan atau pengeluaran bulanan.
- [x] Fitur yang belum aktif (Fase 3) tidak menampilkan angka palsu, melainkan state "belum ada data" yang transparan.

---

## Step 2.2 — Aktivitas Lanjutan: Cari, Filter, Paginasi & Detail
- **Ukuran**: M (1 hari)
- **Status**: ✅ Selesai
- **Tujuan**: Membangun riwayat transaksi lengkap mengacu pada Layar #4 [Monetra Finance App UI Showcase.png](file:///Users/akmalzulfikar/Testing/documents/Monetra%20Finance%20App%20UI%20Showcase.png) dengan pencarian deskripsi, filter multi-dimensi, paginasi, dan detail transaksi.
- **Rujukan**: PRD FR-005, §10.4; Layar #4 mockup.

### 1. Spesifikasi Teknis
- DTO Filter: `App\DTOs\TransactionFilter`:
  - `search`: ?string (pencarian nama/deskripsi dengan sanitasi LIKE).
  - `accountId`: ?int.
  - `categoryId`: ?int.
  - `type`: ?TransactionType.
  - `startDate`: ?CarbonImmutable.
  - `endDate`: ?CarbonImmutable.
- Optimasi Database:
  - Index komposit: `INDEX (user_id, transaction_date DESC)`.
  - Eager loading relasi `category`, `sourceAccount`, `destinationAccount` untuk mencegah masalah N+1 queries.
- UI Chips & Paginasi:
  - Filter chips mobile: "Semua Akun v", "Kategori v", "Rentang Tanggal v", type chips ("Semua", "Pengeluaran", "Pemasukan", "Transfer").
  - Pengelompokan baris transaksi per tanggal (misal: "17 Apr 2025", "16 Apr 2025").
  - Paginasi 25 item per halaman dengan mempertahankan query string filter aktif (`withQueryString()`).
  - Modal detail transaksi dengan integrasi aksi void transparan.

### 2. Kriteria Penerimaan & Verifikasi
- [x] Pengujian query count membuktikan tidak terjadi N+1 saat merender 25 transaksi beserta relasi akun dan kategorinya.
- [x] Filter bertahan ketika pengguna berpindah ke halaman paginasi berikutnya.
- [x] Input pencarian dengan karakter khusus (`%`, `_`, `'`) tidak memicu SQL error atau injection.

---

## Step 2.3 — Idempotensi & Anti Double-Submit 🔐
- **Ukuran**: M (0.5–1 hari)
- **Status**: ✅ Selesai
- **Tujuan**: Mencegah terjadinya duplikasi transaksi saat pengguna menekan tombol "Simpan" berkali-kali atau mengalami koneksi lambat.
- **Rujukan**: PRD §12.5, §13, DT-09.

### 1. Spesifikasi Teknis
- Kolom Tabel: `client_request_id` (VARCHAR(36) UNIQUE NULLABLE) pada tabel `transactions`.
- Arsitektur:
  - Setiap form transaksi menghasilkan UUID v4 sekali-pakai pada input tersembunyi `<input type="hidden" name="client_request_id" value="{{ Str::uuid() }}">`.
  - Jika request dengan `client_request_id` yang sama masuk kembali:
    - Sistem mendeteksi duplikasi melalui database constraint dan menangkap `UniqueConstraintViolationException`.
    - Mengembalikan respons sukses dengan transaksi yang sudah tersimpan sebelumnya tanpa membuat baris baru.
  - Lapisan UI (UX): Tombol submit otomatis dinonaktifkan (`disabled`) seketika saat form dikirim menggunakan Alpine.js (`x-data="{ submitting: false }" @submit="submitting = true"`) dan menampilkan spinner indikator.

### 2. Kriteria Penerimaan & Verifikasi
- [x] Pengujian automated test: Dua HTTP POST request dengan payload identik dan `client_request_id` sama hanya menghasilkan **tepat satu** baris di tabel `transactions`.
- [x] Pengguna tidak mendapatkan error layar 500 jika koneksi terputus dan browser melakukan auto-retry.

---

## Step 2.4 — QA Responsif Mobile-First & Aksesibilitas Fase 2
- **Ukuran**: M (1 hari)
- **Status**: ✅ Selesai
- **Tujuan**: Memastikan seluruh alur yang dibangun pada Fase 0 s/d Fase 2 telah memenuhi standar aksesibilitas dan responsivitas seluler sebelum melangkah ke Fase 3.
- **Rujukan**: PRD §7.3, §15, §16.3.

### 1. Checklist Pengujian
1. **Viewport Verification**:
   - Uji layar 360px (lebar layar terkecil umum di Indonesia).
   - Pastikan tidak ada horizontal scrolling pada halaman mana pun.
2. **Touch Targets**:
   - Seluruh elemen interaktif (tombol, dropdown, chips filter, bottom nav tabs) memiliki ukuran fisik minimal 44 × 44 px.
3. **Navigasi Keyboard & Screen Reader**:
   - Seluruh form dan modal dapat dinavigasi menggunakan tombol `Tab` dan `Enter/Esc`.
   - Warna tidak menjadi satu-satunya indikator status (dilengkapi teks/ikon).

### 2. Kriteria Penerimaan & Verifikasi
- [x] Laporan pengujian responsif pada 360px, 768px, dan 1280px terdokumentasi dan lulus 100%.
- [x] Tidak ada regresi fungsional pada alur Login → Tambah Transaksi → Lihat Dashboard → Lihat Riwayat (diverifikasi oleh `EndToEndUserJourneyTest`).
