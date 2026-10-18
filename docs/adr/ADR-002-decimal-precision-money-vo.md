# ADR-002: Representasi Nilai Uang Presisi DECIMAL(19,2) & Value Object Money

- **Status**: Diterima (Accepted)
- **Tanggal**: 2026-10-17
- **Pengambil Keputusan**: Tim Rekayasa Monetra

---

## 1. Konteks
Sistem keuangan memerlukan presisi matematika mutlak. Kesalahan pembulatan IEEE-754 pada floating-point (`float`/`double`) dapat mengakibatkan ketidaksesuaian saldo akumulatif atau kehilangan nilai sen pada laporan keuangan.

## 2. Keputusan
1. Semua kolom moneter di database menggunakan tipe `DECIMAL(19,2)`.
2. Semua manipulasi uang di lapisan kode PHP **wajib** menggunakan Immutable Value Object `App\Domain\Money\Money` yang dibangun di atas pustaka matematika arbitrer `brick/math` (`BigDecimal`).
3. Dilarang keras menggunakan tipe data `float` atau `double` di baris kode mana pun dalam kalkulasi atau penyimpanan uang.
4. Pembulatan terpusat pada `RoundingPolicy` dengan metode `RoundingMode::HALF_UP` dan presisi 2 desimal.
5. Pemformatan tampilan menggunakan `NumberFormatter` lokal Indonesia (`id_ID`) dengan pola `Rp 12.450.000`.

## 3. Alternatif yang Dipertimbangkan
- *Primitive Float/Double*: Ditolak keras karena ketidakakuratan biner representasi pecahan desimal.
- *Penyimpanan Integer dalam satuan Sen*: Ditolak karena konversi manual antar-lapisan rentan salah kaprah, dan format DECIMAL(19,2) di database SQL lebih mudah diaudit langsung oleh manusia melalui query SQL maupun phpMyAdmin.

## 4. Konsekuensi
- **Positif**:
  - Nol resiko kesalahan pembulatan mengambang.
  - Nilai objek immutable mencegah perubahan state sampingan (*side-effects*).
- **Negatif / Mitigasi**:
  - Seluruh model Eloquent yang memiliki kolom uang harus memakai `MoneyCast` untuk casting otomatis string ↔ `Money`.
