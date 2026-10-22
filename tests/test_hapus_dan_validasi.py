import unittest
from keuangan_service import PencatatKeuangan
from app import app

class TestHapusDanValidasi(unittest.TestCase):
    """
    Unit Test Aturan Bisnis 5: Penghapusan Transaksi & Integrasi Validasi (User Story 4 & 5)
    Menguji penghapusan transaksi berdasarkan ID, integritas data setelah hapus,
    serta integrasi route Flask & penanganan error.
    """

    def setUp(self):
        self.tracker = PencatatKeuangan(saldo_awal=500000)
        self.app_client = app.test_client()

    def test_hapus_transaksi_berdasarkan_id_sukses(self):
        """Memastikan transaksi yang dipilih berhasil dihapus dari daftar."""
        t1 = self.tracker.tambah_transaksi("2026-10-22", "pemasukan", "Gaji Part-time", 300000)
        t2 = self.tracker.tambah_transaksi("2026-10-22", "pengeluaran", "Makan Malam", 50000)

        self.assertEqual(len(self.tracker.transaksi), 2)
        terhapus = self.tracker.hapus_transaksi(t1['id'])

        self.assertEqual(terhapus['id'], t1['id'])
        self.assertEqual(len(self.tracker.transaksi), 1)
        self.assertEqual(self.tracker.transaksi[0]['id'], t2['id'])

    def test_saldo_terkalkulasi_ulang_setelah_transaksi_dihapus(self):
        """Memastikan sisa saldo menyesuaikan secara otomatis setelah transaksi dihapus."""
        t1 = self.tracker.tambah_transaksi("2026-10-22", "pemasukan", "Bonus", 200000)
        # Saldo awal 500.000 + 200.000 = 700.000
        self.assertEqual(self.tracker.hitung_sisa_saldo(), 700000.0)

        self.tracker.hapus_transaksi(t1['id'])
        # Kembali menjadi 500.000
        self.assertEqual(self.tracker.hitung_sisa_saldo(), 500000.0)

    def test_hapus_transaksi_id_tidak_ditemukan_menghasilkan_error(self):
        """Memastikan error muncul jika ID transaksi yang akan dihapus tidak ada di daftar."""
        with self.assertRaises(ValueError) as ctx:
            self.tracker.hapus_transaksi(9999)
        self.assertIn("tidak ditemukan", str(ctx.exception))

    def test_hapus_transaksi_id_bukan_angka(self):
        """Memastikan error muncul jika ID transaksi bukan format numerik."""
        with self.assertRaises(ValueError) as ctx:
            self.tracker.hapus_transaksi("invalid_id")
        self.assertIn("harus berupa angka", str(ctx.exception))

    def test_route_flask_tambah_dan_hapus_transaksi(self):
        """Memastikan endpoint Flask merespons request form dengan redirect status 302."""
        # Test route tambah transaksi
        resp_tambah = self.app_client.post('/tambah', data={
            'tanggal': '2026-10-22',
            'jenis': 'pemasukan',
            'keterangan': 'Test Route Pemasukan',
            'nominal': '150000'
        }, follow_redirects=False)
        self.assertEqual(resp_tambah.status_code, 302)

        # Test route set saldo
        resp_saldo = self.app_client.post('/set-saldo', data={
            'saldo_awal': '250000'
        }, follow_redirects=False)
        self.assertEqual(resp_saldo.status_code, 302)

        # Test index page
        resp_index = self.app_client.get('/')
        self.assertEqual(resp_index.status_code, 200)
        self.assertIn(b"Pencatat Keuangan Harian", resp_index.data)

if __name__ == '__main__':
    unittest.main()

