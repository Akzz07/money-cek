---
last_updated: 2026-10-17
owner: Produk & Rekayasa Perangkat Lunak
status: Dasar Pengembangan
subtitle: Aplikasi Web Pengelolaan Keuangan Pribadi
title: Monetra --- Dokumen Kebutuhan Produk (PRD)
version: 1.0.0
---

# Monetra --- Dokumen Kebutuhan Produk (PRD)

## Pengendalian Dokumen

  -----------------------------------------------------------------------
  Kolom                               Nilai
  ----------------------------------- -----------------------------------
  Produk                              Monetra (nama kerja)

  Jenis dokumen                       Dokumen Kebutuhan Produk (Product
                                      Requirements Document/PRD)

  Versi                               1.0.0

  Status                              Dasar implementasi; keputusan
                                      bertanda TBD perlu dikonfirmasi

  Pembaca utama                       Pemilik produk, developer, QA, dan
                                      pemelihara aplikasi

  Platform target                     Aplikasi web responsif dengan
                                      pendekatan mobile-first

  Backend                             Laravel

  Database                            MySQL

  UI pengguna                         Laravel Blade

  Panel admin/CMS                     Filament, hanya untuk administrasi
                                      dan pemeliharaan

  Pengguna utama                      Satu pengguna pribadi pada rilis
                                      awal

  Mata uang dan lokal                 IDR / lokal Indonesia sebagai
                                      bawaan

  Zona waktu                          Asia/Jakarta sebagai bawaan; dapat
                                      diubah

  Tujuan dokumen                      Menetapkan ruang lingkup,
                                      kebutuhan, batasan arsitektur,
                                      model data, kriteria penerimaan,
                                      pengujian, keamanan, dan rencana
                                      rilis
  -----------------------------------------------------------------------

> **Catatan implementasi:** PRD ini disusun sebagai pedoman rekayasa
> perangkat lunak dengan praktik dokumentasi, keterlacakan kebutuhan,
> dan kontrol kualitas bergaya enterprise. Dokumen ini tidak mengklaim
> sertifikasi atau kepatuhan formal terhadap kerangka kerja perusahaan
> tertentu. Saat inisialisasi proyek, pastikan versi Laravel, PHP,
> MySQL, dan Filament kompatibel berdasarkan dokumentasi resmi terkini.

## 1. Ringkasan Eksekutif

Monetra adalah aplikasi web pengelolaan keuangan pribadi yang
mengutamakan perangkat seluler. Aplikasi membantu satu pengguna mencatat
aktivitas keuangan, memahami uang yang tersedia, menetapkan batas
pengeluaran, merencanakan tabungan, serta memantau aset dan kewajiban.

Prioritas produk adalah akurasi keuangan, kemudahan pencatatan harian,
penjelasan perhitungan yang transparan, privasi, dan kemampuan pemulihan
data. Rilis pertama menggunakan pencatatan transaksi manual. Integrasi
bank, data pasar otomatis, OCR, dan pendamping keuangan berbasis AI
tidak termasuk dalam ruang lingkup MVP.

Aplikasi menggunakan Laravel untuk logika server, MySQL untuk
penyimpanan data, Blade untuk halaman pengguna, dan Filament untuk layar
administrasi/pemeliharaan bila mempercepat pengembangan. Arsitektur yang
disarankan adalah monolit modular Laravel yang dirender di server,
kecuali ada kebutuhan nyata yang membenarkan kompleksitas tambahan.

### 1.1 Pernyataan produk

**Pahami uangmu. Kendalikan pengeluaranmu. Bangun asetmu.**

### 1.2 Hasil utama yang ingin dicapai

1.  Pengguna dapat menjawab: "Berapa total uang saya di semua akun?"
2.  Pengguna dapat menjawab: "Berapa yang aman saya belanjakan sampai
    gajian berikutnya?"
3.  Pengguna dapat melihat ke mana uang pergi dan apakah pengeluaran
    sesuai anggaran.
4.  Pengguna dapat merencanakan tabungan serta memantau aset dan
    kewajiban tanpa menganggap transfer sebagai pengeluaran.
5.  Pengguna dapat mengekspor dan mencadangkan catatan keuangan
    pribadinya.

## 2. Pernyataan Masalah

Informasi keuangan pribadi sering tersebar di aplikasi bank, dompet
digital, rekening investasi, spreadsheet, dan ingatan pengguna. Saldo
rekening saja tidak menunjukkan tagihan yang belum dibayar, kebutuhan
hidup mendatang, dana yang dialokasikan, atau total kekayaan bersih.
Aplikasi pencatat pengeluaran sederhana mungkin hanya menyimpan riwayat
tanpa membantu pengguna merencanakan langkah berikutnya.

Monetra mengatasi masalah tersebut dengan menggabungkan pencatatan
transaksi, saldo akun, anggaran, tagihan, target tabungan, serta
estimasi transparan bernama **Safe-to-Spend** dalam satu antarmuka.

## 3. Sasaran, Batasan, dan Kriteria Keberhasilan

### 3.1 Sasaran produk

-   Membuat pencatatan transaksi rutin cepat dan nyaman di layar ponsel.
-   Menjaga konsistensi saldo tunai, bank, dompet digital, dan akun
    investasi yang dicatat manual.
-   Membedakan pemasukan, pengeluaran konsumsi, transfer, kewajiban, dan
    perubahan valuasi aset.
-   Menampilkan arus kas bulanan dan kinerja anggaran dengan definisi
    yang mudah dipahami.
-   Menghitung Safe-to-Spend berdasarkan input yang jelas dan mencegah
    pengurangan ganda.
-   Memungkinkan ekspor data dan menyediakan prosedur backup/restore
    yang sudah diuji.
-   Menjaga sistem tetap sederhana agar dapat dirawat oleh satu
    developer.

### 3.2 Di luar ruang lingkup MVP

-   Sinkronisasi otomatis dengan bank, dompet digital, broker, atau
    penyedia pembayaran.
-   Inisiasi pembayaran, transfer uang, atau penyimpanan dana pengguna.
-   Pelaporan pajak, kepatuhan akuntansi formal, penjualan produk
    keuangan, atau rekomendasi investasi.
-   Saran keuangan yang dihasilkan AI.
-   OCR struk, pembacaan email/SMS, atau kategorisasi transaksi
    otomatis.
-   Keuangan rumah tangga bersama, anggaran bersama, tim, atau layanan
    SaaS multi-tenant.
-   Harga saham real-time atau sinkronisasi portofolio broker.
-   Aplikasi native iOS/Android.
-   Akuntansi multi-mata uang pada rilis pertama; model data sebaiknya
    tidak menghalangi dukungan di masa depan.

### 3.3 Metrik keberhasilan

Karena produk awal digunakan secara pribadi, metrik harus praktis dan
dapat diukur.

  ------------------------------------------------------------------------
  Metrik                                 Target awal Metode pengukuran
  --------------------- ---------------------------- ---------------------
  Pencatatan transaksi                  \<= 30 detik Uji kegunaan manual
  umum                                               

  Waktu muat dashboard       \<= 2 detik pada p95 di Pemantauan
  pada volume data          pengujian mirip produksi aplikasi/browser
  normal                                             

  Akurasi perhitungan      100% untuk kasus uji yang Pengujian otomatis
  saldo                                didefinisikan 

  Bug transfer                  0 bug yang diketahui Pengujian otomatis
  terhitung ganda                                    dan review

  Kelengkapan ekspor    100% transaksi yang memenuhi Rekonsiliasi hasil
  data                               filter tercakup ekspor

  Pemulihan backup         Berhasil melalui prosedur Simulasi pemulihan
                        terdokumentasi di lingkungan 
                                                 uji 

  Isu keamanan                 0 isu terbuka sebelum Daftar periksa/review
  kritis/tinggi                  penggunaan produksi keamanan

  Kegunaan di perangkat     Alur inti berfungsi pada QA responsif
  seluler                          lebar layar 360px 
  ------------------------------------------------------------------------

Target tersebut adalah sasaran awal, bukan klaim hasil pengukuran.
Sesuaikan setelah pengujian dasar.

## 4. Pengguna dan Persona

### 4.1 Persona utama: Pemilik keuangan pribadi

Satu individu yang mencatat pemasukan dan pengeluaran, menggunakan
beberapa akun keuangan, merencanakan kebutuhan berdasarkan siklus
gajian, menabung untuk tujuan tertentu, dan ingin mengetahui kondisi
keuangannya secara andal.

**Kebutuhan** - Mencatat pembelian dengan cepat. - Mencocokkan
pencatatan manual dengan saldo aktual. - Mengetahui dana yang sudah
dialokasikan dan dana yang masih bebas digunakan. - Memantau tagihan
rutin dan target tabungan. - Memahami tren keuangan tanpa harus
menguasai akuntansi.

**Kendala** - Terutama menggunakan browser ponsel. - Bisa memasukkan
transaksi setelah transaksi terjadi. - Bisa memiliki pemasukan tidak
tetap atau jarak gajian yang berubah. - Bisa mencatat investasi secara
manual tanpa data harga pasar langsung.

### 4.2 Operator sekunder: Pemilik/administrator aplikasi

Orang yang sama, menggunakan panel Filament yang terlindungi untuk
pemeliharaan terbatas, perbaikan kategori, pemeriksaan akun, dan
pekerjaan operasional. Filament tidak boleh menjadi jalan pintas yang
melewati aturan domain keuangan atau otorisasi.

