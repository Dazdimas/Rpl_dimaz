# 💰 Pencatat Keuangan Harian (Keuangan RPL)

### Aplikasi web full-stack untuk mencatat pemasukan, pengeluaran, dan saldo harian

## 📌 Deskripsi

**Pencatat Keuangan Harian** adalah aplikasi berbasis web yang dirancang untuk membantu pengguna mengelola dan memantau arus kas pribadi maupun kelompok. Pengguna dapat menentukan saldo awal, mencatat transaksi pemasukan atau pengeluaran, memperbarui data transaksi, menghapus transaksi dengan dialog konfirmasi, serta memantau ringkasan saldo keuangan harian secara real-time.

Aplikasi ini dibangun menggunakan arsitektur bersih terpisah antara **Backend (PHP Native REST API)** dan **Frontend (HTML5, Tailwind CSS, JavaScript)** dengan basis data **MySQL**.

---

## 🎯 Tujuan

- Memudahkan pencatatan transaksi keuangan harian secara terstruktur.
- Menyediakan antarmuka ringkasan keuangan (Saldo Awal, Total Pemasukan, Total Pengeluaran, dan Saldo Akhir).
- Menyediakan operasi CRUD lengkap untuk transaksi (Create, Read, Update, Delete).
- Mengintegrasikan penyimpanan persisten menggunakan database MySQL dengan skema relasional dan data awal (seeder).
- Menyediakan fitur pencarian dan filter transaksi berdasarkan jenis (Semua / Pemasukan / Pengeluaran).

---

## ⚙️ Fitur Utama

### 1. Pengaturan Saldo Awal
Pengguna dapat menentukan atau memperbarui nominal saldo awal kapan saja sebagai dasar perhitungan saldo akhir.

### 2. Pencatatan Transaksi (Pemasukan & Pengeluaran)
Setiap transaksi mencatat:
- Tanggal transaksi
- Jenis transaksi (`pemasukan` atau `pengeluaran`)
- Kategori transaksi (Gaji, Usaha, Kebutuhan Rumah, Makan, Transportasi, dll.)
- Keterangan transaksi
- Nominal transaksi dalam Rupiah

### 3. Ringkasan Keuangan (Dashboard)
Aplikasi menampilkan kartu ringkasan keuangan yang diperbarui secara instan:
- **Saldo Awal**
- **Total Pemasukan**
- **Total Pengeluaran**
- **Saldo Akhir**

Rumus perhitungan saldo akhir:
```text
Saldo Akhir = Saldo Awal + Total Pemasukan - Total Pengeluaran
```

### 4. Manajemen Riwayat Transaksi (CRUD & Filter)
- **Tabel Transaksi:** Menampilkan seluruh riwayat transaksi dengan badge warna (Hijau untuk Pemasukan, Merah untuk Pengeluaran).
- **Filter Jenis:** Menyaring transaksi berdasarkan Semua, Pemasukan saja, atau Pengeluaran saja.
- **Pencarian:** Mencari transaksi berdasarkan kata kunci keterangan atau kategori.
- **Ubah (Edit):** Form modal untuk memperbarui data transaksi yang sudah tersimpan.
- **Hapus (Delete):** Modal dialog konfirmasi sebelum penghapusan data transaksi.

---

## 📂 Struktur Proyek

```text
keuangan_RPL/
├── backend/
│   ├── api.php           # Router dan handler REST API (GET, POST, PUT, DELETE)
│   ├── config.php        # Konfigurasi database MySQL, migrasi otomatis & service
│   ├── database.sql      # Skema database MySQL & data awal (seed)
│   └── index.php         # Halaman dokumentasi & status server backend
├── frontend/
│   ├── assets/
│   │   ├── app.js        # Logika aplikasi frontend, interaksi API, state & filter
│   │   └── style.css     # Styling kustom, animasi toast, dan badge status
│   └── index.html        # Antarmuka dashboard pengguna berbasis Tailwind CSS
└── README.md             # Dokumentasi lengkap proyek
```

---

## 🛠️ Teknologi yang Digunakan

- **Backend:** PHP Native (PHP 8.x) dengan ekstensi PDO MySQL
- **Frontend:** HTML5, Tailwind CSS, JavaScript Vanilla (ES6+)
- **Database:** MySQL / MariaDB (XAMPP / MySQL Server)
- **Format Pertukaran Data:** JSON dengan struktur standar PascalCase (`Success`, `Message`, `Data`, `Summary`)

