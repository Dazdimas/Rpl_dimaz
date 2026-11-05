# Laporan Uji Keamanan

| | |
|---|---|
| **Aplikasi** | Sistem Pencatat Keuangan Harian (PHP) |
| **Branch / commit dasar** | `php-keuangan` / `b26ab75` |
| **Tanggal uji** | 5 November 2026 |
| **Lingkungan** | Windows 11, PHP 8.2.12 (XAMPP), Microsoft Edge headless |
| **Perubahan terkait** | [CHANGELOG.md](../CHANGELOG.md), versi 1.1.0 |

## 1. Ringkasan

Kode awal gagal pada 16 dari 19 pemeriksaan. Setelah perbaikan, seluruh 16 temuan tersebut tertutup. Satu risiko masih terbuka: **aplikasi belum memiliki login**, sehingga hanya aman dijalankan di `localhost` dan belum siap dipublikasikan ke internet.

| Hasil | Sebelum | Sesudah |
|---|---:|---:|
| PASS | 2 | **18** |
| FAIL | 16 | **0** |
| OPEN (risiko diketahui) | 1 | **1** |
| Total pemeriksaan | 19 | 19 |

Bukti mentah: [`scan_sebelum.txt`](bukti/scan_sebelum.txt), [`scan_sesudah.txt`](bukti/scan_sesudah.txt), [`reproduksi_versi_lama.txt`](bukti/reproduksi_versi_lama.txt).

## 2. Metode dan Batasannya

