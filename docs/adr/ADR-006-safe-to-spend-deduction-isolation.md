# ADR-006: Asumsi Kalkulasi Safe-to-Spend & Kebijakan Isolasi Deduksi

- **Status**: Diterima (Accepted)
- **Tanggal**: 2026-10-17
- **Pengambil Keputusan**: Tim Rekayasa Monetra

---

## 1. Konteks
Fitur pembeda utama Monetra adalah indikator **Safe-to-Spend** (Bisa belanja sampai berapa sebelum gajian berikutnya). Perhitungan ini sering kali salah pada aplikasi pencatat keuangan karena terjadi *double counting* (pengurangan ganda)—misalnya saat pengguna sudah memindahkan uang tabungan ke rekening khusus tabungan, aplikasi tetap mengurangkan kembali target tabungan dari total saldo spendable.

## 2. Keputusan
1. **Formula Komputasi Safe-to-Spend**:
   ```
   Safe-to-Spend = Total Saldo Akun Spendable
                 − Tagihan Belum Lunas s/d Tanggal Rencana
                 − Alokasi Target Belum Dieksekusi
   ```
2. **Kebijakan Akun Spendable**:
   - Akun memiliki flag boolean `include_in_safe_to_spend`.
   - Akun bertipe `investment` (investasi/saham/reksadana) secara default bernilai `false` (dana tidak cair/tidak siap belanja harian).
3. **Pencegahan Pengurangan Ganda (Anti Double-Deduction)**:
   - Jika kontribusi tabungan telah dilakukan melalui transfer dana riil (mengurangi saldo rekening operasional), sistem tidak mengurangkannya kembali dari sisa alokasi.
   - Tagihan yang sudah berstatus `paid` (lunas) tidak dimasukkan lagi ke dalam pengurang tagihan mendatang.
4. **Transparansi Asumsi**:
   - Antarmuka selalu menyajikan rincian komponen (saldo awal, daftar tagihan pemotong, dan sisa alokasi) beserta tanggal perencanaan yang digunakan.
   - Menyertakan disclaimer: *"Estimasi perencanaan, bukan nasihat keuangan formal."*

## 3. Alternatif yang Dipertimbangkan
- *Mengurangi seluruh sisa anggaran bulanan*: Ditolak karena anggaran belum tentu berupa uang kas yang wajib keluar sebelum hari gajian tertentu, melainkan batas plafon. Mengurangkan plafon anggaran akan membuat Safe-to-Spend terlalu pesimistis.

## 4. Konsekuensi
- **Positif**:
  - Angka Safe-to-Spend sangat akurat, realistis, dan dapat dipercaya pengguna.
  - Membantu pengguna mengambil keputusan belanja harian dengan tenang.
