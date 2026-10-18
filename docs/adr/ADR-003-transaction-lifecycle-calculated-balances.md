# ADR-003: Siklus Hidup Transaksi (Posted/Voided), Transfer Atomik, dan Saldo Dihitung

- **Status**: Diterima (Accepted)
- **Tanggal**: 2026-10-17
- **Pengambil Keputusan**: Tim Rekayasa Monetra

---

## 1. Konteks
Pada sistem keuangan, penghapusan baris data transaksi secara diam-diam (*hard delete*) merusak integritas audit dan riwayat pergerakan saldo. Selain itu, menyimpan saldo statis di kolom tabel akun (*cached balance*) rentan mengalami *race condition* dan desinkronisasi data.

## 2. Keputusan
1. **Siklus Status Transaksi**:
   - Transaksi hanya memiliki dua status: `posted` (aktif dan memengaruhi saldo) dan `voided` (dibatalkan).
   - Pembatalan transaksi tidak menghapus baris dari database, melainkan mengubah status menjadi `voided` dengan mencatat `void_reason` dan `voided_at`.
   - Tidak menggunakan *soft delete* (`deleted_at`) sebagai mekanisme pembatalan keuangan.
2. **Transfer Atomik**:
   - Transfer antar-akun dicatat sebagai satu baris transaksi dengan `source_account_id` dan `destination_account_id`.
   - Dieksekusi dalam satu transaksi database (`DB::transaction`). Transfer memindahkan saldo antar-akun tetapi tidak mengubah total agregat kekayaan.
3. **Model Saldo Dihitung (*Calculated Balances*)**:
   - Saldo akun dihitung on-the-fly oleh `AccountBalanceService` melalui query agregasi SQL: `opening_balance + Σ(posted transactions)`.
   - Penyesuaian fisik dilakukan melalui `ReconcileAccount` yang membuat transaksi bertipe `adjustment` dengan `adjustment_direction`.

## 3. Alternatif yang Dipertimbangkan
- *Cached balance column di tabel accounts*: Ditolak untuk MVP karena rawan desinkronisasi ketika terjadi void atau koreksi masa lalu.
- *Double-entry bookkeeping penuh (Ledger Debet/Kredit murni)*: Ditolak untuk MVP karena menambah kompleksitas model untuk kebutuhan pengguna pribadi tunggal.

## 4. Konsekuensi
- **Positif**:
  - Saldo selalu mencerminkan kenyataan transaksi sebenarnya (100% konsisten).
  - Jejak audit tetap utuh selamanya.
- **Negatif / Mitigasi**:
  - Kueri agregasi membutuhkan index komposit `(source_account_id, transaction_date)` dan `(user_id, status)` untuk menjaga performa tetap optimal (< 10ms).