| Pemeriksaan | Alat | Hasil |
|---|---|---|
| Uji dinamis API & pemeriksaan statis frontend | `tests/security_scan.php`, 19 pemeriksaan | Lihat bagian 3 |
| XSS & CSP di browser sungguhan | Skrip E2E Puppeteer + Edge | Payload tampil sebagai teks, tanpa pelanggaran CSP |
| Kerentanan dependensi | Database [OSV](https://osv.dev), `chart.js@4.4.1` | Tidak ada advisory per tanggal uji |
| Kredensial di riwayat git | Pencarian pola pada semua branch | Tidak ada rahasia. Hanya `DB_PASS=` kosong dan nama variabel `csrf_token`. `.env` dan `data.json` tidak pernah di-commit |

**Batasan:**

- Pemindai ditulis khusus untuk aplikasi ini, jadi hanya menguji skenario yang terdaftar. Hasil lolos bukan jaminan bebas kerentanan.
- OWASP ZAP, Semgrep, dan Gitleaks tidak tersedia di lingkungan uji dan **tidak dijalankan**. Pencarian kredensial memakai pola `grep` dan cakupannya lebih sempit daripada Gitleaks.
- Tingkat risiko adalah penilaian penulis untuk aplikasi tanpa login yang berjalan di mesin lokal, bukan skor CVSS resmi.

Cara mengulang pemindaian:

```bash
php tests/security_scan.php
```

## 3. Temuan dan Perbaikan

| ID | Risiko | Temuan pada versi lama | Bukti sebelum | Perbaikan | Bukti sesudah |
|---|---|---|---|---|---|
| SEC-09 | **Kritis** | Satu byte UTF-8 rusak menghapus seluruh data | File data 0 byte | Hanya menerima JSON valid; simpan atomik | File utuh (743 byte) |
| SEC-08 | **Tinggi** | Nominal `PHP_INT_MAX` membuat aplikasi fatal permanen | Setiap GET berisi fatal error | Batas nominal 999.999.999.999 | HTTP 200, JSON valid |
| SEC-01 | **Tinggi** | Situs apa pun bisa membaca data keuangan | `Allow-Origin: *` | Daftar izin origin | HTTP 403, tanpa header |
| SEC-02 | **Tinggi** | Preflight dari origin asing diizinkan | `Allow-Origin: *` | Daftar izin origin | HTTP 403 |
| SEC-03 | **Tinggi** | CSRF: situs asing bisa menambah transaksi | Transaksi 3 → 4 | Wajib JSON; origin dicek server | HTTP 403, tetap 3 |
| SEC-04 | **Tinggi** | Situs asing bisa menghapus transaksi | Transaksi 3 → 2 | Origin dicek server | HTTP 403, tetap 3 |
| SEC-05 | **Tinggi** | `data.json` bisa diunduh langsung | HTTP 200 | Dipindah ke `storage/` | HTTP 404 |
| SEC-15 | **Tinggi** | File data rusak ditimpa data contoh | File ditimpa | Dilaporkan sebagai error | HTTP 500, file utuh |
| SEC-10 | Sedang | Error membocorkan path server & stack trace | Path tampil | Error ke log; klien terima kode referensi | Bersih |
| SEC-11 | Sedang | Nominal `"12abc"`, `"1e30"`, `[5]`, `true`, `1.5` lolos | 6 dari 8 lolos | Validasi tipe dan rentang | 8 dari 8 ditolak |
| SEC-12 | Sedang | Keterangan tanpa batas panjang | 1 MB diterima | Batas 255 karakter, body 16 KB | HTTP 413 |
| SEC-17 | Sedang | CDN tanpa SRI, tanpa CSP, ada field tanpa escape | 3 masalah | SRI, CSP, escape | Lengkap |
| SEC-18 | Sedang | Tidak ada log aktivitas | Tidak ada log | [Log aman](LOG_DAN_ERROR.md) | Bebas data sensitif |
| SEC-07 | Rendah | Tanggal mustahil `2026-99-99` diterima | HTTP 200 | Validasi `checkdate` | HTTP 400 |
| SEC-13 | Rendah | Header keamanan tidak ada | 4 header kurang | Header ditambahkan | Lengkap |
| SEC-14 | Rendah | Versi PHP diumumkan | `PHP/8.2.12` | `X-Powered-By` dihapus | Tidak ada |

Sudah aman sejak awal:

- **SEC-06:** `.env`, `.git/config`, dan path traversal tidak terbuka.
- **SEC-16:** payload XSS/SQLi tetap teks biasa di respons JSON, dan backend tidak memakai SQL.

Perbaikan yang **tidak** tercakup pemindai dinamis: penguncian file diuji lewat unit test dengan 4 proses × 25 penulisan. Kondisi balapan tidak bisa direproduksi pada versi lama, karena `php -S` di Windows melayani request satu per satu, sehingga risikonya berasal dari analisis kode.

## 4. Risiko yang Masih Terbuka

| ID | Risiko | Dampak | Mitigasi saat ini | Rekomendasi |
|---|---|---|---|---|
| **SEC-19** | **Tinggi** jika terekspos jaringan | Tanpa login, siapa pun yang menjangkau server bisa membaca dan mengubah semua data. Header `Host` juga tidak divalidasi, sehingga DNS rebinding belum dicegah (belum diuji) | Jalankan hanya di `localhost` | Bangun login & sesi, lalu validasi `Host` |
| R-02 | Tinggi jika dipublikasikan | `php -S` tidak dirancang untuk produksi dan tanpa HTTPS | Hanya untuk demo lokal | Gunakan Apache/Nginx + PHP-FPM + TLS |
| R-03 | Sedang | PHP 8.2.12 (rilis Oktober 2023) bukan patch terbaru | Hanya lokal | Perbarui PHP/XAMPP |
| R-04 | Rendah | Tidak ada pembatasan laju permintaan | Hanya lokal | Tambahkan di web server |
| R-05 | Rendah | Log tidak dirotasi dan bisa terus membesar | Volume demo kecil | Tambahkan rotasi log |
| R-06 | Rendah | `storage/data.json` tidak terenkripsi | Tidak di-commit; di luar web root | Enkripsi disk |

## 5. Kesimpulan

Layak untuk **demo lokal**. **Belum layak dipublikasikan ke internet** sampai SEC-19 dan R-02 diselesaikan.
