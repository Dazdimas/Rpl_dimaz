# 💰 Sistem Informasi Pencatat Keuangan Harian

### Sistem Informasi Pencatat Keuangan Harian Berbasis Web

## 📌 Deskripsi

**Sistem Informasi Pencatat Keuangan Harian** adalah aplikasi berbasis web yang dibuat untuk membantu pengguna mencatat dan memantau arus kas harian secara sederhana dan terorganisir. Aplikasi ini memungkinkan pengguna untuk mencatat pemasukan, pengeluaran, serta saldo yang tersisa berdasarkan transaksi yang telah dilakukan.

Pengguna dapat menginput saldo awal, menambahkan transaksi, dan melihat ringkasan keuangan dalam satu tampilan. Data yang tercatat akan membantu pengguna memahami kondisi keuangan mereka dalam periode tertentu.

## 📍 Status Implementasi

Bagian kebutuhan di bawah menggambarkan rancangan sistem. Kode saat ini baru mencakup sebagian:

| Fitur | Status |
|---|---|
| Tambah, edit, hapus, dan filter transaksi | ✅ Tersedia |
| Ringkasan saldo, grafik pengeluaran, batas anggaran | ✅ Tersedia |
| Saldo awal | ⚠️ Nilai tetap Rp 5.000.000, belum bisa diubah pengguna |
| Login, peran Admin, tabel `users` | ❌ Belum ada |
| Database MySQL | ❌ Belum dipakai. Data disimpan di `storage/data.json`, dan `backend/database.sql` hanya rancangan |

> ⚠️ Karena belum ada login, jalankan aplikasi **hanya di `localhost`**. Lihat [Laporan Keamanan](docs/LAPORAN_KEAMANAN.md).

**Dokumen Pertemuan 8:** [Laporan Keamanan](docs/LAPORAN_KEAMANAN.md) · [Changelog](CHANGELOG.md) · [Log & Analisis Error](docs/LOG_DAN_ERROR.md) · [Verifikasi Akhir](docs/VERIFIKASI.md)

---

## 🎯 Tujuan

Sistem ini dibuat dengan tujuan:

- Mempermudah pengguna dalam mencatat pemasukan dan pengeluaran harian.
- Menampilkan total pemasukan dan total pengeluaran secara otomatis.
- Menghitung saldo akhir berdasarkan saldo awal dan transaksi yang telah ditambahkan.
- Menyediakan riwayat transaksi agar pengguna dapat memantau kegiatan keuangannya.
- Membantu proses pencatatan keuangan menjadi lebih terstruktur dan mudah dipahami.

---

## 💡 Manfaat

### 👤 Bagi Pengguna

- Dapat mencatat pemasukan dan pengeluaran dengan lebih cepat.
- Tidak perlu menghitung manual karena sistem menghitung saldo secara otomatis.
- Dapat melihat ringkasan keuangan secara langsung.
- Dapat memantau riwayat transaksi dengan lebih jelas.

### 👨‍💼 Bagi Admin / Pengelola

- Mempermudah pengelolaan data transaksi keuangan.
- Mengurangi risiko kesalahan pencatatan manual.
- Mempermudah analisis pemasukan dan pengeluaran.
- Membantu dalam pengambilan keputusan keuangan secara lebih cepat.

---

## 👥 Pengguna Sistem

Sistem memiliki **2 jenis pengguna**:

### 1. Pengguna

Pengguna dapat:

- Mengatur saldo awal.
- Menambahkan transaksi pemasukan dan pengeluaran.
- Melihat ringkasan saldo.
- Melihat riwayat transaksi.

### 2. Admin

Admin dapat:

- Mengelola data transaksi.
- Melihat data pengguna dan semua catatan keuangan.
- Mengawasi saldo serta catatan pemasukan dan pengeluaran.
- Mengelola sistem secara keseluruhan.

---

## ⚙️ Fitur Utama

### 🔐 1. Pengaturan Saldo Awal

Pengguna dapat memasukkan nominal saldo awal yang menjadi dasar perhitungan saldo akhir.

### 💵 2. Pencatatan Transaksi

Pengguna dapat menambahkan transaksi dengan data berikut:

- Tanggal transaksi
- Jenis transaksi (pemasukan/pengeluaran)
- Keterangan
- Nominal

### 📊 3. Ringkasan Keuangan

Sistem menampilkan:

- Saldo awal
- Total pemasukan
- Total pengeluaran
- Saldo akhir

Perhitungan saldo dilakukan dengan rumus:

```text
Saldo akhir = Saldo awal + Total pemasukan - Total pengeluaran
```

### 🧾 4. Riwayat Transaksi

Transaksi yang telah ditambahkan akan ditampilkan dalam tabel agar pengguna dapat memantau aktivitas keuangan.

---

## 🔄 Alur Sistem

```text
Pengguna
    ↓
Login / Akses Aplikasi
    ↓
Input Saldo Awal
    ↓
Tambah Transaksi
    ↓
Pilih Jenis Transaksi
    ↓
Input Tanggal, Keterangan, Nominal
    ↓
Sistem Menghitung Saldo
    ↓
Lihat Ringkasan & Riwayat Transaksi
```

---

## 🗄️ Database

