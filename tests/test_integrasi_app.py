import os
import sqlite3
import tempfile
import unittest
from unittest import mock

from app import create_app


class TestIntegrasiAplikasi(unittest.TestCase):
    """
    Uji Integrasi: Alur Utama dari Awal hingga Akhir
    Menguji UI (route Flask) + logika bisnis + database SQLite sekaligus,
    termasuk data kosong, input tidak valid, dan kegagalan penyimpanan.
    """

    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.config = {
            'TESTING': True,
            'DATABASE': os.path.join(self.tmp.name, 'uji.db'),
            'LOG_TO_FILE': False,
            'CSRF_ENABLED': False,
            'SECRET_KEY': 'kunci-uji',
        }
        self.app = create_app(self.config)
        self.client = self.app.test_client()

    def tearDown(self):
        self.tmp.cleanup()

    def _halaman(self, resp):
        return resp.get_data(as_text=True)

    def test_alur_lengkap_saldo_transaksi_hapus_reset(self):
        # 1. Kondisi awal: data kosong
        html = self._halaman(self.client.get('/'))
        self.assertIn("Belum ada transaksi yang dicatat", html)

        # 2. Atur saldo awal
        resp = self.client.post('/set-saldo', data={'saldo_awal': '300000'}, follow_redirects=True)
        self.assertIn("Saldo awal berhasil disimpan!", self._halaman(resp))

        # 3. Tambah pemasukan dan pengeluaran
        self.client.post('/tambah', data={'tanggal': '2026-10-22', 'jenis': 'pemasukan',
                                          'keterangan': 'Gaji', 'nominal': '500000'})
        resp = self.client.post('/tambah', data={'tanggal': '2026-10-22', 'jenis': 'pengeluaran',
                                                 'keterangan': 'Sewa Kos', 'nominal': '700000'},
                                follow_redirects=True)
        html = self._halaman(resp)
        self.assertIn("Transaksi pengeluaran &#39;Sewa Kos&#39; berhasil ditambahkan!", html)
        self.assertIn("Riwayat Transaksi (2)", html)
        self.assertIn("Rp 100,000", html)  # 300.000 + 500.000 - 700.000

        # 4. Hapus transaksi pemasukan -> saldo menjadi defisit (300.000 - 700.000)
        resp = self.client.post('/hapus/1', follow_redirects=True)
        html = self._halaman(resp)
        self.assertIn("Transaksi &#39;Gaji&#39; berhasil dihapus.", html)
        self.assertIn("Defisit Saldo!", html)
        self.assertIn("Rp -400,000", html)

        # 5. Reset seluruh data
        resp = self.client.post('/reset', follow_redirects=True)
        html = self._halaman(resp)
        self.assertIn("berhasil direset", html)
        self.assertIn("Belum ada transaksi yang dicatat", html)

    def test_data_tetap_ada_setelah_server_restart(self):
        self.client.post('/set-saldo', data={'saldo_awal': '250000'})
        self.client.post('/tambah', data={'tanggal': '2026-10-22', 'jenis': 'pemasukan',
                                          'keterangan': 'Honor', 'nominal': '100000'})

        app_restart = create_app(self.config)
        data = app_restart.test_client().get('/api/ringkasan').get_json()
        self.assertEqual(data['ringkasan']['sisa_saldo'], 350000.0)
        self.assertEqual(data['transaksi'][0]['keterangan'], 'Honor')

    def test_input_tidak_valid_menampilkan_pesan_error(self):
        kasus = [
            ('/set-saldo', {'saldo_awal': '-5000'}, "tidak boleh bernilai negatif"),
            ('/set-saldo', {'saldo_awal': ''}, "harus berupa angka valid"),
            ('/tambah', {'tanggal': '', 'jenis': 'pemasukan', 'keterangan': 'A', 'nominal': '1'},
             "Tanggal transaksi wajib diisi"),
            ('/tambah', {'tanggal': '2026-10-22', 'jenis': 'pemasukan', 'keterangan': ' ', 'nominal': '1'},
             "Keterangan transaksi tidak boleh kosong"),
            ('/tambah', {'tanggal': '2026-10-22', 'jenis': 'pemasukan', 'keterangan': 'A', 'nominal': '0'},
             "harus lebih besar dari 0"),
            ('/tambah', {'tanggal': '2026-10-22', 'jenis': 'hadiah', 'keterangan': 'A', 'nominal': '1'},
             "Jenis transaksi harus"),
            ('/tambah', {}, "Tanggal transaksi wajib diisi"),
        ]
        for url, data, pesan in kasus:
            with self.subTest(url=url, data=data):
                resp = self.client.post(url, data=data, follow_redirects=True)
                self.assertEqual(resp.status_code, 200)
                html = self._halaman(resp)
                self.assertIn("alert-danger", html)
                self.assertIn(pesan, html)
        self.assertEqual(self.client.get('/api/ringkasan').get_json()['transaksi'], [])

    def test_hapus_transaksi_yang_tidak_ada(self):
        resp = self.client.post('/hapus/999', follow_redirects=True)
        self.assertIn("tidak ditemukan", self._halaman(resp))

    def test_gangguan_database_ditangani_tanpa_crash(self):
        service = self.app.extensions['keuangan']
        with mock.patch.object(service.penyimpanan, 'tambah_transaksi',
                               side_effect=sqlite3.OperationalError("database is locked")):
            with self.assertLogs(self.app.logger, level='ERROR') as log:
                resp = self.client.post('/tambah', data={'tanggal': '2026-10-22', 'jenis': 'pemasukan',
                                                         'keterangan': 'Gaji', 'nominal': '1000'},
                                        follow_redirects=True)
        self.assertEqual(resp.status_code, 200)
        self.assertIn("Data gagal disimpan karena gangguan penyimpanan", self._halaman(resp))
        self.assertIn("Gagal mengakses database saat menambah transaksi", log.output[0])
        self.assertEqual(service.transaksi, [])

    def test_halaman_tidak_ditemukan_dan_metode_salah(self):
        resp = self.client.get('/halaman-tidak-ada')
        self.assertEqual(resp.status_code, 404)
        self.assertIn("tidak ditemukan", self._halaman(resp))
        self.assertIn("Kembali ke Dashboard", self._halaman(resp))

        resp = self.client.get('/tambah')
        self.assertEqual(resp.status_code, 405)

    def test_data_terlalu_besar_ditolak(self):
        resp = self.client.post('/tambah', data={'keterangan': 'x' * 20000})
        self.assertEqual(resp.status_code, 413)

    def test_api_ringkasan_mengembalikan_json(self):
        self.client.post('/tambah', data={'tanggal': '2026-10-22', 'jenis': 'pengeluaran',
                                          'keterangan': 'Pulsa', 'nominal': '25000'})
        resp = self.client.get('/api/ringkasan')
        self.assertEqual(resp.status_code, 200)
        data = resp.get_json()
        self.assertEqual(data['ringkasan']['total_pengeluaran'], 25000.0)
        self.assertTrue(data['ringkasan']['is_defisit'])
        self.assertEqual(len(data['transaksi']), 1)

        resp = self.client.get('/api/tidak-ada')
        self.assertEqual(resp.status_code, 404)
        self.assertIn('error', resp.get_json())


if __name__ == '__main__':
    unittest.main()
