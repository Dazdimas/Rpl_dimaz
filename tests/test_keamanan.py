import os
import re
import tempfile
import unittest

from app import create_app


class TestKeamanan(unittest.TestCase):
    """
    Uji Keamanan Aplikasi
    Menguji proteksi CSRF, header keamanan HTTP, pencegahan XSS,
    konfigurasi aman (debug & secret key), dan keamanan log.
    """

    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.app = create_app({
            'TESTING': True,
            'DATABASE': os.path.join(self.tmp.name, 'uji.db'),
            'LOG_TO_FILE': False,
        })
        self.client = self.app.test_client()

    def tearDown(self):
        self.tmp.cleanup()

    def _ambil_token(self):
        html = self.client.get('/').get_data(as_text=True)
        return re.search(r'name="csrf_token" value="([0-9a-f]+)"', html).group(1)

    def _data_transaksi(self, **tambahan):
        data = {'tanggal': '2026-10-22', 'jenis': 'pemasukan', 'keterangan': 'Gaji', 'nominal': '1000'}
        data.update(tambahan)
        return data

    def test_post_tanpa_token_csrf_ditolak(self):
        resp = self.client.post('/tambah', data=self._data_transaksi())
        self.assertEqual(resp.status_code, 400)
        self.assertIn("Sesi formulir tidak valid", resp.get_data(as_text=True))
        self.assertEqual(self.app.extensions['keuangan'].transaksi, [])

    def test_post_dengan_token_csrf_salah_ditolak(self):
        self._ambil_token()
        resp = self.client.post('/reset', data={'csrf_token': 'token-palsu'})
        self.assertEqual(resp.status_code, 400)

    def test_post_dengan_token_csrf_benar_diterima(self):
        token = self._ambil_token()
        resp = self.client.post('/tambah', data=self._data_transaksi(csrf_token=token))
        self.assertEqual(resp.status_code, 302)
        self.assertEqual(len(self.app.extensions['keuangan'].transaksi), 1)

    def test_header_keamanan_http_terpasang(self):
        resp = self.client.get('/')
        self.assertIn("script-src 'self'", resp.headers['Content-Security-Policy'])
        self.assertIn("frame-ancestors 'none'", resp.headers['Content-Security-Policy'])
        self.assertEqual(resp.headers['X-Content-Type-Options'], 'nosniff')
        self.assertEqual(resp.headers['X-Frame-Options'], 'DENY')
        self.assertEqual(resp.headers['Referrer-Policy'], 'same-origin')
        self.assertEqual(resp.headers['Cache-Control'], 'no-store')
        self.assertEqual(self.client.get('/api/ringkasan').headers['Cache-Control'], 'no-store')

    def test_cookie_sesi_httponly_dan_samesite(self):
        self._ambil_token()
        cookie = self.client.get_cookie('session')
        self.assertTrue(cookie.http_only)
        self.assertEqual(cookie.same_site, 'Lax')

    def test_input_xss_di_keterangan_di_escape(self):
        token = self._ambil_token()
        payload = '<script>alert("xss")</script>'
        self.client.post('/tambah', data=self._data_transaksi(keterangan=payload, csrf_token=token))
        html = self.client.get('/').get_data(as_text=True)
        self.assertNotIn(payload, html)
        self.assertIn('&lt;script&gt;', html)

    def test_template_tidak_memiliki_script_inline(self):
        html = self.client.get('/').get_data(as_text=True)
        self.assertNotIn('<script>', html)
        self.assertNotIn('onsubmit=', html)
        self.assertNotIn('style="', html)

    def test_mode_debug_mati_secara_default(self):
        self.assertFalse(self.app.debug)

    def test_secret_key_tidak_hardcode(self):
        app_lain = create_app({'TESTING': True, 'DATABASE': os.path.join(self.tmp.name, 'uji2.db'), 'LOG_TO_FILE': False})
        self.assertNotEqual(self.app.config['SECRET_KEY'], app_lain.config['SECRET_KEY'])
        self.assertGreaterEqual(len(self.app.config['SECRET_KEY']), 32)

    def test_log_tidak_memuat_data_keuangan_dan_tahan_log_injection(self):
        token = self._ambil_token()
        with self.assertLogs(self.app.logger, level='INFO') as log:
            self.client.post('/tambah', data=self._data_transaksi(
                keterangan='Gaji Rahasia', nominal='7654321', csrf_token=token))
            self.client.post('/hapus/1%0aPALSU%20ERROR', data={'csrf_token': 'salah'})
        semua_log = '\n'.join(log.output)
        self.assertNotIn('Gaji Rahasia', semua_log)
        self.assertNotIn('7654321', semua_log)
        self.assertNotIn(token, semua_log)
        for baris in log.output:
            self.assertEqual(len(baris.splitlines()), 1)


if __name__ == '__main__':
    unittest.main()
