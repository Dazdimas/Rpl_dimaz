# 📝 Catatan Perubahan (Changelog)

## [1.0.0] — 2026-10-22 — MVP Final

### Ditambahkan
- **Penyimpanan permanen SQLite** (`penyimpanan.py`): data saldo dan transaksi tidak hilang saat server restart.
- **API JSON** `GET /api/ringkasan` untuk membaca ringkasan saldo dan daftar transaksi.
- **Tombol "Reset Semua Data"** di UI, dengan konfirmasi.
- **Indikator loading**: tombol berubah menjadi *"Memproses..."* dan dinonaktifkan saat form dikirim (mencegah double submit).
- **Halaman error** 400 / 404 / 405 / 413 / 500 yang ramah pengguna (`templates/error.html`).
- **Logging ke file** `logs/app.log` dengan rotasi dan aturan log aman.
- **Proteksi keamanan**: token CSRF, header keamanan (CSP, X-Frame-Options, nosniff, Referrer-Policy, Cache-Control), cookie HttpOnly + SameSite, batas ukuran request 16 KB.
- **Konfigurasi lewat environment variable**: `SECRET_KEY`, `DATABASE_PATH`, `LOG_DIR`, `HOST`, `PORT`, `FLASK_DEBUG`.
- **29 test baru** di 4 file: `test_validasi_input.py`, `test_penyimpanan.py`, `test_integrasi_app.py`, `test_keamanan.py` (total 55 test).
- Dokumen `HowToRun.md`, `checklist_mvp.md`, `docs/LAPORAN_KEAMANAN.md`, `docs/LOG_DAN_ERROR.md`, dan video `demo.mp4`.

### Diubah
- `app.py` memakai pola **application factory** (`create_app()`), sehingga test dapat memakai database sementara yang terpisah.
- JavaScript konfirmasi hapus dan style inline dipindahkan ke `static/js/app.js` dan `static/css/style.css` (syarat CSP ketat).
- **Flask 3.0.0 → 3.1.3** dan seluruh dependensi di-*pin* ke versi pasti.

### Diperbaiki
- Nominal/saldo `nan` dan `inf` sebelumnya diterima dan merusak perhitungan saldo.
- Tanggal transaksi sebelumnya bisa berisi teks bebas. Sekarang wajib berformat `YYYY-MM-DD` yang valid.
- Keterangan tanpa batas panjang. Sekarang dibatasi maksimal 100 karakter.

### Keamanan
- Debug mode (Werkzeug debugger, risiko eksekusi kode jarak jauh) **dimatikan secara default**.
- `SECRET_KEY` tidak lagi di-*hardcode* di kode sumber.
- Menutup CVE-2026-27205 (Flask) dengan upgrade ke 3.1.3.

Detail: [`docs/LAPORAN_KEAMANAN.md`](docs/LAPORAN_KEAMANAN.md)

---

## [0.2.0] — Pertemuan 5–6

- Restrukturisasi folder: CSS dipindah ke `static/css/style.css`, file CSS duplikat dihapus.
- Tanggal default form diisi dari server (Python) menggantikan JavaScript.
- 5 file unit test aturan bisnis (26 test).

## [0.1.0] — Pertemuan 4

- Scaffold Flask, `keuangan_service.py`, dan `templates/index.html`.
