# ✅ Checklist Fitur MVP — Sistem Pencatat Keuangan Harian

**Status akhir:** seluruh fitur MVP selesai dan diuji.
**Unit test:** `python -m unittest discover -s tests -v` → **Ran 55 tests — OK (ALL PASSED)**

Legenda kolom *Uji Manual*: ✔ = sudah dicoba langsung di browser dan terekam di `demo.mp4`.

---

## A. Fitur Inti (User Stories)

| ID | Fitur | Kriteria yang diuji | Unit Test | Uji Manual | Status |
|---|---|---|---|---|---|
| US-01 | Atur saldo awal | Menerima angka ≥ 0, desimal, string angka; menolak negatif, teks, NaN/Infinity, > Rp 1 triliun; update berulang | `test_saldo.py` (8), `test_validasi_input.py` | ✔ | ✅ PASSED |
| US-02 | Catat pemasukan | Data lengkap tersimpan; nominal > 0; tanggal & keterangan wajib; total pemasukan terakumulasi; badge hijau | `test_transaksi_pemasukan.py` (5) | ✔ | ✅ PASSED |
| US-03 | Catat pengeluaran & ringkasan saldo | Nominal > 0; jenis hanya pemasukan/pengeluaran; Saldo Akhir = Awal + Masuk − Keluar; indikator **Defisit Saldo!** | `test_transaksi_pengeluaran.py` (4), `test_kalkulasi_saldo.py` (4) | ✔ | ✅ PASSED |
| US-04 | Riwayat & hapus transaksi | Hapus berdasarkan ID; saldo dihitung ulang; ID tidak ada / bukan angka ditolak; konfirmasi sebelum hapus | `test_hapus_dan_validasi.py` (5) | ✔ | ✅ PASSED |
| US-05 | Notifikasi & validasi | Sukses → alert hijau; gagal → alert merah dengan pesan jelas; redirect 302 / render 200 | `test_hapus_dan_validasi.py`, `test_integrasi_app.py` | ✔ | ✅ PASSED |
| — | Reset seluruh data | Saldo & transaksi kembali 0; ID transaksi mulai lagi dari 1; tombol dengan konfirmasi | `test_kalkulasi_saldo.py`, `test_penyimpanan.py`, `test_integrasi_app.py` | ✔ | ✅ PASSED |

## B. Integrasi UI + Logika + Penyimpanan Data / API

| Fitur | Kriteria yang diuji | Unit Test | Uji Manual | Status |
|---|---|---|---|---|
| Penyimpanan permanen (SQLite) | Data tetap ada setelah reload & restart server; hapus/reset tersimpan permanen | `test_penyimpanan.py` (5), `test_integrasi_app.py` | ✔ | ✅ PASSED |
| API JSON `GET /api/ringkasan` | Mengembalikan ringkasan + daftar transaksi; error API dalam format JSON | `test_integrasi_app.py` | ✔ | ✅ PASSED |
| Alur end-to-end | Data kosong → saldo awal → pemasukan → pengeluaran → hapus → defisit → reset | `test_integrasi_app.py::test_alur_lengkap_saldo_transaksi_hapus_reset` | ✔ | ✅ PASSED |

## C. Penanganan Input Tidak Valid, Data Kosong, Loading, dan Kegagalan

| Kondisi | Perilaku aplikasi | Unit Test | Uji Manual | Status |
|---|---|---|---|---|
| Data kosong | Riwayat menampilkan *"Belum ada transaksi yang dicatat"*; tombol reset disembunyikan | `test_integrasi_app.py` | ✔ | ✅ PASSED |
| Field kosong / hanya spasi | Ditolak dengan pesan merah, data tidak berubah | `test_integrasi_app.py`, `test_transaksi_pemasukan.py` | ✔ | ✅ PASSED |
| Format tanggal salah / tanggal tidak ada (mis. 30 Feb) | Ditolak: *"Format tanggal tidak valid"* | `test_validasi_input.py` | — | ✅ PASSED |
| Nominal `nan`, `inf`, terlalu besar | Ditolak | `test_validasi_input.py` | — | ✅ PASSED |
| Keterangan > 100 karakter | Ditolak (server) + `maxlength` di form | `test_validasi_input.py` | — | ✅ PASSED |
| Request terlalu besar (> 16 KB) | Halaman error 413 | `test_integrasi_app.py` | — | ✅ PASSED |
| Loading | Tombol berubah menjadi *"Memproses..."* dan dinonaktifkan (cegah double submit) | — (perilaku browser) | ✔ | ✅ Diuji manual |
| Gangguan database (read-only/terkunci) | Tidak crash; notifikasi *"Data gagal disimpan..."*; error + traceback tercatat di log; data di memori tidak berubah | `test_integrasi_app.py`, `test_penyimpanan.py` | ✔ (DB dibuat read-only) | ✅ PASSED |
| Halaman tidak ditemukan / metode salah | Halaman error 404 / 405 dengan tombol kembali | `test_integrasi_app.py` | ✔ | ✅ PASSED |

