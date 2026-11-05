# Log Aman dan Analisis Penyebab Error

Semua contoh di bawah adalah keluaran asli dari pengujian 5 November 2026, bukan rekaan. Sumber: [`contoh_log.jsonl`](bukti/contoh_log.jsonl) dan [`reproduksi_versi_lama.txt`](bukti/reproduksi_versi_lama.txt).

## 1. Aturan Log Aman

Log ditulis ke `storage/logs/app.log` (tidak di-commit), satu event per baris dalam format JSON. Sanitasi ada di fungsi `sanitasiLog()` dan `sanitasiTeksLog()` pada `backend/config.php`.

| Dicatat | Tidak dicatat atau disensor | Cara |
|---|---|---|
| Waktu, level, nama event | `password`, `token`, `api_key`, `cookie`, `authorization` | Diganti `[REDACTED]` |
| Metode dan path (tanpa query string) | `nominal`, `keterangan`, `saldo`, `budget` | Diganti `[MASKED]` |
| ID, jenis, dan kategori transaksi | Path folder server | Diganti `<app>` atau `<path>` |
| Alasan penolakan dan kode status | Alamat email | Diganti `[EMAIL]` |
| Kode referensi error | IP pengguna dan isi body request | Tidak pernah ditulis |

Teks dipotong maksimal 200 karakter. Karena setiap event di-encode JSON, input berisi baris baru tidak bisa memalsukan baris log.

## 2. Contoh Log Aman

**Aktivitas normal.** Transaksi uji bernilai Rp 150.000 dengan keterangan berisi payload XSS hanya tercatat sebagai ID, jenis, dan kategori:

```json
{"waktu":"2026-11-05T02:44:56+01:00","level":"INFO","event":"transaksi.ditambah","metode":"POST","path":"/api.php","id":4,"jenis":"pengeluaran","kategori":"Makanan"}
{"waktu":"2026-11-05T02:44:56+01:00","level":"INFO","event":"transaksi.diubah","metode":"PUT","path":"/api.php","id":4}
{"waktu":"2026-11-05T02:44:56+01:00","level":"INFO","event":"budget.diubah","metode":"POST","path":"/api.php","budget_bulanan":"[MASKED]"}
{"waktu":"2026-11-05T02:44:56+01:00","level":"INFO","event":"transaksi.dihapus","metode":"DELETE","path":"/api.php","id":4}
```

**Permintaan ditolak.** Baris kedua berasal dari request berisi `keterangan: "password=RahasiaUji123"`. Nilai itu tidak ikut tercatat:

```json
{"waktu":"2026-11-05T02:44:57+01:00","level":"WARNING","event":"permintaan.ditolak","metode":"DELETE","path":"/api.php","alasan":"origin_tidak_diizinkan","status":403}
{"waktu":"2026-11-05T02:44:57+01:00","level":"WARNING","event":"permintaan.ditolak","metode":"POST","path":"/api.php","alasan":"validasi","status":400}
{"waktu":"2026-11-05T02:44:57+01:00","level":"WARNING","event":"permintaan.ditolak","metode":"POST","path":"/api.php","alasan":"content_type","status":415}
```

**Error server.** Klien hanya menerima pesan umum dan kode referensi, yang bisa dicocokkan dengan log:

```json
{"waktu":"2026-11-05T02:44:57+01:00","level":"ERROR","event":"error.tidak_tertangani","metode":"GET","path":"/api.php","ref":"4aee0ebd","jenis":"RuntimeException","pesan":"File data rusak atau tidak bisa dibaca.","lokasi":"config.php:190"}
```

```json
{"success":false,"message":"Terjadi kesalahan pada server.","ref":"4aee0ebd"}
```

**Verifikasi:** pencarian `RahasiaUji123`, `150000`, `200000`, `onerror`, dan `Users` pada log hasil uji menemukan **0 baris**. Test SEC-18 dan 6 unit test log aman berstatus PASS.

### Perbandingan dengan versi lama

Versi lama tidak punya log, dan error tampil langsung ke klien. Keluaran ini memperlihatkan struktur folder server (path disingkat menjadi `<app>`):

```text
Fatal error:  Uncaught TypeError: hitungTotal(): Return value must be of type int, float returned in <app>\backend\config.php:75
```

## 3. Analisis Penyebab Error

### RCA-1: Seluruh data hilang akibat satu byte rusak (SEC-09)

