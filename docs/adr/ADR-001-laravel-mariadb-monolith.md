# ADR-001: Arsitektur Monolit Modular Laravel 13 & Kompatibilitas MariaDB 11.8 / MySQL 8

- **Status**: Diterima (Accepted)
- **Tanggal**: 2026-10-17
- **Pengambil Keputusan**: Tim Rekayasa Monetra

---

## 1. Konteks
Aplikasi Monetra dirancang sebagai sistem pengelolaan keuangan pribadi untuk satu pengguna privat dengan fokus utama pada akurasi data keuangan, auditabilitas, kesederhanaan operasional, dan pemeliharaan jangka panjang oleh developer tunggal. Kami perlu memutuskan pola arsitektur aplikasi dan basis data relasional yang digunakan.

## 2. Keputusan
1. Mengadopsi arsitektur **Monolit Modular Laravel 13** yang dirender di server (Server-Side Rendering dengan Laravel Blade dan Tailwind CSS 4).
2. Memisahkan tanggung jawab mutasi data ke dalam **Actions (Write)** dan kalkulasi/agregat ke dalam **Services (Read)**.
3. Menggunakan **MariaDB 11.8 / MySQL 8.0+** sebagai basis data relasional dengan engine InnoDB, charset `utf8mb4`, collation `utf8mb4_unicode_ci`, dan driver `mysql` pada Laravel.

## 3. Alternatif yang Dipertimbangkan
- *Microservices*: Ditolak karena menambah kompleksitas jaringan, konsistensi data terdistribusi (2PC/Saga), dan overhead infrastruktur tanpa kebutuhan nyata.
- *Single Page Application (SPA) React/Vue terpisah*: Ditolak karena menambah duplikasi state management dan latensi API tanpa memberikan keuntungan signifikan dibanding Blade + Alpine.js untuk skala pengguna privat.
- *SQLite di Produksi*: Ditolak karena batasan strict typing pada kolom DECIMAL dan keterbatasan fitur konkurensi database relasional penuh.

## 4. Konsekuensi
- **Positif**:
  - Semua operasi transaksi berada dalam satu lingkungan transaksi ACID lokal (`DB::transaction`).
  - Deploy dan backup data sangat sederhana (satu server dan satu dump SQL).
  - Kode bersih, minim dependensi eksternal, dan mudah diuji secara otomatis.
- **Negatif / Mitigasi**:
  - Perlu disiplin menjaga lapisan controller tetap tipis (≤ 10 baris) agar logika domain tidak bocor ke presentation layer.
