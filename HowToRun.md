# 🚀 How To Run — Sistem Pencatat Keuangan Harian

Panduan menjalankan aplikasi dari nol (fresh clone) sampai aplikasi bisa diakses di browser dan seluruh unit test lulus.

---

## 1. Prasyarat

| Kebutuhan | Versi | Cek dengan perintah |
|---|---|---|
| Python | 3.10 – 3.13 | `python --version` |
| pip | bawaan Python | `python -m pip --version` |
| Git | versi apa saja | `git --version` |

Tidak perlu memasang database terpisah. Aplikasi memakai **SQLite** (bawaan Python), dan file database dibuat otomatis saat pertama kali dijalankan.

---

## 2. Instalasi (Fresh Clone)

### Windows (PowerShell)

```powershell
git clone https://github.com/Dazdimas/Rpl_dimaz.git
cd Rpl_dimaz
python -m venv venv
.\venv\Scripts\Activate.ps1
python -m pip install -r requirements.txt
```

> Jika muncul error *"running scripts is disabled on this system"*, jalankan sekali:
> `Set-ExecutionPolicy -Scope CurrentUser RemoteSigned`, lalu aktifkan venv lagi.

### Linux / macOS

```bash
git clone https://github.com/Dazdimas/Rpl_dimaz.git
cd Rpl_dimaz
python3 -m venv venv
source venv/bin/activate
python -m pip install -r requirements.txt
```

---

## 3. Menjalankan Unit Test

```bash
python -m unittest discover -s tests -v
```

Hasil yang diharapkan di baris terakhir:

```
Ran 55 tests in 1.2s

OK
```

---

## 4. Menjalankan Aplikasi

```bash
python app.py
```

Buka browser ke **http://127.0.0.1:5000**

Untuk menghentikan server, tekan `Ctrl + C` di terminal.

### Konfigurasi opsional (environment variable)

| Variabel | Default | Fungsi |
|---|---|---|
| `SECRET_KEY` | acak setiap start | Kunci penandatangan sesi & token CSRF. **Wajib diatur** jika ingin sesi tetap valid setelah restart. |
| `DATABASE_PATH` | `data/keuangan.db` | Lokasi file database SQLite. |
| `LOG_DIR` | `logs/` | Folder file log (`app.log`, rotasi 1 MB × 3 file). |
| `PORT` | `5000` | Port server. |
| `HOST` | `127.0.0.1` | Alamat bind server. Gunakan `0.0.0.0` hanya di jaringan tepercaya. |
| `FLASK_DEBUG` | mati | Isi `1` **hanya saat development** untuk mengaktifkan auto-reload & debugger. |

Contoh mengatur `SECRET_KEY`:

```powershell
# Windows PowerShell
$env:SECRET_KEY = python -c "import secrets; print(secrets.token_hex(32))"
python app.py
```

```bash
# Linux / macOS
export SECRET_KEY=$(python -c "import secrets; print(secrets.token_hex(32))")
python app.py
```

---

## 5. Alur Demo Singkat

1. Buka dashboard → riwayat kosong menampilkan *"Belum ada transaksi yang dicatat"*.
2. **Atur Saldo Awal** → isi `1000000` → klik *Simpan Saldo Awal*.
3. **Tambah Transaksi** pemasukan `Gaji` Rp 2.500.000, lalu pengeluaran `Sewa kos` Rp 1.200.000.
4. Lihat kartu ringkasan diperbarui otomatis.
5. Coba isi keterangan hanya dengan spasi → muncul notifikasi merah.
6. Tambah pengeluaran besar → kartu berubah menjadi **Defisit Saldo!**
7. Klik **Hapus** pada salah satu transaksi → konfirmasi → saldo dihitung ulang.
8. Refresh halaman / restart server → data tetap ada (tersimpan di SQLite).
9. Buka **http://127.0.0.1:5000/api/ringkasan** untuk melihat data dalam format JSON.
10. Klik **Reset Semua Data** → konfirmasi → semua kembali ke 0.

Rekaman alur lengkap tersedia di [`demo.mp4`](demo.mp4).

---

## 6. Akses Data & Log

| Lokasi | Isi |
|---|---|
| `data/keuangan.db` | Database SQLite (saldo awal & transaksi). Hapus file ini untuk memulai dari data kosong. |
| `logs/app.log` | Log aplikasi. Contoh isi dan cara membacanya ada di [`docs/LOG_DAN_ERROR.md`](docs/LOG_DAN_ERROR.md). |

Kedua folder diabaikan oleh Git (`.gitignore`), jadi tidak ikut ter-commit.

---

## 7. Troubleshooting

| Gejala | Penyebab | Solusi |
|---|---|---|
| `ModuleNotFoundError: No module named 'flask'` | Dependensi belum terpasang / venv belum aktif | Aktifkan venv, lalu `python -m pip install -r requirements.txt` |
| `OSError: [Errno 98] / [WinError 10048] Address already in use` | Port 5000 sedang dipakai program lain | Jalankan dengan port lain: `PORT=5001 python app.py` (Linux/macOS) atau `$env:PORT=5001; python app.py` (PowerShell) |
| Halaman error **400** *"Sesi formulir tidak valid"* | Server di-restart tanpa `SECRET_KEY` tetap, token CSRF lama tidak berlaku | Refresh halaman (F5) lalu kirim ulang form. Atur `SECRET_KEY` agar tidak terulang. |
| Notifikasi *"Data gagal disimpan karena gangguan penyimpanan"* | File `data/keuangan.db` tidak bisa ditulis (read-only / disk penuh / dikunci program lain) | Lihat `logs/app.log`, pastikan file dan folder `data/` bisa ditulis. |
| `python` tidak dikenali (Windows) | Python belum masuk PATH | Pasang ulang Python dan centang *"Add python.exe to PATH"*, atau pakai `py` sebagai ganti `python`. |
