# 📋 Contoh Log Aman, Penyebab Error, dan Tindakan Perbaikan

Semua contoh di bawah adalah **log asli** dari `logs/app.log` saat aplikasi dijalankan dan diuji pada 22 Oktober 2026.

---

## 1. Konfigurasi Logging

| Aspek | Pengaturan |
|---|---|
| Lokasi file | `logs/app.log` (bisa diubah lewat env `LOG_DIR`) |
| Rotasi | Maksimal 1 MB per file, menyimpan 3 file lama (`app.log.1` … `app.log.3`) |
| Format | `waktu LEVEL [modul] pesan` |
| Level | `INFO` = aktivitas normal & validasi, `WARNING` = request mencurigakan / tidak ditemukan, `ERROR` = kegagalan sistem (disertai traceback) |

### Aturan "log aman" yang diterapkan

| ✅ Dicatat | ❌ Tidak pernah dicatat |
|---|---|
| ID dan jenis transaksi (`#3 (pengeluaran)`) | Nominal uang |
| Jenis aksi (tambah / hapus / reset / atur saldo) | Keterangan transaksi |
| Pesan validasi buatan aplikasi | Token CSRF, cookie sesi, `SECRET_KEY` |
| Metode, path, dan IP untuk request ditolak | Isi form mentah |

Selain itu, setiap teks yang berasal dari request (misalnya path URL) dibersihkan dengan `_aman()`: karakter baris baru diganti `\n` dan teks dipotong maksimal 200 karakter, sehingga penyerang tidak bisa menyisipkan baris log palsu.

---

## 2. Contoh Log Aktivitas Normal

```
2026-10-22 13:52:29,547 INFO [app] Aplikasi dimulai: 0 transaksi dimuat dari database.
2026-10-22 13:54:15,478 INFO [app] Saldo awal diperbarui.
2026-10-22 13:54:21,659 INFO [app] Transaksi #1 (pemasukan) ditambahkan.
2026-10-22 13:54:27,325 INFO [app] Transaksi #2 (pengeluaran) ditambahkan.
2026-10-22 13:54:46,404 INFO [app] Transaksi #4 dihapus.
2026-10-22 13:55:00,427 INFO [app] Seluruh data direset.
```

Perhatikan bahwa transaksi *"Gaji bulan Oktober — Rp 2.500.000"* hanya tercatat sebagai `Transaksi #1 (pemasukan)`. Data keuangannya tidak masuk ke log.

---

## 3. Contoh Error: Penyebab & Tindakan Perbaikan

### E1. Input tidak valid (validasi)

```
2026-10-22 13:54:35,613 INFO [app] Validasi gagal saat menambah transaksi: Keterangan transaksi tidak boleh kosong.
2026-10-22 13:55:45,269 INFO [app] Validasi gagal saat menambah transaksi: Nominal transaksi harus lebih besar dari 0.
2026-10-22 13:55:45,419 INFO [app] Validasi gagal saat mengatur saldo awal: Saldo awal harus berupa angka valid.
```

| | |
|---|---|
| **Penyebab** | Pengguna mengisi keterangan hanya dengan spasi, nominal negatif (`-5000`), atau saldo awal berupa teks (`abc`). |
| **Respons aplikasi** | Data tidak disimpan. Pengguna melihat notifikasi merah berisi pesan yang sama, lalu diarahkan kembali ke dashboard (HTTP 302 → 200). |
| **Tindakan perbaikan** | Tidak perlu tindakan dari admin karena ini perilaku yang diharapkan. Pengguna cukup memperbaiki isian form. |

### E2. Token CSRF tidak valid

```
2026-10-22 13:55:45,532 WARNING [app] Token CSRF tidak valid: POST /tambah dari 127.0.0.1
```

| | |
|---|---|
| **Penyebab** | (a) Server di-restart tanpa `SECRET_KEY` tetap, sehingga token di halaman lama tidak berlaku lagi, atau (b) ada request POST dari luar aplikasi (indikasi serangan CSRF). |
| **Respons aplikasi** | HTTP 400 dengan halaman *"Sesi formulir tidak valid atau kedaluwarsa. Muat ulang halaman lalu coba lagi."* Data tidak berubah. |
| **Tindakan perbaikan** | Pengguna: tekan F5 lalu kirim ulang form. Admin: atur env `SECRET_KEY` yang tetap agar tidak terjadi setiap restart. Jika log ini muncul berulang dari IP asing, berarti ada percobaan serangan, dan kontrol CSRF sudah memblokirnya. |

### E3. Percobaan log injection (sudah diredam)

```
2026-10-22 13:55:58,589 WARNING [app] Token CSRF tidak valid: POST /hapus/1\n2026-10-22 13:56:00,000 INFO [app] Seluruh data direset. dari 127.0.0.1
```