> Rancangan, belum diimplementasikan. Lihat [Status Implementasi](#-status-implementasi).

Database dibuat sederhana dengan beberapa tabel utama.

### `users`

```text
id
nama
email
password
role
```

### `transaksi`

```text
id
user_id
tanggal
jenis_transaksi
keterangan
nominal
```

### Relasi

```text
users
  │
  │ 1
  │
  │ *
  ▼
transaksi
```

---

## 🖥️ Halaman Sistem

### 👤 Pengguna

- Login
- Dashboard
- Form Saldo Awal
- Form Transaksi
- Ringkasan Keuangan
- Riwayat Transaksi

### 👨‍💼 Admin

- Login Admin
- Dashboard Admin
- Manajemen Transaksi
- Lihat Semua Data Keuangan
- Kelola Data Pengguna

---

## 🛠️ Teknologi

### Frontend

- **HTML5**
- **CSS**
- **JavaScript**

### Backend

- **PHP**
- **API REST**

### Database

- **MySQL**

### Tools

- Git
- GitHub
- Visual Studio Code
- Postman

---

## 📋 Metode Pengembangan

Project dikembangkan menggunakan konsep **Rekayasa Perangkat Lunak (RPL)** melalui beberapa tahapan:

1. Analisis kebutuhan
2. Perancangan sistem
3. Perancangan database
4. Perancangan UI
5. Implementasi
6. Pengujian
7. Evaluasi

---

## 🧪 Pengujian

Pengujian dilakukan untuk memastikan fitur sistem berjalan sesuai dengan kebutuhan. Hasilnya tercatat di [Verifikasi Akhir](docs/VERIFIKASI.md).

Fitur yang diuji meliputi:

- Penambahan transaksi pemasukan
- Penambahan transaksi pengeluaran
- Perhitungan saldo otomatis
- Tampilan ringkasan keuangan
- Tampilan riwayat transaksi

---

## 💻 Instalasi dan Deployment (Fresh Clone)

**Prasyarat:** Git dan PHP 8.0 atau lebih baru dengan ekstensi `mbstring` (teruji pada PHP 8.2.12 dari XAMPP). Di Windows, pastikan folder PHP seperti `C:\xampp\php` ada di `PATH`. Composer, Node.js, dan MySQL tidak diperlukan.

Perintah berikut sama untuk PowerShell, Git Bash, dan terminal Linux/macOS.

### 1. Clone repository

```bash
git clone -b php-keuangan https://github.com/Dazdimas/Rpl_dimaz.git
cd Rpl_dimaz
```

> Opsi `-b php-keuangan` wajib. Branch `main` di GitHub berisi versi Flask yang berbeda.

### 2. Instalasi dependensi

Backend hanya memakai PHP bawaan, jadi tidak ada paket yang perlu dipasang. Cukup periksa PHP-nya:

```bash
php -v
php -r "echo extension_loaded('mbstring') ? 'mbstring OK' : 'mbstring TIDAK ADA', PHP_EOL;"
```

Chart.js dimuat dari CDN, sehingga grafik memerlukan koneksi internet. Tanpa internet, fitur lain tetap berjalan.

### 3. Build aplikasi

PHP dan JavaScript di project ini tidak dikompilasi. Build memeriksa sintaks, menjalankan seluruh test dan pemindaian keamanan, lalu merakit file runtime ke `dist/keuangan-demo`:

```bash
php scripts/build.php
```

Hasil yang diharapkan:

```text
ALL PASSED: 60/60 test lulus
Ringkasan: 18 PASS, 0 FAIL, 1 OPEN (total 19)
BUILD BERHASIL: 7 file di dist/keuangan-demo
```

`1 OPEN` adalah risiko yang sudah diketahui, yaitu login yang belum ada.

### 4. Deploy dan jalankan paket demo

Jalankan di dua terminal terpisah dari folder project:

```bash
# Terminal 1: backend
php -S localhost:8000 -t dist/keuangan-demo/backend
```

```bash
# Terminal 2: frontend
php -S localhost:8080 -t dist/keuangan-demo/frontend
```

Buka **http://localhost:8080** di browser. Frontend memanggil API di `http://localhost:8000/api.php`, jadi backend harus memakai port 8000. Tekan `Ctrl+C` di tiap terminal untuk berhenti.

### 5. Periksa hasil deploy

Di terminal ketiga:

```bash
php scripts/smoke_test.php
```

Hasil yang diharapkan adalah `SMOKE TEST PASS`.

### Perintah lainnya

| Keperluan | Perintah |
|---|---|
| Menjalankan test saja | `php tests/run_tests.php` |
| Menjalankan pemindaian keamanan saja | `php tests/security_scan.php` |
| Menjalankan dari kode sumber tanpa build | `php -S localhost:8000 -t backend` dan `php -S localhost:8080 -t frontend` |
| Mengembalikan data demo ke awal | Hentikan backend, lalu hapus `dist/keuangan-demo/storage/data.json` |

Build berikutnya tidak menimpa data demo yang sudah ada. Data dan log disimpan di folder `storage/` milik masing-masing lokasi dan tidak ikut di-commit.

### Konfigurasi (opsional)

Aplikasi tidak memakai file `.env`. Pengaturan dibaca dari variabel lingkungan:

| Variabel | Bawaan | Fungsi |
|---|---|---|
| `KEUANGAN_ALLOWED_ORIGINS` | `http://localhost:8080,http://127.0.0.1:8080` | Origin frontend yang diizinkan |
| `KEUANGAN_DATA_FILE` | `storage/data.json` | Lokasi file data |
| `KEUANGAN_LOG_FILE` | `storage/logs/app.log` | Lokasi file log |

### Kendala umum

| Gejala | Solusi |
|---|---|
| `php` tidak dikenali | Tambahkan folder PHP ke `PATH`, lalu buka ulang terminal |
| `Failed to listen on localhost:8000` | Port terpakai. Hentikan program yang memakainya |
| `Gagal mengambil data dari backend` | Pastikan backend berjalan di port 8000 |
| Grafik tidak muncul | Periksa koneksi internet (Chart.js dari CDN) |
| Error server dengan kode `ref` | Cari kode tersebut di `storage/logs/app.log`. Lihat [panduan error](docs/LOG_DAN_ERROR.md) |

---

## 🚧 Status Project

**Status:** Dalam pengembangan