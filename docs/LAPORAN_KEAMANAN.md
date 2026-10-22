# 🔒 Laporan Uji Keamanan & Catatan Perubahan

**Aplikasi:** Sistem Pencatat Keuangan Harian
**Tanggal uji:** 22 Oktober 2026
**Lingkungan:** Windows 11, Python 3.13.4
**Alat:** `bandit 1.9.4` (analisis statis kode Python), `pip-audit 2.10.1` (cek CVE dependensi), serta unit test keamanan `tests/test_keamanan.py` dan uji manual dengan `curl` ke server yang sedang berjalan.

---

## 1. Ringkasan

| Pemeriksaan | Sebelum perbaikan | Sesudah perbaikan |
|---|---|---|
| `bandit` (kode) | **2 temuan** (1 High, 1 Low) | **0 temuan** |
| `pip-audit` (dependensi) | **1 kerentanan** (Flask 3.0.0, CVE-2026-27205) | **0 kerentanan** |
| Proteksi CSRF | Tidak ada | Ada di semua form POST |
| Header keamanan HTTP | Tidak ada | CSP, X-Frame-Options, nosniff, Referrer-Policy, Cache-Control |
| Unit test keamanan | 0 | **10 test — PASSED** |

---

## 2. Temuan & Perbaikan

### 2.1 [HIGH] Flask dijalankan dengan `debug=True` — bandit B201

**Sebelum** (`app.py`):
```python
app.run(debug=True, port=5000)
```

**Risiko:** Werkzeug debugger aktif. Jika terjadi error, siapa pun yang bisa mengakses server dapat membuka konsol interaktif dan **menjalankan kode Python sembarang** di komputer server.

**Perbaikan:** Debug mati secara default dan hanya aktif jika `FLASK_DEBUG=1` diatur secara sadar saat development. Server juga hanya mendengarkan di `127.0.0.1` kecuali `HOST` diubah.
```python
create_app().run(
    host=os.environ.get('HOST', '127.0.0.1'),
    port=int(os.environ.get('PORT', 5000)),
    debug=os.environ.get('FLASK_DEBUG') == '1',
)
```
**Bukti:** `test_mode_debug_mati_secara_default` — PASSED.

### 2.2 [LOW] SECRET_KEY ditulis langsung di kode — bandit B105

**Sebelum:**
```python
app.secret_key = 'kunci-rahasia-pencatat-keuangan-rpl'
```

**Risiko:** Kunci ikut ter-publish di GitHub. Siapa pun bisa memalsukan cookie sesi dan token CSRF.

**Perbaikan:** Kunci dibaca dari environment variable `SECRET_KEY`. Jika tidak diatur, aplikasi membuat kunci acak 256-bit (`secrets.token_hex(32)`) dan menulis peringatan ke log.
**Bukti:** `test_secret_key_tidak_hardcode` — PASSED.

> ⚠️ Kunci lama `kunci-rahasia-pencatat-keuangan-rpl` masih ada di riwayat Git, jadi anggap kunci itu **sudah bocor** dan jangan dipakai lagi.

### 2.3 [MEDIUM] Dependensi rentan: Flask 3.0.0 — CVE-2026-27205 (GHSA-68rp-wp8r-4726)

**Hasil `pip-audit` sebelum perbaikan:**
```
Found 2 known vulnerabilities in 1 package
Name  Version ID              Fix Versions
----- ------- --------------- ------------
flask 3.0.0   PYSEC-2026-2151 3.1.3
```

**Risiko:** Pada kondisi tertentu (misalnya akses `session` dengan operator `in`), Flask lupa menambahkan header `Vary: Cookie`. Jika aplikasi berada di belakang *caching proxy*, halaman berisi data pengguna bisa ter-cache dan tampil ke pengguna lain. Aplikasi ini memang memakai `'_csrf' not in session`, sehingga terdampak.

**Perbaikan:**
1. Upgrade ke `Flask==3.1.3`, dan semua dependensi turunan di-*pin* ke versi pasti di `requirements.txt`.
2. Lapisan pertahanan tambahan: semua respons HTML & JSON diberi header `Cache-Control: no-store`.

**Hasil `pip-audit` sesudah perbaikan:**
```
No known vulnerabilities found
```

### 2.4 [MEDIUM] Tidak ada proteksi CSRF

**Risiko:** Situs jahat bisa membuat form tersembunyi yang mengirim `POST /reset` ke aplikasi saat pengguna membukanya, sehingga **semua data terhapus** tanpa sepengetahuan pengguna.

**Perbaikan:** Setiap form menyertakan token acak per-sesi (`csrf_token`), lalu server memverifikasinya dengan `secrets.compare_digest` (tahan *timing attack*). Request tanpa token atau dengan token salah ditolak dengan **HTTP 400**. Cookie sesi juga diberi `HttpOnly` dan `SameSite=Lax`.