## 5. Prinsip Produk

1.  **Akurasi sebelum tampilan.** Total keuangan harus memiliki
    definisi, jejak audit, dan pengujian.
2.  **Mobile-first.** Alur inti harus berfungsi pada layar sempit dan
    input sentuh.
3.  **Pencatatan cepat.** Minimalkan jumlah langkah dan kolom untuk
    transaksi umum.
4.  **Perhitungan transparan.** Jelaskan input yang memengaruhi setiap
    metrik turunan.
5.  **Tidak ada kehilangan data secara diam-diam.** Tindakan destruktif
    harus dikonfirmasi; ekspor dan backup harus tersedia.
6.  **Aman secara bawaan.** Autentikasi, otorisasi, CSRF, validasi, dan
    pengelolaan rahasia wajib diterapkan.
7.  **Server adalah sumber kebenaran.** Perhitungan keuangan dan
    validasi tidak boleh hanya bergantung pada JavaScript sisi klien.
8.  **Arsitektur sederhana.** Utamakan konvensi Laravel dan monolit
    modular daripada microservices terlalu dini.

## 6. Ruang Lingkup dan Strategi Rilis

### 6.1 MVP P0 --- Wajib untuk rilis pertama

-   Autentikasi dan preferensi profil.
-   Pengelolaan akun keuangan dan saldo awal.
-   Kategori pemasukan dan pengeluaran.
-   Pembuatan, tampilan, pengubahan, dan penghapusan/pembatalan
    transaksi.
-   Transfer antar akun.
-   Dashboard berisi saldo, pemasukan/pengeluaran bulanan, dan transaksi
    terbaru.
-   Anggaran bulanan per kategori.
-   Tagihan dan pelacakan tanggal jatuh tempo.
-   Target tabungan dengan kontribusi manual.
-   Perhitungan Safe-to-Spend beserta rinciannya.
-   Laporan dasar dan ekspor CSV.
-   UI Blade responsif.
-   Panel admin/pemeliharaan Filament yang terlindungi.
-   Pengujian otomatis untuk perhitungan uang, otorisasi, dan alur inti.
-   Petunjuk backup dan pemulihan.

### 6.2 MVP P1 --- Diinginkan setelah P0 stabil

-   Templat transaksi berulang.
-   Snapshot aset dan tren kekayaan bersih.
-   Impor transaksi melalui CSV tervalidasi.
-   Pencarian, filter lanjutan, dan perbandingan bulanan.
-   Dukungan instalasi PWA jika tidak mengurangi keandalan.
-   Pengingat tagihan mendatang secara opsional.

### 6.3 Rilis mendatang

-   Insight dan pendamping keuangan berbasis AI.
-   Lampiran struk/OCR.
-   Integrasi bank atau dompet digital, sesuai ketersediaan penyedia dan
    persetujuan eksplisit.
-   Feed harga investasi.
-   Dukungan multi-mata uang.
-   Keuangan bersama/rumah tangga.
-   Notifikasi push seluler.

## 7. Arsitektur Informasi dan Navigasi

### 7.1 Navigasi untuk pengguna

Navigasi bawah pada perangkat seluler sebaiknya memprioritaskan lima
tujuan:

1.  **Beranda** --- ringkasan kondisi keuangan.
2.  **Aktivitas** --- daftar transaksi, filter, dan pencarian.
3.  **Tambah** --- tindakan utama untuk mencatat transaksi.
4.  **Anggaran** --- anggaran kategori, tagihan, dan target tabungan.
5.  **Aset** --- akun, aset/kewajiban yang dicatat, dan kekayaan bersih.

Pengaturan, kategori, ekspor, dan laporan dapat diakses melalui menu
profil. Hindari memenuhi navigasi bawah dengan terlalu banyak item.

### 7.2 Layar inti

  -----------------------------------------------------------------------
  Layar                   Tujuan                  Aksi utama
  ----------------------- ----------------------- -----------------------
  Login                   Mengautentikasi         Masuk, reset kata sandi
                          pengguna                

  Pengaturan awal         Menetapkan data         Atur lokal, akun, saldo
                          keuangan dasar          awal, tanggal gajian

  Dashboard beranda       Memahami kondisi        Tambah transaksi,
                          keuangan                periksa Safe-to-Spend

  Form transaksi          Mencatat aktivitas      Simpan, batal
                          keuangan                

  Aktivitas               Meninjau riwayat        Cari, filter, ubah,
                                                  ekspor

  Akun                    Mengelola tempat        Buat, ubah,
                          penyimpanan uang        rekonsiliasi, arsipkan

  Anggaran                Merencanakan dan        Buat anggaran, ubah
                          memantau pengeluaran    batas

  Tagihan                 Memantau kewajiban      Tambah tagihan, tandai
                          mendatang               lunas, jadwalkan ulang

  Target tabungan         Merencanakan dana       Buat target, tambah
                          tujuan                  kontribusi

  Laporan                 Meninjau tren           Pilih periode, ekspor
                                                  CSV

  Pengaturan              Mengatur preferensi     Mata uang, zona waktu,
                          pribadi                 tanggal gajian, privasi

  Panel Filament          Pemeliharaan            Kelola data dan operasi
                          terlindungi             yang diizinkan
  -----------------------------------------------------------------------

### 7.3 Persyaratan UX

-   Semua layar inti harus dapat digunakan pada lebar 360px dan
    menyesuaikan tablet/desktop.
-   Gunakan tipografi terbaca, kontras aksesibel, indikator fokus yang
    jelas, serta target sentuh ideal minimal 44x44 piksel CSS.
-   Gunakan label Bahasa Indonesia dan format IDR secara bawaan; simpan
    nilai numerik terpisah dari string yang sudah diformat.
-   Form harus menampilkan validasi di dekat kolom terkait dan
    mempertahankan input non-sensitif setelah kegagalan validasi jika
    aman.
-   Keadaan kosong harus menjelaskan cara memulai; keadaan memuat dan
    error harus eksplisit.
-   Tindakan destruktif wajib meminta konfirmasi dan menjelaskan
    dampaknya.
-   Jangan hanya menggunakan warna untuk menyampaikan kondisi keuangan
    positif/negatif.
-   Gunakan progressive disclosure untuk kolom lanjutan seperti catatan,
    nomor referensi, dan tag.
-   Pastikan total keuangan mudah ditemukan tanpa ruang vertikal
    berlebihan.

## 8. Persyaratan Fungsional

Prioritas: **P0** wajib MVP; **P1** iterasi berikutnya; **P2** masa
depan.

### FR-001 --- Autentikasi dan pengelolaan sesi (P0)

-   Sistem wajib meminta autentikasi sebelum menampilkan data keuangan
    pribadi.
-   Sistem menyediakan login, logout, dan reset kata sandi yang aman.
-   Sistem menggunakan hashing kata sandi dan perlindungan sesi yang
    didukung Laravel.
-   Sistem membatasi percobaan login gagal berulang.
-   Sistem membatalkan sesi saat logout dan menggunakan pengaturan
    cookie aman di produksi.
-   Pemilik aplikasi harus dapat memulihkan akses tanpa mengungkap kata
    sandi.

**Kriteria penerimaan** - Permintaan tanpa autentikasi ke halaman
terlindungi diarahkan ke login. - Pengguna dapat logout dan tidak dapat
menggunakan kembali sesi yang sudah dibatalkan. - Kata sandi tidak
pernah disimpan atau dicatat dalam log sebagai teks biasa. - Pembatasan
login diuji melalui feature test.

### FR-002 --- Profil dan preferensi (P0)

-   Pengguna dapat mengatur nama tampilan, zona waktu, lokal, aturan
    tanggal gajian, dan mata uang bawaan.
-   Mata uang bawaan adalah IDR.
-   Rilis awal mendukung satu mata uang dasar per instalasi/pengguna.
    Mata uang lain tidak boleh digabungkan ke total tanpa dukungan
    eksplisit di rilis mendatang.
-   Tanggal gajian dapat berupa tanggal dalam bulan atau tanggal gajian
    berikutnya yang ditetapkan manual. Penanganan bulan yang lebih
    pendek harus didefinisikan secara eksplisit.

**Kriteria penerimaan** - Perubahan preferensi tersimpan dan tercermin
pada format tanggal serta nominal. - Sistem mendokumentasikan cara
menghitung tanggal gajian.

### FR-003 --- Pengelolaan akun (P0)

Jenis akun yang didukung: tunai, bank, dompet digital, investasi,
tabungan, dan lainnya. Akun kewajiban dapat ditambahkan pada fase
kekayaan bersih; utang tidak boleh direpresentasikan sebagai aset
positif.

Setiap akun memiliki: - ID, pemilik, nama, jenis, saldo awal, tanggal
saldo awal, status aktif/arsip, dan timestamp. - Label/lembaga dan
catatan opsional. - Peran yang jelas dalam perhitungan dana yang
tersedia untuk dibelanjakan.

**Aturan** - Saldo awal adalah titik dasar, bukan pemasukan bulanan. -
Akun yang diarsipkan tetap terlihat dalam laporan historis tetapi secara
bawaan tidak dipilih untuk transaksi baru. - Saldo akun dihitung dari
saldo awal ditambah dampak transaksi yang tercatat, atau menggunakan
model ledger yang didefinisikan dengan jelas. Hindari saldo cache yang
dapat diedit terpisah tanpa rekonsiliasi. - Rekonsiliasi manual dapat
mencatat penyesuaian dengan alasan, tanggal, dan jejak audit; sistem
tidak boleh mengubah riwayat secara diam-diam.