## D. Keamanan

Detail lengkap ada di [`docs/LAPORAN_KEAMANAN.md`](docs/LAPORAN_KEAMANAN.md).

| Kontrol | Unit Test | Status |
|---|---|---|
| Proteksi CSRF di semua form POST | `test_keamanan.py` (3 test) | ✅ PASSED |
| Header keamanan (CSP, X-Frame-Options, nosniff, Referrer-Policy, Cache-Control) | `test_keamanan.py` | ✅ PASSED |
| Escape XSS pada keterangan | `test_keamanan.py` | ✅ PASSED |
| Anti SQL Injection (query berparameter) | `test_penyimpanan.py` | ✅ PASSED |
| Debug mati secara default, SECRET_KEY tidak hardcode | `test_keamanan.py` | ✅ PASSED |
| Cookie sesi HttpOnly + SameSite=Lax | `test_keamanan.py` | ✅ PASSED |
| Log aman (tanpa data keuangan, tahan log injection) | `test_keamanan.py` | ✅ PASSED |
| Dependensi bebas CVE (`pip-audit`) & kode bersih (`bandit`) | — (alat eksternal) | ✅ 0 temuan |

---

## E. Ringkasan Hasil Unit Test

| File | Jumlah Test | Status |
|---|---|---|
| `tests/test_saldo.py` | 8 | ✅ PASSED |
| `tests/test_transaksi_pemasukan.py` | 5 | ✅ PASSED |
| `tests/test_transaksi_pengeluaran.py` | 4 | ✅ PASSED |
| `tests/test_kalkulasi_saldo.py` | 4 | ✅ PASSED |
| `tests/test_hapus_dan_validasi.py` | 5 | ✅ PASSED |
| `tests/test_validasi_input.py` | 6 | ✅ PASSED |
| `tests/test_penyimpanan.py` | 5 | ✅ PASSED |
| `tests/test_integrasi_app.py` | 8 | ✅ PASSED |
| `tests/test_keamanan.py` | 10 | ✅ PASSED |
| **Total** | **55** | **✅ ALL PASSED** |

```
$ python -m unittest discover -s tests
.......................................................
----------------------------------------------------------------------
Ran 55 tests in 1.288s

OK
```

## F. Bug yang Ditemukan & Diperbaiki Selama Penyelesaian MVP

| # | Bug | Dampak | Perbaikan | Test Regresi |
|---|---|---|---|---|
| 1 | Data hanya disimpan di memori | Semua transaksi hilang saat server restart | Tambah penyimpanan SQLite (`penyimpanan.py`) | `test_penyimpanan.py` |
| 2 | Nominal `"nan"` / `"inf"` diterima | Ringkasan saldo menjadi `nan` / rusak | Tolak NaN & Infinity di `_ke_angka()` | `test_nominal_nan_dan_infinity_ditolak` |
| 3 | Tanggal sembarang (mis. `"kemarin"`) diterima | Riwayat berisi tanggal tidak valid | Validasi `date.fromisoformat()` | `test_format_tanggal_tidak_valid_ditolak` |
| 4 | Tidak ada tombol reset di UI | Route `/reset` tidak bisa dipakai pengguna | Tambah tombol *Reset Semua Data* dengan konfirmasi | `test_alur_lengkap_saldo_transaksi_hapus_reset` |
| 5 | Tanggal default memakai UTC (JavaScript) | Tanggal mundur 1 hari di WIB antara jam 00.00–07.00 | Tanggal default dari server (`date.today()`) | uji manual |

Uji regresi: setelah semua perbaikan, ke-26 test lama tetap lulus bersama 29 test baru (total 55).

## G. Verifikasi Fresh Clone

Dilakukan pada 22 Oktober 2026 dari folder kosong, dengan virtual environment baru, terhadap branch `feat/mvp-final` (commit `b3ade2e`).

```
$ git clone -b feat/mvp-final https://github.com/Dazdimas/Rpl_dimaz.git
$ cd Rpl_dimaz
$ python -m venv venv
$ venv/Scripts/python -m pip install -r requirements.txt
  → Flask 3.1.3, Werkzeug 3.1.9, Jinja2 3.1.6, MarkupSafe 3.0.4, itsdangerous 2.2.0, click 8.5.0, blinker 1.9.0
$ venv/Scripts/python -m unittest discover -s tests
Ran 55 tests in 1.183s
OK
$ PORT=5001 venv/Scripts/python app.py
```

| Pemeriksaan pada aplikasi hasil clone | Hasil |
|---|---|
| `GET /` (dashboard) | ✅ 200 |
| `POST /set-saldo` dengan token CSRF | ✅ 302, saldo tersimpan |
| `POST /tambah` dengan token CSRF | ✅ 302, transaksi tersimpan |
| `GET /api/ringkasan` | ✅ 200, saldo akhir Rp 75.000 sesuai perhitungan |
| `GET /static/css/style.css`, `/static/js/app.js` | ✅ 200 |
| `data/keuangan.db` & `logs/app.log` dibuat otomatis | ✅ |
