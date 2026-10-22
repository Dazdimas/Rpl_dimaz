import logging
import os
import secrets
import sqlite3
from datetime import date
from logging.handlers import RotatingFileHandler

from flask import (Flask, abort, flash, jsonify, redirect, render_template,
                   request, session, url_for)
from flask.logging import default_handler

from keuangan_service import PencatatKeuangan
from penyimpanan import PenyimpananSQLite

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

PESAN_ERROR = {
    400: "Permintaan tidak valid.",
    404: "Halaman yang Anda cari tidak ditemukan.",
    405: "Metode permintaan tidak diizinkan untuk alamat ini.",
    413: "Data yang dikirim terlalu besar.",
    500: "Terjadi kesalahan pada server. Silakan coba beberapa saat lagi.",
}

PESAN_GAGAL_SIMPAN = "Data gagal disimpan karena gangguan penyimpanan. Silakan coba lagi."


def _aman(teks, maks=200):
    """Membersihkan teks sebelum ditulis ke log (mencegah log injection lewat baris baru)."""
    return str(teks).replace('\r', '\\r').replace('\n', '\\n')[:maks]


def _atur_logging(app):
    app.logger.setLevel(logging.INFO)
    if app.config['TESTING']:
        # Saat unit test, log tidak dicetak ke konsol agar output test tetap bersih.
        app.logger.removeHandler(default_handler)
        app.logger.propagate = False
        app.logger.addHandler(logging.NullHandler())
    if not app.config['LOG_TO_FILE']:
        return
    os.makedirs(app.config['LOG_DIR'], exist_ok=True)
    handler = RotatingFileHandler(
        os.path.join(app.config['LOG_DIR'], 'app.log'),
        maxBytes=1_000_000, backupCount=3, encoding='utf-8',
    )
    handler.setFormatter(logging.Formatter('%(asctime)s %(levelname)s [%(module)s] %(message)s'))
    app.logger.addHandler(handler)


