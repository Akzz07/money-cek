# Panduan Operasional: Backup & Restore Basis Data Monetra

Dokumen ini memuat standar prosedur operasional (*Standard Operating Procedure* / SOP) pencadangan, verifikasi integritas, dan pemulihan bencana (*disaster recovery*) untuk aplikasi Monetra mengacu pada **PRD §12.4, §13, §17, dan ADR-007**.

---

## 1. Skrip Pencadangan Otomatis (`scripts/backup-db.sh`)

Skrip ini melakukan dump database MySQL/MariaDB secara konsisten menggunakan opsi `--single-transaction`, melakukan kompresi `gzip`, dan menghasilkan checksum SHA-256 untuk memverifikasi integritas berkas.

### Cara Menjalankan
```bash
./scripts/backup-db.sh
```

### Parameter Konfigurasi
Secara bawaan, skrip membaca kredensial dari file `.env`:
- `DB_HOST` (default: 127.0.0.1)
- `DB_PORT` (default: 8000)
- `DB_DATABASE` (default: mymoney)
- `DB_USERNAME` (default: root)
- `DB_PASSWORD`

Hasil pencadangan akan disimpan di direktori `backups/`:
- `backups/monetra_mymoney_YYYYMMDD_HHMMSS.sql.gz`
- `backups/monetra_mymoney_YYYYMMDD_HHMMSS.sql.gz.sha256`

---

## 2. Skrip Pemulihan Data (`scripts/restore-db.sh`)

Skrip ini memulihkan arsip dump SQL ke database target setelah memverifikasi keaslian berkas melalui checksum SHA-256.

### Cara Menjalankan

#### A. Pemulihan Uji Coba (Simulasi Terisolasi)
Untuk memverifikasi keandalan file cadangan tanpa mengganggu database produksi/utama:
```bash
./scripts/restore-db.sh backups/monetra_mymoney_YYYYMMDD_HHMMSS.sql.gz mymoney_restore_test
```

#### B. Pemulihan Penuh ke Database Utama (Disaster Recovery)
```bash
./scripts/restore-db.sh backups/monetra_mymoney_YYYYMMDD_HHMMSS.sql.gz
```

---

## 3. Jadwal Otomatisasi (Cron Job Server)
Untuk menjalankan pencadangan setiap hari pada pukul 02:00 dini hari:
```cron
0 2 * * * /bin/bash /path/to/Testing/scripts/backup-db.sh >> /path/to/Testing/storage/logs/backup.log 2>&1
```

---

## 4. Retensi & Kebersihan
- Simpan cadangan harian selama 14 hari terakhir.
- Simpan satu cadangan bulanan selama 12 bulan terakhir.
- Direktori `backups/` telah dimasukkan ke dalam `.gitignore` sehingga tidak akan pernah terdorong ke repositori Git publik maupun privat.