**Kriteria penerimaan** - Akun yang dibuat dengan saldo awal
menghasilkan saldo sesuai perhitungan. - Pengarsipan akun tidak
menghapus transaksi historis. - Total akun sama dengan saldo awal
ditambah seluruh dampak transaksi yang valid.

### FR-004 --- Kategori (P0)

-   Sistem mendukung kategori pemasukan dan pengeluaran.
-   Setiap kategori memiliki nama, jenis, ikon/warna opsional, status
    aktif, serta penanda pemilik atau kategori bawaan sistem.
-   Kategori tidak boleh digunakan pada jenis transaksi yang tidak
    sesuai.
-   Kategori yang dirujuk transaksi historis sebaiknya diarsipkan, bukan
    dihapus permanen.

Contoh kategori pengeluaran: Makanan, Transportasi, Tempat Tinggal,
Tagihan, Kesehatan, Belanja, Hiburan, Pendidikan, Keluarga, Biaya,
Lainnya. Contoh kategori pemasukan: Gaji, Freelance, Bisnis,
Bunga/Dividen, Hadiah, Pengembalian Dana, Lainnya.

**Kriteria penerimaan** - Pilihan kategori disaring berdasarkan jenis
transaksi. - Kategori arsip tidak muncul pada transaksi baru tetapi
tetap terbaca pada riwayat.

### FR-005 --- Transaksi (P0)

Jenis transaksi: - `income` --- pemasukan - `expense` --- pengeluaran -
`transfer` --- transfer - `adjustment` --- penyesuaian, dibatasi untuk
operasi rekonsiliasi yang dihasilkan sistem

Kolom transaksi: - ID, pemilik, jenis transaksi, nominal, tanggal/waktu
transaksi, akun sumber, akun tujuan opsional, kategori opsional,
deskripsi, catatan, status, dan timestamp. - Nominal harus positif;
jenis transaksi menentukan dampak akuntansinya. - Gunakan tipe data
desimal presisi tetap, bukan floating point. - Validasi sisi server
wajib diterapkan. - Gunakan soft delete atau pembatalan untuk catatan
keuangan jika memungkinkan, disertai jejak audit koreksi.

**Aturan** - Pemasukan menambah saldo akun sumber. - Pengeluaran
mengurangi saldo akun sumber. - Transfer mengurangi saldo akun sumber
dan menambah akun tujuan dengan nominal sama. - Transfer tidak dihitung
sebagai pemasukan atau pengeluaran pada laporan arus kas. -
Mengubah/membatalkan transaksi tercatat harus memperbarui semua total
turunan secara konsisten. - Transaksi harus dimiliki pengguna yang
terautentikasi dan hanya merujuk akun/kategori milik pengguna
tersebut. - Form sebaiknya menetapkan tanggal transaksi ke tanggal lokal
pengguna saat ini.

**Kriteria penerimaan** - Operasi buat, ubah, batalkan, dan daftar
memperbarui saldo serta laporan dengan benar. - Transfer antarakun milik
sendiri mengubah saldo masing-masing akun tetapi tidak mengubah total
dana agregat, kecuali ada biaya atau penyesuaian eksplisit. - Nominal
negatif, nol, format salah, atau terlalu besar ditolak. - ID milik
pengguna lain tidak dapat dipakai untuk membaca atau mengubah data.

### FR-006 --- Dashboard (P0)

Dashboard menampilkan: - Total saldo semua akun yang termasuk
perhitungan. - Pemasukan dan pengeluaran periode berjalan. - Arus kas
bersih untuk periode terpilih. - Safe-to-Spend dan rinciannya. -
Pemakaian anggaran dan peringatan. - Tagihan mendatang. - Transaksi
terbaru. - Progres target tabungan.

**Aturan** - Metrik dashboard harus mengikuti definisi terdokumentasi
dan periode terpilih. - Akun investasi boleh masuk total aset, tetapi
tidak otomatis masuk dana yang tersedia untuk dibelanjakan. - Sediakan
keadaan memuat, kosong, dan gagal. - Metrik turunan dihitung dari data
database yang otoritatif atau cache aman dengan invalidasi yang jelas.

**Kriteria penerimaan** - Total dashboard dapat direkonsiliasi dengan
laporan transaksi dan akun untuk periode yang sama. - Perubahan periode
memperbarui metrik berbasis periode tanpa mengubah data historis. -
Dashboard tidak mengklasifikasikan transfer sebagai
pemasukan/pengeluaran.

### FR-007 --- Anggaran (P0)

-   Pengguna dapat membuat anggaran berdasarkan kategori dan periode;
    MVP menggunakan periode bulanan.
-   Kolom anggaran: kategori, awal periode, akhir periode atau kunci
    bulan, nominal batas, ambang peringatan opsional, dan status aktif.
-   Pengeluaran aktual adalah jumlah transaksi pengeluaran berstatus
    tercatat yang memenuhi kategori dan periode anggaran.
-   Pengembalian dana mengikuti aturan eksplisit. Aturan bawaan MVP:
    refund dicatat sebagai pemasukan/refund dan tidak diam-diam
    menghapus pengeluaran awal. Dukungan refund yang ditautkan dapat
    ditambahkan kemudian.
-   Anggaran menampilkan terpakai, sisa, dan persentase pemakaian.
-   Berikan peringatan saat pemakaian mencapai ambang yang dapat
    dikonfigurasi, bawaan 80% dan 100%.

**Kriteria penerimaan** - Transaksi di luar periode tidak memengaruhi
anggaran tersebut. - Transfer tidak memengaruhi anggaran pengeluaran. -
Batas anggaran tidak boleh negatif atau tidak valid. - Pemakaian dan
sisa dihitung secara konsisten.

### FR-008 --- Tagihan dan kewajiban (P0)

-   Pengguna dapat membuat tagihan dengan judul, nominal, tanggal jatuh
    tempo, kategori/akun opsional, aturan berulang jika
    diimplementasikan, dan status.
-   Status mencakup mendatang, lunas, dilewati/dibatalkan, dan terlambat
    sebagai status turunan atau status yang dikendalikan dengan jelas.
-   Saat menandai tagihan lunas, sistem menawarkan pembuatan/pengaitan
    transaksi pengeluaran; sistem harus mencegah pencatatan pengeluaran
    ganda secara tidak sengaja.
-   Tagihan yang belum dibayar hanya boleh dihitung sekali dalam
    Safe-to-Spend.
-   Status terlambat dihitung berdasarkan zona waktu dan tanggal saat
    ini.

**Kriteria penerimaan** - Tagihan dapat ditandai lunas dan ditautkan ke
satu transaksi. - Tagihan yang sama tidak dapat membuat transaksi
tertaut ganda. - Tampilan jatuh tempo mengikuti aturan zona waktu dan
status.

### FR-009 --- Target tabungan (P0)

-   Pengguna dapat membuat target dengan nama, nominal target, tanggal
    target opsional, prioritas, dan status.
-   Pengguna dapat menambahkan kontribusi dan penarikan.
-   Progres target harus dapat diaudit dan tidak boleh melewati target
    tanpa status kelebihan dana yang eksplisit.
-   Kontribusi target harus dimodelkan dengan jelas: transfer internal
    ke akun tabungan yang ditentukan bukan pengeluaran.
-   Dana yang dicadangkan tidak boleh dikurangi dua kali dalam
    Safe-to-Spend jika sudah dikeluarkan dari dasar saldo yang dapat
    dibelanjakan.

**Kriteria penerimaan** - Kontribusi memperbarui progres tepat satu
kali. - Saldo target dan akun terkait tetap konsisten saat dana
benar-benar ditransfer. - Target selesai, dijeda, atau dibatalkan tetap
tersedia dalam riwayat.

### FR-010 --- Perhitungan Safe-to-Spend (P0)

Safe-to-Spend adalah estimasi dana diskresioner yang tersedia sampai
tanggal gajian berikutnya atau tanggal perencanaan yang dipilih. Fitur
ini bukan nasihat keuangan dan harus menjelaskan input yang digunakan.

Rumus konseptual:

`Safe-to-Spend = saldo yang dapat dibelanjakan - kewajiban belum dibayar - kebutuhan pokok terencana yang tersisa - alokasi cadangan yang belum dikeluarkan dari saldo dasar`

Persyaratan: - Definisikan jenis akun yang dapat dibelanjakan. -
Definisikan perlakuan kartu kredit, saldo negatif, transaksi tertunda,
investasi, dan cerukan. - Gunakan satu rentang perencanaan konsisten
yang berakhir pada tanggal gajian berikutnya atau tanggal pilihan
pengguna. - Setiap tagihan/alokasi hanya dihitung satu kali. - Jangan
mengurangi dana yang sudah dikeluarkan dari dasar saldo yang dapat
dibelanjakan. - Tampilkan rincian dan izinkan pengguna mengubah
asumsi. - Jika data penting belum tersedia, tampilkan status belum
lengkap/estimasi, bukan presisi palsu. - Jangan menyiratkan bahwa hasil
menjamin kemampuan membayar di masa depan.

**Kriteria penerimaan** - Pengujian unit mencakup hasil positif, nol,
dan negatif. - Pengujian membuktikan tabungan cadangan dan tagihan tidak
dihitung ganda. - Jenis akun yang dikecualikan tidak menambah saldo yang
dapat dibelanjakan. - UI menampilkan tanggal perencanaan dan asumsi yang
dipakai.