def create_app(config=None):
    app = Flask(__name__)
    app.config.update(
        SECRET_KEY=os.environ.get('SECRET_KEY'),
        DATABASE=os.environ.get('DATABASE_PATH', os.path.join(BASE_DIR, 'data', 'keuangan.db')),
        LOG_DIR=os.environ.get('LOG_DIR', os.path.join(BASE_DIR, 'logs')),
        LOG_TO_FILE=True,
        CSRF_ENABLED=True,
        MAX_CONTENT_LENGTH=16 * 1024,
        SESSION_COOKIE_HTTPONLY=True,
        SESSION_COOKIE_SAMESITE='Lax',
    )
    if config:
        app.config.update(config)

    _atur_logging(app)

    if not app.config['SECRET_KEY']:
        app.config['SECRET_KEY'] = secrets.token_hex(32)
        app.logger.warning("SECRET_KEY tidak diatur; memakai kunci acak sementara (sesi hilang saat restart).")

    service = PencatatKeuangan(penyimpanan=PenyimpananSQLite(app.config['DATABASE']))
    app.extensions['keuangan'] = service
    app.logger.info("Aplikasi dimulai: %d transaksi dimuat dari database.", len(service.transaksi))

    def _csrf_token():
        if '_csrf' not in session:
            session['_csrf'] = secrets.token_hex(16)
        return session['_csrf']

    app.jinja_env.globals['csrf_token'] = _csrf_token

    @app.before_request
    def _cek_csrf():
        if request.method != 'POST' or not app.config['CSRF_ENABLED']:
            return
        token = request.form.get('csrf_token', '')
        if not token or not secrets.compare_digest(token, session.get('_csrf', '')):
            app.logger.warning("Token CSRF tidak valid: %s %s dari %s",
                               request.method, _aman(request.path), request.remote_addr)
            abort(400, description="Sesi formulir tidak valid atau kedaluwarsa. Muat ulang halaman lalu coba lagi.")

    @app.after_request
    def _header_keamanan(resp):
        resp.headers['Content-Security-Policy'] = (
            "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; "
            "form-action 'self'; frame-ancestors 'none'; base-uri 'self'"
        )
        resp.headers['X-Content-Type-Options'] = 'nosniff'
        resp.headers['X-Frame-Options'] = 'DENY'
        resp.headers['Referrer-Policy'] = 'same-origin'
        if resp.mimetype in ('text/html', 'application/json'):
            resp.headers['Cache-Control'] = 'no-store'
        return resp

    def _proses(aksi, label):
        """Menjalankan aksi bisnis dan mengubah error menjadi flash message yang ramah."""
        try:
            return aksi()
        except ValueError as e:
            app.logger.info("Validasi gagal saat %s: %s", label, _aman(e))
            flash(str(e), "danger")
        except sqlite3.Error:
            app.logger.exception("Gagal mengakses database saat %s", label)
            flash(PESAN_GAGAL_SIMPAN, "danger")
        return None

    @app.route('/')
    def index():
        """Menampilkan dashboard pencatatan keuangan dan ringkasan."""
        return render_template('index.html', ringkasan=service.get_ringkasan(),
                               transaksi=service.transaksi, hari_ini=date.today().isoformat())

    @app.route('/set-saldo', methods=['POST'])
    def set_saldo():
        """User Story 1: Mengatur nominal saldo awal."""
        nominal = request.form.get('saldo_awal')
        if _proses(lambda: service.set_saldo_awal(nominal), "mengatur saldo awal") is not None:
            app.logger.info("Saldo awal diperbarui.")
            flash("Saldo awal berhasil disimpan!", "success")
        return redirect(url_for('index'))

    @app.route('/tambah', methods=['POST'])
    def tambah_transaksi():
        """User Story 2 & 3: Menambahkan transaksi baru (pemasukan/pengeluaran)."""
        f = request.form
        baru = _proses(lambda: service.tambah_transaksi(
            tanggal=f.get('tanggal'), jenis=f.get('jenis'),
            keterangan=f.get('keterangan'), nominal=f.get('nominal'),
        ), "menambah transaksi")
        if baru:
            app.logger.info("Transaksi #%d (%s) ditambahkan.", baru['id'], baru['jenis'])
            flash(f"Transaksi {baru['jenis']} '{baru['keterangan']}' berhasil ditambahkan!", "success")
        return redirect(url_for('index'))

    @app.route('/hapus/<int:transaksi_id>', methods=['POST'])
    def hapus_transaksi(transaksi_id):
        """User Story 4: Menghapus data transaksi berdasarkan ID."""
        hapus_item = _proses(lambda: service.hapus_transaksi(transaksi_id), "menghapus transaksi")
        if hapus_item:
            app.logger.info("Transaksi #%d dihapus.", transaksi_id)
            flash(f"Transaksi '{hapus_item['keterangan']}' berhasil dihapus.", "success")
        return redirect(url_for('index'))

    @app.route('/reset', methods=['POST'])
    def reset():
        """Mereset seluruh data saldo dan transaksi."""
        def _reset():
            service.reset_data()
            return True

        if _proses(_reset, "mereset data"):
            app.logger.info("Seluruh data direset.")
            flash("Seluruh data keuangan berhasil direset ke 0.", "success")
        return redirect(url_for('index'))

    @app.route('/api/ringkasan')
    def api_ringkasan():
        """API JSON (read-only) berisi ringkasan saldo dan daftar transaksi."""
        return jsonify(ringkasan=service.get_ringkasan(), transaksi=service.transaksi)

    def _halaman_error(e):
        kode = getattr(e, 'code', 500) or 500
        if kode >= 500:
            app.logger.error("Error %d pada %s %s", kode, request.method, _aman(request.path))
        elif kode != 400:
            app.logger.warning("Error %d pada %s %s", kode, request.method, _aman(request.path))
        pesan = PESAN_ERROR.get(kode, PESAN_ERROR[500])
        if kode == 400 and getattr(e, 'description', None):
            pesan = e.description
        if request.path.startswith('/api/'):
            return jsonify(error=pesan), kode
        return render_template('error.html', kode=kode, pesan=pesan), kode

    for kode in PESAN_ERROR:
        app.register_error_handler(kode, _halaman_error)

    return app


if __name__ == '__main__':
    create_app().run(
        host=os.environ.get('HOST', '127.0.0.1'),
        port=int(os.environ.get('PORT', 5000)),
        debug=os.environ.get('FLASK_DEBUG') == '1',
    )