| | |
|---|---|
| **Penyebab** | Penyerang mengirim URL berisi `%0a` (baris baru) diikuti teks yang meniru baris log, dengan tujuan memalsukan catatan *"Seluruh data direset."* |
| **Respons aplikasi** | Request ditolak (HTTP 400). Baris baru ditulis sebagai `\n` sehingga semuanya tetap **satu baris** dan terlihat jelas sebagai percobaan serangan. |
| **Tindakan perbaikan** | Tidak ada, karena sudah ditangani oleh fungsi `_aman()`. |

### E4. Halaman tidak ditemukan / metode salah

```
2026-10-22 13:54:07,528 WARNING [app] Error 404 pada GET /favicon.ico
2026-10-22 13:55:45,711 WARNING [app] Error 404 pada GET /tidak-ada
2026-10-22 13:55:45,792 WARNING [app] Error 405 pada GET /tambah
```

| | |
|---|---|
| **Penyebab** | 404: alamat salah ketik atau browser otomatis meminta `favicon.ico`. 405: membuka `/tambah` langsung di address bar (GET), padahal route ini hanya menerima POST dari form. |
| **Respons aplikasi** | Halaman error yang ramah dengan tombol *"Kembali ke Dashboard"*. Untuk `/api/...`, respons berupa JSON `{"error": "..."}`. |
| **Tindakan perbaikan** | Gunakan tautan/form yang tersedia di dashboard. Log `favicon.ico` aman diabaikan. |

### E5. Gangguan penyimpanan database (ERROR)

Traceback di bawah dipersingkat: path folder diganti `...` dan satu frame `<lambda>` dihapus.

```
2026-10-22 13:55:45,968 ERROR [app] Gagal mengakses database saat menambah transaksi
Traceback (most recent call last):
  File "...\app.py", line 103, in _proses
    return aksi()
  File "...\keuangan_service.py", line 92, in tambah_transaksi
    t_id = self.penyimpanan.tambah_transaksi(tanggal_bersih, jenis, keterangan_bersih, nom)
  File "...\penyimpanan.py", line 74, in tambah_transaksi
    cur = conn.execute(
        "INSERT INTO transaksi (tanggal, jenis, keterangan, nominal) VALUES (?, ?, ?, ?)",
        (tanggal, jenis, keterangan, nominal),
    )
sqlite3.OperationalError: attempt to write a readonly database
2026-10-22 13:55:46,139 INFO [app] Transaksi #2 (pengeluaran) ditambahkan.
```

| | |
|---|---|
| **Penyebab** | File `data/keuangan.db` tidak bisa ditulis. Pada uji ini file sengaja dibuat read-only dengan `attrib +R data\keuangan.db`. Penyebab lain yang mungkin: disk penuh, folder tanpa izin tulis, atau file sedang dikunci program lain (misalnya DB Browser for SQLite). |
| **Respons aplikasi** | Tidak crash (bukan error 500). Pengguna melihat notifikasi *"Data gagal disimpan karena gangguan penyimpanan. Silakan coba lagi."* Data di memori **tidak berubah**, jadi tampilan tetap konsisten dengan isi database. Traceback dicatat untuk developer, dan isinya hanya menampilkan **query dengan placeholder `?`, bukan nilai transaksi**. |
| **Tindakan perbaikan** | Hapus atribut read-only (`attrib -R data\keuangan.db` di Windows, `chmod u+w data/keuangan.db` di Linux/macOS), kosongkan disk, atau tutup program yang mengunci file. Baris terakhir log menunjukkan transaksi berhasil disimpan setelah perbaikan dilakukan. |

### E6. SECRET_KEY tidak diatur

```
2026-10-22 13:45:43,687 WARNING [app] SECRET_KEY tidak diatur; memakai kunci acak sementara (sesi hilang saat restart).
```

| | |
|---|---|
| **Penyebab** | Aplikasi dijalankan tanpa environment variable `SECRET_KEY`. |
| **Respons aplikasi** | Aplikasi tetap berjalan aman dengan kunci acak, tetapi sesi dan token CSRF menjadi tidak berlaku setiap kali server restart (lihat E2). |
| **Tindakan perbaikan** | Atur `SECRET_KEY` sesuai panduan di [`HowToRun.md`](../HowToRun.md#konfigurasi-opsional-environment-variable). |

---

## 4. Cara Membaca Log

```powershell
# Windows PowerShell: 20 baris terakhir, lalu pantau terus
Get-Content logs\app.log -Tail 20 -Wait

# Hanya error
Select-String -Path logs\app.log -Pattern "ERROR"
```

```bash
# Linux / macOS
tail -n 20 -f logs/app.log
grep ERROR logs/app.log
```