### FR-011 --- Laporan dan analitik (P0)

-   Pengguna dapat melihat pemasukan, pengeluaran, dan arus kas bersih
    pada periode yang dipilih.
-   Pengguna dapat melihat rincian pengeluaran berdasarkan kategori.
-   Pengguna dapat membandingkan bulan berjalan dengan periode
    sebelumnya jika data mencukupi.
-   Laporan harus membedakan pengeluaran konsumsi dari transfer,
    penyesuaian saldo, dan perubahan valuasi investasi.
-   Grafik harus memiliki label aksesibel dan alternatif berupa
    teks/tabel.

**Kriteria penerimaan** - Total laporan cocok dengan daftar transaksi. -
Periode kosong menampilkan keadaan kosong yang informatif. - Perubahan
periode memperbarui semua nilai laporan secara konsisten.

### FR-012 --- Ekspor CSV (P0)

-   Pengguna dapat mengekspor transaksi berdasarkan rentang tanggal
    terpilih.
-   Kolom ekspor serta format tanggal/desimal harus didokumentasikan.
-   Pembuatan CSV harus mengurangi risiko formula injection spreadsheet,
    misalnya dengan menangani sel yang diawali karakter `=`, `+`, `-`,
    atau `@` bila relevan.
-   Ekspor harus mengikuti otorisasi dan filter terpilih.
-   Ekspor besar perlu di-stream atau dimasukkan ke antrean jika
    dibutuhkan.

**Kriteria penerimaan** - Jumlah baris dan total hasil ekspor cocok
dengan kueri yang difilter. - Pengguna tidak berwenang tidak dapat
mengekspor data yang bukan miliknya. - Teks yang menyerupai formula
ditangani dengan aman.

### FR-013 --- Pelacakan aset dan kekayaan bersih (P1)

-   Lacak aset dan kewajiban bernilai manual, termasuk tunai, tabungan,
    investasi, dan utang.
-   Kekayaan bersih = total nilai aset - total nilai kewajiban.
-   Simpan snapshot bertanggal agar tren dapat dilihat.
-   Bedakan arus transaksi akun dari perubahan valuasi pasar.
-   Nilai pasar langsung tidak dijanjikan pada MVP.

**Kriteria penerimaan** - Kekayaan bersih dapat direkonsiliasi dengan
catatan aset dan kewajiban untuk snapshot tertentu. - Perubahan valuasi
tidak dicatat sebagai pemasukan kecuali dimodelkan secara eksplisit
sebagai pendapatan terealisasi berdasarkan kebijakan akuntansi di masa
depan.

### FR-014 --- Transaksi berulang dan pengingat (P1)

-   Sediakan templat untuk pemasukan, tagihan, dan pengeluaran rutin.
-   Aturan pengulangan harus mendefinisikan kemunculan berikutnya dan
    perilaku pada tanggal akhir bulan.
-   Posting otomatis harus bersifat opt-in; bawaan yang lebih aman
    adalah membuat pengingat/draf, bukan diam-diam mencatat pembayaran
    selesai.
-   Mulai dari indikator dalam aplikasi; pengiriman email/push menjadi
    keputusan berikutnya.

### FR-015 --- Impor (P1)

-   Sediakan templat impor CSV terdokumentasi.
-   Validasi ukuran file, encoding, header, tanggal, nominal, dan
    kategori.
-   Tampilkan pratinjau serta laporan validasi sebelum menyimpan.
-   Deteksi kemungkinan duplikasi dengan aturan konservatif; jangan
    diam-diam menghilangkan data.
-   Impor harus bersifat transaksional atau memiliki strategi rollback
    yang jelas.

### FR-016 --- Panel pemeliharaan Filament (P0, ruang lingkup terbatas)

-   Filament digunakan untuk alur admin/pemeliharaan tepercaya, bukan UI
    utama keuangan pribadi di ponsel.
-   Lindungi panel dengan otorisasi eksplisit dan, bila sesuai,
    kemampuan/role admin terpisah.
-   Gunakan policy dan domain service untuk semua perubahan data
    keuangan.
-   Nonaktifkan aksi resource, operasi massal, dan pendaftaran publik
    yang tidak diperlukan.
-   Jangan tampilkan rahasia, hash kata sandi, data sesi, atau catatan
    sensitif yang tidak diperlukan.
-   Catat aksi admin yang berdampak penting.
-   Invarian bisnis yang sama harus berlaku pada alur Filament maupun
    Blade.

**Kriteria penerimaan** - Permintaan panel dari pengguna yang tidak
terautentikasi atau tidak berwenang ditolak. - Kueri resource dibatasi
sesuai izin. - Aksi Filament tidak dapat melewati validasi transaksi
atau aturan saldo.

## 9. Aturan Domain dan Definisi Keuangan

Bagian ini bersifat normatif. Kode, pengujian, laporan, dan UI wajib
mengikuti definisi berikut.

### 9.1 Nominal dan pembulatan

-   Simpan nominal menggunakan MySQL `DECIMAL`, misalnya
    `DECIMAL(19,2)`, bukan `FLOAT` atau `DOUBLE`.
-   Gunakan integer satuan terkecil atau value object uang berbasis
    desimal dalam logika aplikasi bila praktis.
-   Jangan menghitung uang menggunakan floating-point biner.
-   Tetapkan aturan pembulatan di satu tempat dan uji secara otomatis.
-   Format tampilan mata uang harus sesuai lokal; pemformatan tampilan
    tidak boleh mengubah nilai tersimpan.

### 9.2 Saldo akun

Untuk model transaksi sederhana:

`Saldo akun = saldo awal + pemasukan tercatat - pengeluaran tercatat + transfer masuk - transfer keluar + penyesuaian yang disetujui`

Jika kewajiban didukung, tanda dan aturan pelaporannya harus
didefinisikan secara eksplisit. Jangan mencampur saldo aset dan
kewajiban tanpa normalisasi.

### 9.3 Arus kas

`Arus kas bersih = pemasukan yang memenuhi syarat - pengeluaran yang memenuhi syarat`

Transfer antarakun milik sendiri dikecualikan. Biaya transfer harus
menjadi transaksi pengeluaran eksplisit atau biaya transfer yang
dimodelkan dengan jelas, bukan selisih saldo tersembunyi.

### 9.4 Kekayaan bersih

`Kekayaan bersih = total nilai aset - total nilai kewajiban`

Perubahan nilai investasi adalah perubahan valuasi, bukan selalu
pemasukan tunai. Waktu snapshot dan sumber/metode valuasi harus dicatat.

### 9.5 Siklus hidup transaksi

Gunakan siklus hidup kecil dan eksplisit, misalnya `posted` (tercatat)
dan `voided` (dibatalkan). Status draf dapat ditambahkan jika
diperlukan. Laporan hanya memasukkan catatan yang memenuhi syarat dan
berstatus tercatat. Pembatalan harus mempertahankan riwayat dan mencatat
siapa/kapan/alasan jika relevan.

### 9.6 Tanggal dan periode

-   Simpan timestamp dalam UTC bila menggunakan timestamp; simpan
    tanggal transaksi tanpa jam sebagai `DATE` jika waktu tidak
    diperlukan.
-   Gunakan zona waktu pengguna untuk "hari ini", tanggal gajian, jatuh
    tempo, dan batas periode.
-   Gunakan rentang setengah terbuka secara internal jika praktis: awal
    inklusif dan awal periode berikutnya eksklusif.
-   Uji batas akhir bulan dan tahun kabisat.

## 10. Model Data Awal

Berikut model logis. Tipe kolom, constraint, dan indeks final harus
ditetapkan dalam migration sebelum implementasi.

### 10.1 `users`

  -----------------------------------------------------------------------
  Kolom                   Tipe yang disarankan    Catatan
  ----------------------- ----------------------- -----------------------
  `id`                    BIGINT UNSIGNED PK      Bawaan Laravel

  `name`                  VARCHAR(120)            Wajib

  `email`                 VARCHAR(255) UNIQUE     Wajib

  `password`              VARCHAR(255)            Hash kata sandi Laravel

  `timezone`              VARCHAR(64)             Bawaan `Asia/Jakarta`

  `locale`                VARCHAR(10)             Bawaan `id`

  `currency`              CHAR(3)                 Bawaan `IDR`

  `payday_day`            TINYINT UNSIGNED NULL   Validasi rentang yang
                                                  didukung

  `next_payday_date`      DATE NULL               Tanggal eksplisit
                                                  opsional

  `created_at`,           TIMESTAMP               Timestamp Laravel
  `updated_at`                                    
  -----------------------------------------------------------------------

Gunakan skema autentikasi Laravel yang didukung, tabel token reset/sesi
sesuai kebutuhan, dan strategi verifikasi email yang teruji bila email
dipakai untuk pemulihan akun.