---

## 🗄️ Skema Basis Data (MySQL)

Database yang digunakan bernama `keuangan_rpl` dengan tabel-tabel berikut:

### 1. Tabel `pengaturan`
Menyimpan konfigurasi saldo awal:
- `id` (INT, Primary Key, Auto Increment)
- `kunci` (VARCHAR(50), Unique)
- `nilai` (VARCHAR(255))
- `keterangan` (VARCHAR(255))
- `updated_at` (TIMESTAMP)

### 2. Tabel `kategori`
Menyimpan daftar kategori transaksi:
- `id` (INT, Primary Key, Auto Increment)
- `nama` (VARCHAR(100))
- `jenis` (ENUM: `pemasukan`, `pengeluaran`)
- `created_at` (TIMESTAMP)

### 3. Tabel `transaksi`
Menyimpan data catatan transaksi keuangan:
- `id` (INT, Primary Key, Auto Increment)
- `tanggal` (DATE)
- `jenis` (ENUM: `pemasukan`, `pengeluaran`)
- `kategori` (VARCHAR(100))
- `keterangan` (VARCHAR(255))
- `nominal` (BIGINT)
- `created_at` (TIMESTAMP)

> **Catatan:** Backend telah dilengkapi fitur *auto-create* database dan migrasi/seeding otomatis saat pertama kali dijalankan, atau file `backend/database.sql` dapat diimpor langsung ke phpMyAdmin / MySQL CLI.

---

## 💻 Cara Menjalankan Aplikasi

Pastikan **PHP** dan server **MySQL** (misal melalui XAMPP) sudah berjalan di komputer Anda.

### 1. Jalankan Backend API

Buka terminal PowerShell pada direktori proyek:

```powershell
cd backend
php -S localhost:8000
```

Halaman status dan dokumentasi backend dapat diakses di:
```text
http://localhost:8000
```
Endpoint REST API beralamat di:
```text
http://localhost:8000/api.php
```

### 2. Jalankan Frontend

Buka jendela terminal baru:

```powershell
cd frontend
php -S localhost:8080
```

Buka peramban (browser) dan akses alamat berikut:
```text
http://localhost:8080
```

---

## 📡 Daftar Endpoint REST API

| Metode | Endpoint | Deskripsi |
| :--- | :--- | :--- |
| `GET` | `/api.php?action=transactions` | Mengambil seluruh data transaksi dan ringkasan saldo |
| `GET` | `/api.php?action=transactions&jenis=pemasukan` | Menyaring transaksi berdasarkan jenis |
| `GET` | `/api.php?action=transactions&search=gaji` | Mencari transaksi berdasarkan kata kunci |
| `GET` | `/api.php?action=transactions&id={id}` | Mengambil detail 1 transaksi berdasarkan ID |
| `POST` | `/api.php?action=transactions` | Menambahkan transaksi baru |
| `PUT` | `/api.php?action=transactions&id={id}` | Memperbarui data transaksi |
| `DELETE` | `/api.php?action=transactions&id={id}` | Menghapus transaksi |
| `GET` | `/api.php?action=saldo-awal` | Mengambil data nominal saldo awal |
| `POST` | `/api.php?action=saldo-awal` | Mengubah nominal saldo awal |
| `GET` | `/api.php?action=categories` | Mengambil daftar kategori transaksi |
| `GET` | `/api.php?action=summary` | Mengambil data ringkasan keuangan harian |

---

## 📋 Aturan Format & Respons API

Respons API menggunakan format standar JSON dengan penamaan atribut PascalCase:

```json
{
  "Success": true,
  "Message": "Transaksi berhasil ditambahkan.",
  "Data": {
    "Id": 1,
    "Tanggal": "2026-09-28",
    "Jenis": "pemasukan",
    "Kategori": "Gaji Pokok",
    "Keterangan": "Gaji bulan ini",
    "Nominal": 2500000,
    "CreatedAt": "2026-09-28 13:55:42"
  },
  "Summary": {
    "SaldoAwal": 5000000,
    "TotalPemasukan": 3000000,
    "TotalPengeluaran": 1200000,
    "SaldoAkhir": 6800000,
    "TotalTransaksi": 4,
    "PemasukanCount": 2,
    "PengeluaranCount": 2
  }
}
```

---

## 🚧 Status Proyek

**Status:** Selesai dan siap digunakan (Full-stack CRUD terintegrasi database MySQL).