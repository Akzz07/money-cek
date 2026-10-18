# Monetra — Master Roadmap & Execution Index
**Panduan Eksekusi Step-by-Step Rekayasa Perangkat Lunak Enterprise**

> **Dokumen Rujukan Utama:**
> - [Monetra_PRD_Bahasa_Indonesia.md](file:///Users/akmalzulfikar/Testing/documents/Monetra_PRD_Bahasa_Indonesia.md) (Sumber Kebenaran Produk)
> - [Monetra_Rencana_Implementasi.md](file:///Users/akmalzulfikar/Testing/documents/Monetra_Rencana_Implementasi.md) (Arsitektur & Spesifikasi Rekayasa)
> - [Monetra Finance App UI Showcase.png](file:///Users/akmalzulfikar/Testing/documents/Monetra%20Finance%20App%20UI%20Showcase.png) (Spesifikasi Desain Visual & Layar Mobile)

---

## 1. Prinsip Rekayasa Enterprise Big Tech

Dalam rekayasa sistem finansial Monetra, kita menerapkan standar ketat perusahaan teknologi terdepan (setara Google / Stripe):
1. **Mathematical Invariant & Zero-Float Policy**:
   Semua operasi moneter wajib beroperasi di `DECIMAL(19,2)` pada basis data dan diwakili oleh Immutable Value Object `Money` (`brick/math`). Tidak ada operasi `float` atau `double` di baris kode mana pun.
2. **Pemisahan Tegas Command (Write) vs Query (Read)**:
   - **Actions (Write)**: Menjalankan satu aksi mutasi bisnis dalam isolasi `DB::transaction`, memvalidasi invarian domain, memicu Domain Events, dan mencatat `audit_logs`.
   - **Services (Read/Compute)**: Menghitung agregat (saldo, Safe-to-Spend, pemakaian anggaran, analitik arus kas) secara deterministik tanpa efek samping ke database.
   - **Controllers**: Lapisan tipis (≤ 10 baris) yang hanya mendelegasikan request ke Action/Service.
3. **Single Source of Truth & Zero Bypass**:
   Semua pintu masuk mutasi data (Blade UI, Filament Admin Panel di `/admin`, maupun CLI Artisan Commands) **wajib** memanggil Action dan Policy yang sama. Tidak ada callback Filament atau controller yang menulis langsung ke database.
4. **Mobile-First High Fidelity**:
   Desain visual mengacu langsung pada 8 layar mockup ([Monetra Finance App UI Showcase.png](file:///Users/akmalzulfikar/Testing/documents/Monetra%20Finance%20App%20UI%20Showcase.png)) menggunakan Tailwind CSS 4 native tokens, responsive 360px tanpa scroll horizontal, dan target sentuh ≥ 44px.

---

## 2. Struktur Modul Langkah Implementasi

Rencana eksekusi dipecah ke dalam 5 dokumen fase tematik:

| Modul Dokumen | Fase | Jumlah Step | Cakupan Utama |
|---|---|:---:|---|
| [01_FASE_0_FONDASI_DAN_LINGKUNGAN.md](file:///Users/akmalzulfikar/Testing/documents/steps/01_FASE_0_FONDASI_DAN_LINGKUNGAN.md) | **Fase 0** | 5 Step | Database & user provisioning, Git baseline, code standards, Tailwind 4 tokens & UI shell, ADRs |
| [02_FASE_1_AUTENTIKASI_DAN_FONDASI_KEUANGAN.md](file:///Users/akmalzulfikar/Testing/documents/steps/02_FASE_1_AUTENTIKASI_DAN_FONDASI_KEUANGAN.md) | **Fase 1** | 7 Step | Breeze custom auth privat, Value Object Money, Akun, Kategori, Transaksi, Transfer atomik, Mesin Saldo & Rekonsiliasi |
| [03_FASE_2_DASHBOARD_DAN_AKTIVITAS.md](file:///Users/akmalzulfikar/Testing/documents/steps/03_FASE_2_DASHBOARD_DAN_AKTIVITAS.md) | **Fase 2** | 4 Step | Dashboard metrik keuangan, Aktivitas lanjutan (filter/search), Idempotensi anti double-submit, QA mobile-first |
| [04_FASE_3_PERENCANAAN_DAN_LAPORAN.md](file:///Users/akmalzulfikar/Testing/documents/steps/04_FASE_3_PERENCANAAN_DAN_LAPORAN.md) | **Fase 3** | 7 Step | Anggaran bulanan, Tagihan & jatuh tempo, Target tabungan, Safe-to-Spend engine, Laporan arus kas, Ekspor CSV aman, Filament 5 |
| [05_FASE_4_KEAMANAN_OPERASIONAL_DAN_RILIS.md](file:///Users/akmalzulfikar/Testing/documents/steps/05_FASE_4_KEAMANAN_OPERASIONAL_DAN_RILIS.md) | **Fase 4** | 5 Step | Audit keamanan menyeluruh, Audit aksesibilitas, Prosedur backup-restore teruji, Checklist deployment, Acceptance test |

---

## 3. Quality Gates (G1 — G6)

Sebelum sebuah step dapat dinyatakan selesai (`✅`) dan di-merge, keenam gerbang kualitas berikut wajib dipenuhi:

```
[Kode Ditulis] ──► G1: Unit & Feature Tests Hijau (php artisan test --compact)
               ──► G2: Format Kode Bersih (vendor/bin/pint --format agent)
               ──► G3: Static Analysis Tanpa Error Baru (phpstan / larastan)
               ──► G4: Kriteria Penerimaan PRD Terbukti (Checklist)
               ──► G5: Code & Security Review (Peer Review)
               ──► G6: Git Hygiene (Zero secret, zero dump/dd, clean diff)
               ──► [Step Selesai & Merge]
```

---

## 4. Matriks Ketertelakan Fitur (PRD Requirement Traceability)

| Requirement | Prioritas | Modul & Step | Status |
|---|:---:|---|:---:|
| **FR-001** Autentikasi & Sesi Privat | P0 | [Fase 1 — Step 1.1](file:///Users/akmalzulfikar/Testing/documents/steps/02_FASE_1_AUTENTIKASI_DAN_FONDASI_KEUANGAN.md#step-11--autentikasi-privat--preferensi-profil) | ✅ Selesai |
| **FR-002** Profil & Preferensi Gajian | P0 | [Fase 1 — Step 1.1](file:///Users/akmalzulfikar/Testing/documents/steps/02_FASE_1_AUTENTIKASI_DAN_FONDASI_KEUANGAN.md#step-11--autentikasi-privat--preferensi-profil) | ✅ Selesai |
| **FR-003** Akun, Saldo & Rekonsiliasi | P0 | [Fase 1 — Step 1.3 & 1.7](file:///Users/akmalzulfikar/Testing/documents/steps/02_FASE_1_AUTENTIKASI_DAN_FONDASI_KEUANGAN.md#step-13--manajemen-akun-keuangan) | ✅ Selesai |
| **FR-004** Kategori Transaksi | P0 | [Fase 1 — Step 1.4](file:///Users/akmalzulfikar/Testing/documents/steps/02_FASE_1_AUTENTIKASI_DAN_FONDASI_KEUANGAN.md#step-14--kategori-pemasukan--pengeluaran) | ✅ Selesai |
| **FR-005** Transaksi, Transfer & Void | P0 | [Fase 1 — Step 1.5, 1.6 & Fase 2 — Step 2.2, 2.3](file:///Users/akmalzulfikar/Testing/documents/steps/02_FASE_1_AUTENTIKASI_DAN_FONDASI_KEUANGAN.md#step-15--transaksi-pemasukan--pengeluaran) | ✅ Selesai |
| **FR-006** Dashboard Keuangan | P0 | [Fase 2 — Step 2.1](file:///Users/akmalzulfikar/Testing/documents/steps/03_FASE_2_DASHBOARD_DAN_AKTIVITAS.md#step-21--dashboard-inti--ringkasan-keuangan) | ✅ Selesai |
| **FR-007** Anggaran Bulanan | P0 | [Fase 3 — Step 3.1](file:///Users/akmalzulfikar/Testing/documents/steps/04_FASE_3_PERENCANAAN_DAN_LAPORAN.md#step-31--anggaran-bulanan--peringatan-ambang) | ✅ Selesai |
| **FR-008** Tagihan & Jatuh Tempo | P0 | [Fase 3 — Step 3.2](file:///Users/akmalzulfikar/Testing/documents/steps/04_FASE_3_PERENCANAAN_DAN_LAPORAN.md#step-32--tagihan--pelacakan-jatuh-tempo) | ✅ Selesai |
| **FR-009** Target Tabungan & Alokasi | P0 | [Fase 3 — Step 3.3](file:///Users/akmalzulfikar/Testing/documents/steps/04_FASE_3_PERENCANAAN_DAN_LAPORAN.md#step-33--target-tabungan--alokasi-dana) | ✅ Selesai |
| **FR-010** Safe-to-Spend Engine | P0 | [Fase 3 — Step 3.4](file:///Users/akmalzulfikar/Testing/documents/steps/04_FASE_3_PERENCANAAN_DAN_LAPORAN.md#step-34--kalkulasi-safe-to-spend-transparan) | ✅ Selesai |
| **FR-011** Laporan & Analitik | P0 | [Fase 3 — Step 3.5](file:///Users/akmalzulfikar/Testing/documents/steps/04_FASE_3_PERENCANAAN_DAN_LAPORAN.md#step-35--laporan-arus-kas--analitik-kategori) | ✅ Selesai |
| **FR-012** Ekspor CSV Aman | P0 | [Fase 3 — Step 3.6](file:///Users/akmalzulfikar/Testing/documents/steps/04_FASE_3_PERENCANAAN_DAN_LAPORAN.md#step-36--ekspor-csv-transaksi-aman) | ✅ Selesai |
| **FR-016** Panel Filament Terproteksi | P0 | [Fase 3 — Step 3.7](file:///Users/akmalzulfikar/Testing/documents/steps/04_FASE_3_PERENCANAAN_DAN_LAPORAN.md#step-37--panel-filament-5-terproteksi) | ✅ Selesai |
| **§12/§13** Audit Keamanan & Keandalan | P0 | [Fase 4 — Step 4.1 & 4.3](file:///Users/akmalzulfikar/Testing/documents/steps/05_FASE_4_KEAMANAN_OPERASIONAL_DAN_RILIS.md#step-41--audit-keamanan-menyeluruh--penutupan-celah) | ✅ Selesai |
| **Lampiran A** Uji Penerimaan Akhir | P0 | [Fase 4 — Step 4.5](file:///Users/akmalzulfikar/Testing/documents/steps/05_FASE_4_KEAMANAN_OPERASIONAL_DAN_RILIS.md#step-45--uji-penerimaan-akhir-acceptance-testing) | ✅ Selesai |

---

## 5. Protokol Memulai Pekerjaan
Ketika dokumen ini dan dokumen sub-fase disetujui:
1. Mulai secara berurutan dari **Step 0.1** di [01_FASE_0_FONDASI_DAN_LINGKUNGAN.md](file:///Users/akmalzulfikar/Testing/documents/steps/01_FASE_0_FONDASI_DAN_LINGKUNGAN.md).
2. Setiap kali menyelesaikan step, jalankan verifikasi otomatis dan ubah status di dokumen terkait menjadi `✅ Selesai`.
3. Tidak melompat antar step tanpa prasyarat selesai.