### 10.2 `accounts`

  ----------------------------------------------------------------------------
  Kolom                        Tipe yang disarankan    Catatan
  ---------------------------- ----------------------- -----------------------
  `id`                         BIGINT UNSIGNED PK      

  `user_id`                    BIGINT UNSIGNED FK      Pemilik

  `name`                       VARCHAR(100)            Wajib

  `type`                       VARCHAR/ENUM            cash, bank, e_wallet,
                                                       savings, investment,
                                                       other

  `opening_balance`            DECIMAL(19,2)           Saldo dasar

  `opening_date`               DATE                    Tanggal saldo dasar

  `include_in_total_assets`    BOOLEAN                 Aturan inklusi aset

  `include_in_safe_to_spend`   BOOLEAN                 Kelayakan untuk
                                                       perhitungan dana
                                                       belanja

  `status`                     VARCHAR(20)             active, archived

  `notes`                      TEXT NULL               Opsional

  `created_at`, `updated_at`   TIMESTAMP               
  ----------------------------------------------------------------------------

Indeks: `(user_id, status)`. Foreign key dan pemeriksaan kepemilikan
wajib diterapkan.

### 10.3 `categories`

  -----------------------------------------------------------------------
  Kolom                   Tipe yang disarankan    Catatan
  ----------------------- ----------------------- -----------------------
  `id`                    BIGINT UNSIGNED PK      

  `user_id`               BIGINT UNSIGNED NULL FK Null dapat menandakan
                                                  kategori bawaan sistem

  `name`                  VARCHAR(80)             

  `type`                  VARCHAR(20)             income, expense

  `icon`                  VARCHAR(80) NULL        Kunci ikon yang
                                                  diizinkan

  `color`                 VARCHAR(20) NULL        Token/hex tervalidasi

  `is_system`             BOOLEAN                 Kategori bawaan

  `is_active`             BOOLEAN                 Arsipkan alih-alih
                                                  menghapus

  `created_at`,           TIMESTAMP               
  `updated_at`                                    
  -----------------------------------------------------------------------

Tentukan strategi keunikan owner/type/name yang bekerja untuk kategori
sistem dengan pemilik bernilai null.

### 10.4 `transactions`

  --------------------------------------------------------------------------
  Kolom                      Tipe yang disarankan    Catatan
  -------------------------- ----------------------- -----------------------
  `id`                       BIGINT UNSIGNED PK      

  `user_id`                  BIGINT UNSIGNED FK      Kunci kepemilikan untuk
                                                     pembatasan aman

  `type`                     VARCHAR(20)             income, expense,
                                                     transfer, adjustment

  `amount`                   DECIMAL(19,2)           Nominal positif

  `transaction_date`         DATE                    Tanggal lokal pengguna

  `source_account_id`        BIGINT UNSIGNED FK      Wajib kecuali aturan
                                                     domain menentukan lain

  `destination_account_id`   BIGINT UNSIGNED NULL FK Wajib untuk transfer

  `category_id`              BIGINT UNSIGNED NULL FK Wajib untuk
                                                     pemasukan/pengeluaran
                                                     sesuai kebijakan

  `description`              VARCHAR(160)            Wajib atau nilai bawaan
                                                     yang bermakna

  `notes`                    TEXT NULL               Opsional

  `status`                   VARCHAR(20)             posted, voided,
                                                     opsional draft

  `voided_at`                TIMESTAMP NULL          Audit

  `void_reason`              VARCHAR(255) NULL       Opsional

  `created_at`, `updated_at` TIMESTAMP               
  --------------------------------------------------------------------------

Constraint/invarian: - `amount > 0`. - Akun sumber dan tujuan transfer
harus berbeda. - Transfer wajib memiliki akun tujuan dan tidak memiliki
kategori pemasukan/pengeluaran, kecuali model biaya transfer
didokumentasikan. - Pemasukan/pengeluaran wajib memiliki akun sumber. -
Akun sumber/tujuan/kategori harus dimiliki pengguna yang sama dengan
transaksi. - Tambahkan indeks `(user_id, transaction_date)`,
`(source_account_id, transaction_date)`,
`(category_id, transaction_date)`, dan indeks status yang relevan. -
Gunakan foreign key dengan perilaku penghapusan yang disengaja. Utamakan
larangan penghapusan entitas keuangan yang masih dirujuk. - Validasi
tingkat aplikasi tetap wajib meskipun database mendukung check
constraint.

### 10.5 `budgets`

  Kolom                        Tipe yang disarankan   Catatan
  ---------------------------- ---------------------- -----------------------
  `id`                         BIGINT UNSIGNED PK     
  `user_id`                    BIGINT UNSIGNED FK     
  `category_id`                BIGINT UNSIGNED FK     Kategori pengeluaran
  `period_start`               DATE                   Inklusif
  `period_end`                 DATE                   Eksklusif disarankan
  `limit_amount`               DECIMAL(19,2)          Harus \> 0
  `warning_threshold`          DECIMAL(5,2)           Misalnya 80.00 persen
  `status`                     VARCHAR(20)            active, archived
  `created_at`, `updated_at`   TIMESTAMP              

Tambahkan strategi keunikan agar tidak ada anggaran aktif ganda untuk
pemilik/kategori/periode yang sama.

### 10.6 `bills`

  -----------------------------------------------------------------------
  Kolom                   Tipe yang disarankan    Catatan
  ----------------------- ----------------------- -----------------------
  `id`                    BIGINT UNSIGNED PK      

  `user_id`               BIGINT UNSIGNED FK      

  `title`                 VARCHAR(120)            

  `amount`                DECIMAL(19,2)           Nominal perkiraan

  `due_date`              DATE                    Jatuh tempo

  `category_id`           BIGINT UNSIGNED NULL FK Opsional

  `status`                VARCHAR(20)             upcoming, paid,
                                                  skipped/cancelled

  `paid_transaction_id`   BIGINT UNSIGNED NULL FK Mencegah tautan
                          UNIQUE                  pembayaran ganda

  `recurrence_rule`       JSON NULL               P1; validasi skema

  `notes`                 TEXT NULL               

  `created_at`,           TIMESTAMP               
  `updated_at`                                    
  -----------------------------------------------------------------------

Status terlambat sebaiknya diturunkan dari tanggal jatuh tempo dan
status pembayaran, bukan disimpan sebagai keadaan yang dapat menjadi
usang.

### 10.7 `savings_goals`

  -----------------------------------------------------------------------
  Kolom                   Tipe yang disarankan    Catatan
  ----------------------- ----------------------- -----------------------
  `id`                    BIGINT UNSIGNED PK      

  `user_id`               BIGINT UNSIGNED FK      

  `name`                  VARCHAR(120)            

  `target_amount`         DECIMAL(19,2)           Harus \> 0

  `target_date`           DATE NULL               

  `priority`              TINYINT UNSIGNED        Rentang didefinisikan

  `status`                VARCHAR(20)             active, paused,
                                                  completed, cancelled

  `linked_account_id`     BIGINT UNSIGNED NULL FK Akun alokasi opsional

  `created_at`,           TIMESTAMP               
  `updated_at`                                    
  -----------------------------------------------------------------------

Utamakan tabel ledger `savings_goal_entries` untuk kontribusi/penarikan
daripada mengedit `current_amount` secara langsung. Jika kontribusi
sesuai transfer nyata, tautkan entri target ke transfer agar dampak
keuangan tidak dihitung ganda.

### 10.8 Tabel pendukung

-   `savings_goal_entries`: ID target, nominal, arah transaksi, tanggal,
    transaksi tertaut opsional, catatan, timestamp.
-   `asset_snapshots` (P1): referensi akun/aset, tanggal snapshot,
    nilai, sumber/metode, catatan.
-   `audit_logs`: aktor, aksi, jenis/ID entitas, timestamp, ID
    permintaan/korelasi, metadata aman. Jangan mencatat kata sandi, ID
    sesi, atau catatan keuangan sensitif yang tidak diperlukan.
-   `settings` hanya jika preferensi tidak dapat direpresentasikan
    dengan baik di `users`; hindari penyimpanan key/value generik tanpa
    validasi.
-   Tabel framework Laravel seperti `sessions`, `cache`, `jobs`, dan
    `password_reset_tokens` sesuai konfigurasi deployment.

### 10.9 Keputusan model data sebelum migration

1.  Apakah setiap entitas keuangan menggunakan soft delete, pembatalan,
    atau keduanya.
2.  Apakah saldo akun dihitung langsung, di-cache dengan invalidasi,
    atau didukung tabel ledger. Untuk MVP, hitung dari saldo awal dan
    transaksi tercatat; optimalkan setelah pengukuran.
3.  Apakah pendaftaran pengguna dibuka untuk umum atau akun disiapkan
    pemilik. Untuk aplikasi pribadi, nonaktifkan pendaftaran publik
    secara bawaan.
4.  Bagaimana refund, biaya transfer, kartu kredit, utang, dan
    rekonsiliasi akun direpresentasikan.
5.  Apakah pengulangan tagihan termasuk P0 atau ditunda ke P1.

## 11. Arsitektur Teknis

### 11.1 Stack yang direkomendasikan

-   PHP: versi yang kompatibel dengan rilis Laravel pilihan.
-   Laravel: rilis stabil terkini pada saat proyek dimulai, dengan versi
    dikunci di Composer.
-   Database: MySQL 8.0+ atau rilis kompatibel yang masih didukung;
    konfirmasi dukungan produksi dan SQL mode.
-   UI: Blade, HTML semantik, Tailwind CSS atau Bootstrap (pilih satu
    dan gunakan secara konsisten).
-   Panel admin: rilis Filament stabil yang kompatibel.
-   Perilaku frontend: JavaScript/Alpine.js seperlunya; hindari SPA
    kecuali ada alasan jelas.
-   Pengujian: Pest atau PHPUnit; pilih satu gaya utama.
-   Kualitas kode: Laravel Pint dan analisis statis seperti
    PHPStan/Larastan bila memungkinkan.