| | |
|---|---|
| **Gejala** | `POST` berisi `keterangan=%FF` membalas HTTP 200 dengan body kosong. File data menjadi 0 byte dan transaksi pengguna hilang, diganti data contoh |
| **Reproduksi** | Transaksi 4 → POST rusak → file 0 byte → transaksi kembali 3 data contoh |
| **Akar masalah** | Tiga kelemahan bertemu: `$_POST` diterima tanpa cek UTF-8, `json_encode()` mengembalikan `false` tanpa exception, dan `file_put_contents()` menulis nilai itu sebagai string kosong langsung ke file asli |
| **Perbaikan** | Hanya menerima `application/json`; validasi UTF-8; `JSON_THROW_ON_ERROR`; tulis ke file sementara lalu `rename` |
| **Pencegahan** | Test `gagal encode tidak mengosongkan file data`, `keterangan UTF-8 rusak ditolak`, dan SEC-09 |

### RCA-2: Aplikasi fatal permanen akibat nominal besar (SEC-08)

| | |
|---|---|
| **Gejala** | Setelah satu `POST` dengan nominal `9223372036854775807`, API dan halaman backend menampilkan `Uncaught TypeError` di setiap request |
| **Reproduksi** | Lihat bagian A pada [`reproduksi_versi_lama.txt`](bukti/reproduksi_versi_lama.txt) |
| **Akar masalah** | Nominal tanpa batas atas. Penjumlahan melewati `PHP_INT_MAX`, PHP mengubahnya menjadi `float`, dan return type `int` pada `hitungTotal()` melempar `TypeError`. Nilai itu sudah tersimpan, jadi error berulang di setiap request |
| **Perbaikan** | Nominal dibatasi 999.999.999.999 lewat `validasiBilangan()`, dan error ditangani handler global |
| **Pencegahan** | Test nominal batas atas dan `nominal 9223372036854775807 ditolak`, serta SEC-08 |

### RCA-3: Path server bocor di respons (SEC-10)

| | |
|---|---|
| **Gejala** | `keterangan` berupa array menghasilkan `Warning: Array to string conversion in ...\backend\api.php on line 52` di dalam respons, lalu transaksi tetap tersimpan |
| **Akar masalah** | Cast `(string)` tanpa cek tipe, ditambah `display_errors` aktif pada konfigurasi bawaan XAMPP |
| **Perbaikan** | Cek tipe setiap field; `display_errors` dimatikan di aplikasi; `pasangPenangananError()` mencatat rincian ke log dan mengirim kode referensi |
| **Pencegahan** | Test `keterangan array ditolak`, `error 500 generik dengan kode referensi yang ada di log`, dan SEC-10 |

### RCA-4: File data rusak ditimpa diam-diam (SEC-15)

| | |
|---|---|
| **Gejala** | Jika `data.json` rusak, aplikasi tetap membalas HTTP 200 dan penyimpanan berikutnya menimpa file dengan data contoh, sehingga peluang memulihkan data hilang |
| **Akar masalah** | `bacaData()` menganggap hasil decode yang gagal sebagai kondisi normal dan mengembalikan `dataAwal()` |
| **Perbaikan** | `bacaData()` melempar exception, API membalas HTTP 500, dan file tidak disentuh |
| **Pencegahan** | Test `file data rusak dilaporkan dan tidak ditimpa` dan SEC-15 |

### RCA-5: Error 404 `favicon.ico` saat uji E2E

| | |
|---|---|
| **Gejala** | Pemeriksaan "tanpa error konsol" gagal, 12/13, dengan `Failed to load resource: 404` |
| **Akar masalah** | Halaman tidak mendeklarasikan ikon sehingga browser meminta `/favicon.ico`. URL dipastikan dengan mencatat semua respons ≥ 400 |
| **Perbaikan** | Menambahkan `<link rel="icon" href="data:,">` |
| **Hasil** | E2E 13/13 |

### Catatan: fatal error PHP berstatus HTTP 200

Saat dijalankan pertama kali, SEC-08 keliru berstatus PASS. Dengan `display_errors` aktif, PHP mengirim fatal error dengan HTTP 200, sehingga cek status saja tidak cukup. Pemeriksaannya diperbaiki agar juga mewajibkan JSON valid, dan hasil versi lama berubah dari 3 PASS / 15 FAIL menjadi 2 PASS / 16 FAIL, sesuai laporan.

## 4. Panduan Saat Terjadi Error

| Pesan atau kode | Arti | Tindakan |
|---|---|---|
| `Terjadi kesalahan pada server.` + `ref` | Error internal | Cari `ref` di `storage/logs/app.log` |
| `File data rusak...` di log | `storage/data.json` bukan JSON valid | Perbaiki atau pulihkan file. Aplikasi tidak menimpanya |
| HTTP 403 `Origin tidak diizinkan` | Frontend dibuka dari alamat lain | Tambahkan ke `KEUANGAN_ALLOWED_ORIGINS` |
| `Gagal mengambil data dari backend` | Backend belum berjalan | Jalankan backend di port 8000 |
