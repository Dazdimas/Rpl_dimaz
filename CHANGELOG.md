# Catatan Perubahan (Changelog)

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/). Berlaku untuk codebase PHP (branch `php-keuangan`).

## [1.1.0] - 2026-11-05 - Pengerasan Keamanan (Pertemuan 8)

### Keamanan

Kode `SEC-xx` merujuk ke [Laporan Keamanan](docs/LAPORAN_KEAMANAN.md).

- **CORS `*` diganti daftar izin origin**, dan permintaan dari origin lain ditolak server dengan HTTP 403 (SEC-01 s.d. SEC-04).
- **Endpoint tulis hanya menerima `application/json`**, sehingga form/`text/plain` lintas situs tidak bisa memicu CSRF (SEC-03).
- **File data dipindah dari `backend/data.json` ke `storage/data.json`**, di luar web root, agar tidak bisa diunduh lewat URL (SEC-05).
- **Validasi input diperketat**: tanggal harus nyata, nominal dibatasi 1 s.d. 999.999.999.999, `keterangan` maksimal 255 karakter dan UTF-8 valid, body maksimal 16 KB (SEC-07, SEC-08, SEC-11, SEC-12).
- **Penulisan data dibuat atomik dan gagal dengan aman**: byte UTF-8 rusak tidak lagi mengosongkan file data, dan file rusak tidak lagi ditimpa data contoh (SEC-09, SEC-15).
- **Pesan error tidak lagi memuat path atau stack trace**. Klien menerima pesan umum dan kode referensi (SEC-10).
- **Header keamanan ditambahkan** (`nosniff`, CSP, `X-Frame-Options`, `Referrer-Policy`, `Cache-Control`) dan `X-Powered-By` dihapus (SEC-13, SEC-14).
- **Frontend**: Chart.js dari CDN dikunci dengan SRI, CSP ditambahkan, dan seluruh field tabel di-escape (SEC-17).
- **Penguncian file per request** mencegah update hilang saat ada permintaan bersamaan.

### Ditambahkan

- Log aman JSON Lines di `storage/logs/app.log`, dengan sensor rahasia dan data keuangan (SEC-18).
- `tests/run_tests.php`: 60 unit & integration test tanpa dependensi.
- `tests/security_scan.php`: pemindai keamanan dengan 19 pemeriksaan yang bisa diulang.
- `scripts/build.php` dan `scripts/smoke_test.php`: build paket demo dan pemeriksaan demo yang berjalan.
- Variabel lingkungan `KEUANGAN_DATA_FILE`, `KEUANGAN_LOG_FILE`, `KEUANGAN_ALLOWED_ORIGINS`.
- Dokumen `docs/LAPORAN_KEAMANAN.md`, `docs/LOG_DAN_ERROR.md`, `docs/VERIFIKASI.md`, dan folder `docs/bukti/`.

### Diubah (perlu diperhatikan)

- **API lebih ketat**: `tanggal`, `jenis`, atau `kategori` yang tidak valid kini ditolak HTTP 400. Sebelumnya diganti diam-diam dengan nilai bawaan.
- **API**: `POST`/`PUT` tanpa `Content-Type: application/json` kini ditolak HTTP 415.
- **Data lama berpindah otomatis**: `backend/data.json` dipindah ke `storage/data.json` saat aplikasi pertama kali berjalan.
- **README** diperbarui dengan panduan fresh clone, dan status fitur disesuaikan dengan kode yang sebenarnya.

### Diperbaiki

- Error 404 `favicon.ico` di konsol browser.

### Belum Ditangani

- **Autentikasi & otorisasi belum ada (SEC-19).** Aplikasi hanya aman dijalankan di `localhost`. Lihat [risiko terbuka](docs/LAPORAN_KEAMANAN.md#4-risiko-yang-masih-terbuka).

## [1.0.0] - 2026-10-29

- Fitur kategori, edit/hapus transaksi, filter, grafik pengeluaran, dan batas anggaran bulanan.