-   Version control: Git dengan commit kecil yang mudah ditinjau.
-   Deployment: hosting terkelola/VPS berbasis Linux dengan HTTPS, task
    terjadwal, backup database, dan konfigurasi berbasis environment.

Jangan mengunci asumsi versi tanpa memeriksa matriks kompatibilitas
resmi saat setup proyek.

### 11.2 Pola arsitektur

Gunakan **monolit modular Laravel**:

-   Lapisan HTTP: routes, controllers, form requests, middleware.
-   Lapisan aplikasi/domain: actions/services untuk posting transaksi,
    transfer, perhitungan anggaran, Safe-to-Spend, dan laporan.
-   Lapisan persistensi: model Eloquent, query/scopes, migrations.
-   Lapisan presentasi: layout Blade, komponen, halaman, dan view model
    jika membantu.
-   Lapisan admin: resource/halaman Filament yang memanggil domain
    service yang sama.
-   Pengujian: unit test untuk perhitungan domain; feature test untuk
    route, otorisasi, dan persistensi.

Jangan menempatkan aturan keuangan hanya di controller, template Blade,
atau callback Filament. Semua jalur masuk harus menggunakan operasi
domain yang sama.

### 11.3 Struktur proyek yang disarankan

``` text
app/
  Actions/
    Accounts/
    Transactions/
    Budgets/
    Bills/
    SavingsGoals/
  Domain/
    Money/
    Finance/
  Enums/
  Http/
    Controllers/
    Requests/
    Middleware/
  Models/
  Policies/
  Services/
    Dashboard/
    Reporting/
    SafeToSpend/
  Filament/
    Resources/
    Pages/
  ViewModels/                 # opsional
database/
  factories/
  migrations/
  seeders/
resources/
  views/
    layouts/
    components/
    auth/
    dashboard/
    transactions/
    accounts/
    budgets/
    bills/
    savings-goals/
    reports/
    settings/
  css/
  js/
routes/
  web.php
  console.php
tests/
  Unit/
  Feature/
```

Struktur ini merupakan saran, bukan kewajiban untuk membuat folder
kosong sekaligus. Tambahkan kelas ketika tanggung jawabnya jelas.

### 11.4 Desain route

Gunakan `routes/web.php` untuk layar server-rendered yang dilindungi
middleware autentikasi dan otorisasi, serta perlindungan sesi/CSRF
Laravel.

Contoh konsep route: - `GET /dashboard` - `GET|POST /transactions` -
`GET|PATCH|DELETE /transactions/{transaction}` - `GET|POST /accounts` -
`GET|POST /budgets` - `GET|POST /bills` - `GET|POST /savings-goals` -
`GET /reports` - `GET /exports/transactions`

Nama route dan metode HTTP final harus mengikuti konvensi Laravel dan
memakai named route. Untuk catatan keuangan, tindakan pembatalan/arsip
eksplisit lebih disukai daripada penghapusan destruktif jika sesuai.

### 11.5 Penggunaan Filament

Gunakan Filament hanya untuk alur admin/pemeliharaan tepercaya. Jangan
membangun pengalaman keuangan utama seluler dengan Filament karena UI
pengguna secara eksplisit menggunakan Blade.

-   Pastikan rilis Filament kompatibel dengan versi Laravel/PHP
    terpilih.
-   Lindungi panel dengan gate/otorisasi eksplisit.
-   Nonaktifkan pendaftaran pengguna publik kecuali benar-benar
    diperlukan.
-   Batasi kueri resource sesuai izin.
-   Gunakan domain service untuk mencatat atau membatalkan transaksi.
-   Hindari penghapusan massal catatan keuangan atau aksi yang melewati
    invarian domain.
-   Pisahkan navigasi pengguna dari navigasi Filament.
-   Tambahkan audit log untuk operasi pemeliharaan yang berdampak
    penting.

## 12. Keamanan, Privasi, dan Perlindungan Data

Catatan keuangan merupakan data pribadi sensitif. Keamanan adalah
persyaratan rilis, bukan fitur tambahan belakangan.

### 12.1 Autentikasi dan otorisasi

-   Gunakan fitur autentikasi Laravel yang mapan atau starter kit yang
    kompatibel.
-   Gunakan hashing kata sandi Laravel; jangan menggunakan MD5/SHA1 atau
    enkripsi yang dapat dibalik untuk kata sandi.
-   Terapkan otorisasi melalui policy/gate dan pembatasan data
    berdasarkan pemilik di server.
-   Tolak akses secara bawaan.
-   Cegah insecure direct object reference (IDOR) dengan memeriksa
    kepemilikan pada setiap baca, ubah, hapus, ekspor, dan relasi
    bertingkat.
-   Terapkan pembatasan login dan reset kata sandi yang aman.
-   Pertimbangkan MFA sebagai peningkatan masa depan untuk deployment
    yang dapat diakses internet.

### 12.2 Keamanan web

-   Pertahankan perlindungan CSRF untuk request yang mengubah data.
-   Escape output Blade secara bawaan; jangan merender input pengguna
    sebagai HTML mentah tanpa sanitasi.
-   Validasi input melalui Form Request atau validasi terpusat yang
    setara.
-   Gunakan parameter binding Eloquent/query builder; jangan
    menyambungkan input tidak tepercaya ke SQL.
-   Atur cookie aman, HTTP-only, same-site, serta HTTPS di produksi.
-   Konfigurasikan trusted hosts, security headers, dan content security
    policy yang sesuai deployment.
-   Perbarui dependensi dan jalankan audit dependensi.
-   Batasi laju endpoint sensitif dan ekspor.
-   Validasi file unggahan jika lampiran ditambahkan di masa depan.

### 12.3 Rahasia dan logging

-   Jangan masukkan `.env` ke version control.
-   Gunakan kredensial terpisah untuk lokal, pengujian, staging, dan
    produksi.
-   Jangan mencatat kata sandi, token reset, cookie sesi, kunci rahasia,
    atau payload sensitif secara lengkap.
-   Gunakan log terstruktur dengan ID permintaan dan konteks yang aman.
-   Redaksi data pribadi/keuangan dari pelaporan error jika
    memungkinkan.

### 12.4 Siklus hidup data

-   Sediakan ekspor CSV.
-   Dokumentasikan frekuensi backup, retensi, enkripsi, dan prosedur
    pemulihan.
-   Lindungi backup database sebagai data sensitif.
-   Definisikan perilaku ekspor/penghapusan sebelum menambahkan
    penghapusan akun.
-   Gunakan soft delete/pembatalan saat integritas riwayat diperlukan.
-   Jika aplikasi di-host secara publik, sediakan pemberitahuan privasi
    yang menjelaskan penyimpanan, akses, retensi, dan backup.

### 12.5 Skenario ancaman yang harus diuji

-   Pengguna mengakses akun/transaksi pengguna lain dengan mengubah ID.
-   Panel Filament diakses oleh sesi yang tidak berwenang.
-   CSRF atau request perubahan data palsu.
-   SQL injection pada input pencarian/filter.
-   Formula injection pada CSV.
-   Posting ganda akibat klik dua kali atau pengulangan request.
-   Race condition pada transfer atau pembayaran tagihan berulang.
-   Kebocoran `.env`, dump database, atau log.
-   Mode debug tidak aman di produksi.

## 13. Keandalan dan Integritas Data

-   Bungkus operasi multi-record, terutama transfer dan transaksi
    terkait target tabungan, dalam transaksi database.
-   Kunci baris terkait atau gunakan kontrol konkurensi aman jika
    diperlukan.
-   Pertimbangkan idempotency key atau strategi deduplikasi untuk
    operasi yang mungkin diulang.
-   Jangan mengirim respons sukses sebelum commit database selesai.
-   Gunakan foreign key dan aturan penghapusan yang disengaja.
-   Tambahkan pengujian otomatis untuk atomicity transfer dan rollback.
-   Gunakan migration dan seeder sebagai sumber kebenaran skema serta
    kategori awal.
-   Jangan mengubah struktur database produksi secara manual kecuali
    melalui prosedur darurat terdokumentasi.
-   Backup belum dianggap andal sampai proses restore berhasil diuji.

## 14. Kinerja dan Skalabilitas

Walaupun aplikasi digunakan satu orang, hindari desain yang membuat
akurasi dan pemeliharaan menjadi mahal.

-   Tambahkan indeks berdasarkan jalur kueri umum: pemilik, tanggal
    transaksi, akun, kategori, dan status.
-   Lakukan pagination pada riwayat transaksi.
-   Agregasikan laporan melalui SQL jika sesuai dan hindari N+1 query.
-   Eager-load relasi yang digunakan view.
-   Gunakan cache hanya bila diperlukan dan definisikan aturan
    invalidasi.
-   Stream atau antrekan ekspor besar jika volume data meningkat.
-   Lakukan pengukuran sebelum optimasi; hindari microservices, event
    bus, dan distributed cache terlalu dini.

Target nonfungsional awal: - Waktu respons p95 dashboard \<= 2 detik
pada volume data pribadi yang representatif. - Halaman daftar standar
p95 \<= 2 detik pada volume data representatif. - Tidak ada kueri daftar
transaksi tanpa batas. - Tidak ada N+1 query pada layar utama setelah
peninjauan.

## 15. Aksesibilitas dan Lokalisasi