**Bukti uji manual:**
```
$ curl -X POST http://127.0.0.1:5000/tambah --data "tanggal=2026-10-22&jenis=pemasukan&keterangan=Tanpa token&nominal=1"
/tambah -> 400
```
**Bukti unit test:** `test_post_tanpa_token_csrf_ditolak`, `test_post_dengan_token_csrf_salah_ditolak`, `test_post_dengan_token_csrf_benar_diterima`, `test_cookie_sesi_httponly_dan_samesite` — PASSED.

### 2.5 [MEDIUM] Tidak ada header keamanan & JavaScript inline

**Risiko:** Tanpa Content-Security-Policy, satu celah XSS bisa langsung menjalankan script. Tanpa `X-Frame-Options`, halaman bisa disisipkan ke iframe untuk *clickjacking*.

**Perbaikan:**
- Header pada setiap respons:
  `Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'self'`
  `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: same-origin`
- Semua `onsubmit="..."` dan `style="..."` inline dipindahkan ke `static/js/app.js` dan `static/css/style.css` agar CSP ketat bisa diterapkan.

**Bukti:** `test_header_keamanan_http_terpasang`, `test_template_tidak_memiliki_script_inline` — PASSED.

### 2.6 [LOW] Validasi input kurang ketat

**Temuan:** Nilai `"nan"` dan `"inf"` lolos sebagai nominal (`float("nan") < 0` bernilai `False`). Tanggal bebas teks juga diterima, dan panjang keterangan tidak dibatasi.

**Perbaikan:** NaN/Infinity ditolak, tanggal wajib berformat `YYYY-MM-DD` yang valid, keterangan maksimal 100 karakter, nominal maksimal Rp 1 triliun, dan ukuran request dibatasi 16 KB (`MAX_CONTENT_LENGTH`).
**Bukti:** `tests/test_validasi_input.py` (6 test), `test_data_terlalu_besar_ditolak` — PASSED.

### 2.7 [LOW] Log injection & kebocoran data di log

**Risiko:** Jika input pengguna ditulis ke log apa adanya, karakter baris baru (`%0a`) bisa dipakai untuk menyisipkan baris log palsu. Selain itu, menulis nominal dan keterangan transaksi ke log membocorkan data keuangan pribadi.

**Perbaikan:** Teks dari request dibersihkan dengan `_aman()` (baris baru → `\n`, dipotong maksimal 200 karakter). Log hanya mencatat ID dan jenis transaksi, **tidak pernah** nominal, keterangan, token CSRF, atau SECRET_KEY.

**Bukti uji manual** (percobaan menyisipkan baris log palsu *"Seluruh data direset."*):
```
2026-10-22 13:55:58,589 WARNING [app] Token CSRF tidak valid: POST /hapus/1\n2026-10-22 13:56:00,000 INFO [app] Seluruh data direset. dari 127.0.0.1
```
Upaya itu tetap berada di **satu baris** dan jelas terlihat sebagai percobaan serangan.
**Bukti unit test:** `test_log_tidak_memuat_data_keuangan_dan_tahan_log_injection` — PASSED.

---

## 3. Kontrol yang Sudah Aman Sejak Awal (Diverifikasi)

| Kontrol | Mekanisme | Bukti |
|---|---|---|
| XSS pada keterangan transaksi | Jinja2 auto-escape | `test_input_xss_di_keterangan_di_escape`: input `<script>alert("xss")</script>` ditampilkan sebagai `&lt;script&gt;...` |
| SQL Injection | Semua query SQLite memakai parameter `?` | `test_payload_sql_injection_disimpan_sebagai_teks_biasa`: input `x'); DROP TABLE transaksi; --` tersimpan sebagai teks biasa, tabel tetap utuh |

---

## 4. Hasil Akhir Alat Pemindai

```
$ bandit -r app.py keuangan_service.py penyimpanan.py
Test results:
        No issues identified.
Code scanned:
        Total lines of code: 369
Run metrics:
        Total issues (by severity):
                Low: 0  Medium: 0  High: 0

$ pip-audit -r requirements.txt
No known vulnerabilities found
```

Cara mengulang pemeriksaan:
```bash
python -m pip install bandit pip-audit
bandit -r app.py keuangan_service.py penyimpanan.py
pip-audit -r requirements.txt
python -m unittest tests.test_keamanan -v
```

---

## 5. Risiko yang Masih Diterima (Di Luar Lingkup MVP)

| Risiko | Alasan diterima | Rekomendasi lanjutan |
|---|---|---|
| Tidak ada login/autentikasi | MVP ditujukan untuk satu pengguna di komputer lokal (`127.0.0.1`) | Tambah login sebelum aplikasi dibuka ke jaringan |
| Server development Flask | Cukup untuk demo lokal | Gunakan `waitress` / `gunicorn` + HTTPS untuk produksi |
| Database tidak terenkripsi | File lokal di komputer pengguna | Batasi izin folder `data/` |