-   Gunakan landmark semantik, heading, label, dan deskripsi form.
-   Pastikan navigasi keyboard dan fokus terlihat.
-   Pertahankan kontras aksesibel dan jangan gunakan warna sebagai
    satu-satunya indikator status.
-   Grafik harus memiliki ringkasan atau tabel aksesibel.
-   Gunakan Bahasa Indonesia sebagai bahasa bawaan dengan format mata
    uang/tanggal konsisten.
-   Simpan nominal, timestamp, dan tanggal sebagai data terstruktur;
    jangan menyimpan string mata uang yang sudah diformat.
-   Gunakan perhitungan tanggal yang memperhatikan zona waktu, khususnya
    gajian dan jatuh tempo.

## 16. Strategi Pengujian

### 16.1 Unit test

Area unit test wajib: - Perhitungan saldo akun. - Dampak
pemasukan/pengeluaran/transfer. - Laporan arus kas. - Pemakaian anggaran
dan batas periode. - Safe-to-Spend dengan input yang belum lengkap dan
pencegahan hitung ganda. - Perhitungan tanggal gajian pada akhir bulan,
tahun kabisat, dan batas zona waktu. - Perilaku kontribusi/penarikan
target tabungan. - Presisi desimal dan aturan pembulatan.

### 16.2 Feature test

-   Perilaku login/logout/reset kata sandi.
-   Middleware autentikasi.
-   CRUD akun, kategori, transaksi, anggaran, tagihan, dan target.
-   Otorisasi kepemilikan untuk setiap resource.
-   Rollback transaksi database ketika transfer gagal.
-   Pengaitan pembayaran tagihan dan pencegahan duplikasi.
-   Rekonsiliasi dashboard/laporan.
-   Filter ekspor CSV dan mitigasi formula injection.
-   Kontrol akses Filament.

### 16.3 Pengujian browser/manual

-   Viewport ponsel 360px, viewport Android umum, tablet, dan desktop.
-   Navigasi keyboard dan validasi form.
-   Keadaan kosong, memuat, error, dan sukses.
-   Nama kategori panjang dan nominal mata uang besar.
-   Pergantian bulan dan tanggal jatuh tempo.
-   Klik ulang/pengiriman ulang form transaksi.
-   Simulasi backup dan restore.

### 16.4 Skenario data uji

Minimal mencakup: 1. Pengguna baru tanpa akun. 2. Akun dengan saldo awal
nol. 3. Banyak akun dengan transfer. 4. Pemasukan diikuti pengeluaran.
5. Pengeluaran pada hari terakhir bulan. 6. Ambang anggaran terlampaui
oleh satu transaksi. 7. Tagihan jatuh tempo hari ini, terlambat, lunas,
dan dibatalkan. 8. Kontribusi tabungan yang terkait transfer akun. 9.
Safe-to-Spend bernilai negatif. 10. Kategori/akun arsip yang dirujuk
riwayat. 11. Transaksi dibatalkan. 12. Pengguna mencoba mengakses data
milik pengguna lain.

## 17. Observabilitas dan Operasional

-   Gunakan log Laravel dengan tingkat detail berbeda per lingkungan.
-   Produksi harus menonaktifkan debug mode.
-   Pantau error aplikasi dan kegagalan task terjadwal tanpa mencatat
    payload sensitif.
-   Dokumentasikan deployment, migration, keterbatasan rollback, task
    terjadwal, dan pemulihan backup.
-   Jika pengingat atau pekerjaan berulang diaktifkan, konfigurasikan
    Laravel Scheduler dan pantau kegagalan.
-   Buat daftar periksa operasional sederhana untuk pembaruan
    dependensi, backup database, verifikasi restore, dan review
    keamanan.

## 18. Strategi Lingkungan dan Deployment

Lingkungan: - **Lokal:** komputer developer dengan data seed
non-sensitif. - **Test/CI:** pengujian otomatis dengan database
terisolasi. - **Staging:** konfigurasi mirip produksi dan data sintetis
jika hosting memungkinkan. - **Produksi:** HTTPS, kredensial terbatas,
debug nonaktif, backup, dan kontrol akses.

Daftar periksa rilis: - \[ \] Variabel lingkungan dikonfigurasi secara
aman. - \[ \] Backup database dibuat sebelum migration berisiko. - \[ \]
Migration diuji terhadap data staging. - \[ \] Seluruh pengujian
otomatis lulus. - \[ \] Tidak ada isu keamanan kritis/tinggi yang masih
terbuka. - \[ \] Debug mode dinonaktifkan. - \[ \] HTTPS dan cookie aman
diverifikasi. - \[ \] Scheduler/queue dikonfigurasi jika diperlukan. -
\[ \] Langkah backup dan restore sudah diverifikasi. - \[ \] Health
check dan log diperiksa setelah deployment.

Jangan menganggap setiap migration dapat dibalik dengan aman. Untuk
perubahan skema destruktif, gunakan tahapan expand/migrate/contract dan
pertahankan jalur pemulihan.

## 19. Rencana Pengiriman dan Definition of Done

### Fase 0 --- Produk dan setup

Hasil: - Konfirmasi keputusan pada Bagian 21. - Inisialisasi proyek
Laravel dan batasan versi. - Konfigurasi MySQL, gaya kode, pengujian,
file environment, dan CI. - Buat token desain awal dan layout Blade.

Kriteria keluar: - Aplikasi berjalan secara lokal. - Migration dapat
dijalankan dari database kosong. - Perintah pengujian otomatis
terdokumentasi. - Rahasia tidak masuk Git.

### Fase 1 --- Autentikasi dan fondasi keuangan

Hasil: - Autentikasi dan preferensi profil. - Akun dan kategori. - Model
saldo awal. - Aturan domain transaksi inti.

Kriteria keluar: - Unit/feature test inti lulus. - Pembatasan
kepemilikan diterapkan. - Saldo awal dan transaksi dapat direkonsiliasi.

### Fase 2 --- Transaksi dan dashboard

Hasil: - CRUD/pembatalan transaksi. - Alur transfer. - Dashboard dan
aktivitas terbaru. - Navigasi seluler responsif.

Kriteria keluar: - Pengujian transfer dan arus kas lulus. - Total
dashboard cocok dengan catatan sumber. - Alur inti berfungsi pada lebar
360px.

### Fase 3 --- Anggaran dan perencanaan

Hasil: - Anggaran, tagihan, target tabungan. - Layanan Safe-to-Spend dan
UI rinciannya. - Laporan dan ekspor CSV.

Kriteria keluar: - Tidak ada hitung ganda pada skenario yang
ditentukan. - Ekspor sesuai catatan yang difilter. - Pengujian batas
periode lulus.

### Fase 4 --- Kualitas, operasional, dan rilis

Hasil: - Review keamanan. - Review aksesibilitas/responsif. - Panduan
backup/restore. - Deployment produksi dan smoke test.

Kriteria keluar: - Daftar periksa rilis selesai. - Tidak ada defect
kritis/tinggi yang terbuka. - Simulasi restore berhasil. - Keterbatasan
yang diketahui terdokumentasi.

### Definition of Done untuk setiap fitur

Fitur dianggap selesai hanya jika: - Kebutuhan dan kriteria penerimaan
terpenuhi. - Validasi dan otorisasi diterapkan di server. - Pengujian
otomatis mencakup perilaku yang diharapkan dan kasus tepi yang
relevan. - UI berfungsi pada seluler dan desktop. - Keadaan
kosong/error/sukses ditangani. - Tidak ada data sensitif yang terekspos
di log. - Dokumentasi diperbarui jika perilaku atau setup berubah. -
Kode telah diformat dan ditinjau sesuai konvensi proyek.

## 20. Risiko dan Mitigasi

  -----------------------------------------------------------------------
  Risiko                  Dampak                  Mitigasi
  ----------------------- ----------------------- -----------------------
  Perhitungan uang keliru Tinggi                  Domain service
                                                  terpusat, logika aman
                                                  desimal, pengujian
                                                  rekonsiliasi

  Transfer/tabungan       Tinggi                  Jenis transaksi
  dihitung ganda                                  eksplisit, catatan
                                                  tertaut, pengujian
                                                  khusus

  Estimasi Safe-to-Spend  Tinggi                  Rincian transparan,
  keliru                                          asumsi eksplisit,
                                                  peringatan data belum
                                                  lengkap

  Kehilangan data         Tinggi                  Backup, ekspor, restore
                                                  teruji, migration
                                                  hati-hati

  Akses data tanpa izin   Tinggi                  Policy, pembatasan
                                                  pemilik, pengujian
                                                  negatif otorisasi

  Perluasan ruang lingkup Sedang                  Batas rilis P0/P1/P2

  Filament melewati       Tinggi                  Service bersama,
  aturan domain                                   policy, aksi terbatas

  Kesalahan zona          Sedang                  Zona waktu eksplisit,
  waktu/akhir bulan                               pengujian batas

  Beban input manual      Sedang                  UI input cepat, default
                                                  yang masuk akal, impor
                                                  CSV di masa depan

  Arsitektur berlebihan   Sedang                  Monolit modular, tanpa
                                                  microservices prematur
  -----------------------------------------------------------------------

## 21. Keputusan Terbuka

Putuskan sebelum atau selama fase implementasi yang relevan; jangan
mengasumsikan perilaku secara diam-diam.

  -----------------------------------------------------------------------
  ID                      Keputusan               Rekomendasi bawaan
  ----------------------- ----------------------- -----------------------
  OD-01                   Aplikasi privat untuk   Akun disiapkan pemilik;
                          pemilik atau            nonaktifkan pendaftaran
                          pendaftaran publik?     publik

  OD-02                   Target deployment?      Hosting Laravel
                                                  terkelola atau VPS
                                                  dengan HTTPS dan backup

  OD-03                   Pendekatan CSS?         Pilih Tailwind CSS atau
                                                  Bootstrap; jangan
                                                  campur tanpa alasan

  OD-04                   Framework pengujian?    Pest atau PHPUnit,
                                                  dipilih saat setup

  OD-05                   Model saldo akun?       Hitung dari saldo awal
                                                  dan transaksi tercatat;
                                                  optimalkan kemudian

  OD-06                   Dukungan kartu          Tunda akuntansi kartu
                          kredit/utang di MVP?    kredit penuh; catat
                                                  kewajiban dengan benar
                                                  jika diperlukan

  OD-07                   Transaksi berulang?     P1 kecuali dibutuhkan
                                                  untuk penggunaan harian

  OD-08                   Pengingat email?        Tunda; gunakan
                                                  indikator jatuh tempo
                                                  dalam aplikasi terlebih
                                                  dahulu

  OD-09                   Snapshot kekayaan       P1; valuasi manual
                          bersih?                 setelah ledger inti
                                                  stabil

  OD-10                   Kebijakan penghapusan   Batalkan/arsipkan
                          data?                   catatan keuangan;
                                                  kebijakan hapus akun
                                                  ditentukan terpisah

  OD-11                   Konfigurasi tanggal     Izinkan tanggal gajian
                          gajian?                 berikutnya secara
                                                  eksplisit dan hari
                                                  gajian bulanan opsional

  OD-12                   Mode offline?           Tidak untuk MVP;
                                                  sediakan keadaan error
                                                  dan ekspor/backup
  -----------------------------------------------------------------------

## 22. Pedoman Rekayasa dan Pengembangan Berbantuan AI

Aturan ini sebaiknya dimasukkan ke panduan developer di repository dan
diikuti selama implementasi.

1.  Baca PRD ini sebelum mengimplementasikan fitur.
2.  Implementasikan satu vertical slice pada satu waktu: migration,
    model, operasi domain, validasi, otorisasi, UI, pengujian,
    dokumentasi.
3.  Jangan membangun semua tabel dan layar tanpa menguji invarian
    keuangan.
4.  Jangan menempatkan perhitungan keuangan hanya di Blade, JavaScript,
    controller, atau callback Filament.
5.  Jangan menggunakan floating-point untuk nominal uang.
6.  Jangan memercayai ID dari browser; verifikasi kepemilikan dan relasi
    di server.
7.  Jangan menggunakan mass assignment tanpa kebijakan
    `$fillable`/`$guarded` dan validasi yang jelas.
8.  Jangan menonaktifkan CSRF atau otorisasi hanya agar pengujian lulus.
9.  Jangan menggunakan MD5/SHA1 untuk kata sandi.
10. Jangan membuat lapisan repository generik untuk setiap model kecuali
    benar-benar menyelesaikan masalah; ikuti konvensi Laravel/Eloquent.
11. Utamakan perubahan kecil yang mudah ditinjau dan jalankan pengujian
    terarah setelah tiap perubahan.
12. Sebelum mengubah skema, periksa migration dan dampak terhadap data
    yang sudah ada.
13. Hindari migration destruktif tanpa rencana backup/pemulihan.
14. Seed hanya kategori bawaan/data sintetis; jangan commit catatan
    keuangan pribadi.
15. Jauhkan `.env`, kredensial, hasil ekspor, dan dump database dari
    Git.
16. Jika kebutuhan ambigu, dokumentasikan asumsi dan tambahkan pengujian
    untuk perilaku yang dipilih.
17. Perbarui PRD atau ADR saat keputusan produk/arsitektur penting
    berubah.
18. Verifikasi kompatibilitas paket melalui dokumentasi rilis resmi,
    bukan tebakan versi.
19. Hindari API dan integrasi yang tidak perlu; pertahankan aplikasi
    server-rendered Blade pada tahap awal.
20. Perlakukan Filament sebagai jalur masuk lain ke aturan domain yang
    sama, bukan implementasi logika bisnis terpisah.

## 23. Rekomendasi Architecture Decision Record (ADR)

Buat direktori `docs/adr/` dan catat keputusan yang memengaruhi
pemeliharaan jangka panjang.

-   `ADR-001`: Laravel + Blade sebagai monolit modular.
-   `ADR-002`: Representasi desimal MySQL dan strategi perhitungan uang.
-   `ADR-003`: Siklus hidup transaksi, transfer, dan perhitungan saldo
    akun.
-   `ADR-004`: Provisioning akun privat dan autentikasi.
-   `ADR-005`: Kontrol akses Filament dan operasi pemeliharaan yang
    diizinkan.
-   `ADR-006`: Asumsi Safe-to-Spend dan kebijakan akun yang dapat
    dibelanjakan.
-   `ADR-007`: Kebijakan backup, ekspor, dan pemulihan.

Setiap ADR harus mencakup konteks, keputusan, alternatif yang
dipertimbangkan, konsekuensi, dan tanggal.

## 24. Glosarium

  -----------------------------------------------------------------------
  Istilah                             Definisi
  ----------------------------------- -----------------------------------
  Akun                                Tempat uang atau saldo keuangan
                                      dicatat

  Saldo awal                          Nilai dasar akun pada awal
                                      pencatatan

  Transaksi                           Peristiwa keuangan yang dicatat

  Transfer                            Perpindahan uang antar akun milik
                                      pengguna

  Arus kas                            Pemasukan dikurangi pengeluaran
                                      yang memenuhi syarat dalam suatu
                                      periode

  Anggaran                            Batas pengeluaran yang direncanakan
                                      untuk kategori dan periode

  Tagihan                             Kewajiban yang diharapkan dengan
                                      tanggal jatuh tempo

  Target tabungan                     Nominal yang dituju untuk kebutuhan
                                      tertentu

  Safe-to-Spend                       Estimasi dana diskresioner yang
                                      tersedia dalam rentang perencanaan

  Aset                                Sesuatu yang memiliki nilai
                                      keuangan dan dimiliki pengguna

  Kewajiban                           Nominal yang harus dibayar pengguna

  Kekayaan bersih                     Total aset dikurangi total
                                      kewajiban

  Void/pembatalan                     Menandai catatan tidak berlaku
                                      sambil mempertahankan jejak audit

  P0/P1/P2                            Prioritas rilis: wajib MVP /
                                      iterasi berikutnya / masa depan
  -----------------------------------------------------------------------

## Lampiran A --- Daftar Periksa Penerimaan MVP

-   [ ] Autentikasi privat berfungsi; pendaftaran publik nonaktif
    kecuali disetujui secara eksplisit.
-   [ ] Saldo awal akun dapat direkonsiliasi.
-   [ ] Pemasukan menambah akun yang benar.
-   [ ] Pengeluaran mengurangi akun yang benar.
-   [ ] Transfer mengurangi sumber dan menambah tujuan secara atomik.
-   [ ] Transfer dikecualikan dari total pemasukan/pengeluaran.
-   [ ] Perubahan/pembatalan transaksi memperbarui metrik turunan dengan
    benar.
-   [ ] Anggaran menggunakan periode dan pengeluaran yang memenuhi
    syarat.
-   [ ] Pembayaran tagihan tidak membuat transaksi pengeluaran tertaut
    ganda.
-   [ ] Kontribusi tabungan tidak menghitung transfer dua kali.
-   [ ] Safe-to-Spend menampilkan asumsi dan lulus pengujian pencegahan
    hitung ganda.
-   [ ] Ekspor CSV mengikuti filter dan mencegah formula injection.
-   [ ] Semua catatan pribadi dibatasi berdasarkan otorisasi.
-   [ ] Akses Filament dibatasi secara eksplisit.
-   [ ] Layar utama berfungsi pada viewport 360px.
-   [ ] Pengujian otomatis lulus di lingkungan bersih.
-   [ ] Pemulihan backup berhasil diuji.
-   [ ] Debug produksi nonaktif dan HTTPS diwajibkan.

## Lampiran B --- Sprint Implementasi Pertama

Urutan sprint awal yang direkomendasikan:

1.  Buat proyek Laravel dan kunci versi PHP/Laravel/Filament yang
    kompatibel.
2.  Konfigurasikan MySQL dan database pengujian.
3.  Siapkan Git, formatter, analisis statis, dan perintah pengujian
    otomatis.
4.  Implementasikan autentikasi dan provisioning akun privat.
5.  Buat migration untuk pengguna/preferensi, akun, kategori, dan
    transaksi.
6.  Implementasikan domain service yang aman untuk nominal uang untuk
    pemasukan, pengeluaran, dan transfer.
7.  Tulis pengujian perhitungan saldo, atomicity transfer, otorisasi,
    dan pembatalan.
8.  Bangun shell Blade mobile dan layar input transaksi.
9.  Bangun dashboard setelah perhitungan dasar diuji.
10. Tambahkan anggaran, tagihan, target, dan Safe-to-Spend melalui
    vertical slice berikutnya.

**Milestone pertama bukan dashboard yang indah. Milestone pertama adalah
fondasi keuangan yang benar dan teruji, yang dapat dipercaya oleh
dashboard.**
