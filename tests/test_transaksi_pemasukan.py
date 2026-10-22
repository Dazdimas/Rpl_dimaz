import unittest
from keuangan_service import PencatatKeuangan

class TestTransaksiPemasukan(unittest.TestCase):
    """
    Unit Test Aturan Bisnis 2: Pencatatan Transaksi Pemasukan (User Story 2)
    Menguji penambahan transaksi masuk, akumulasi nominal masuk, dan validasi input.
    """

    def setUp(self):
        self.tracker = PencatatKeuangan()

    def test_tambah_transaksi_pemasukan_sukses(self):
        """Memastikan transaksi pemasukan berhasil disimpan dengan struktur data lengkap."""
        item = self.tracker.tambah_transaksi(
            tanggal="2026-10-22",
            jenis="pemasukan",
            keterangan="Gaji Bulanan",
            nominal=5000000
        )
        self.assertEqual(item['id'], 1)
        self.assertEqual(item['jenis'], 'pemasukan')
        self.assertEqual(item['keterangan'], 'Gaji Bulanan')
        self.assertEqual(item['nominal'], 5000000.0)
        self.assertEqual(len(self.tracker.transaksi), 1)

    def test_akumulasi_total_pemasukan_majemuk(self):
        """Memastikan total pemasukan menghitung seluruh transaksi berjenis pemasukan dengan tepat."""
        self.tracker.tambah_transaksi("2026-10-20", "pemasukan", "Honor Freelance", 1500000)
        self.tracker.tambah_transaksi("2026-10-21", "pemasukan", "Penjualan Barang", 350000)
        
        total_masuk = self.tracker.hitung_total_pemasukan()
        self.assertEqual(total_masuk, 1850000.0)

    def test_validasi_nominal_pemasukan_harus_lebih_dari_nol(self):
        """Aturan Bisnis: Nominal pemasukan tidak boleh 0 atau bernilai negatif."""
        with self.assertRaises(ValueError) as ctx_nol:
            self.tracker.tambah_transaksi("2026-10-22", "pemasukan", "Bonus", 0)
        self.assertIn("lebih besar dari 0", str(ctx_nol.exception))

        with self.assertRaises(ValueError) as ctx_neg:
            self.tracker.tambah_transaksi("2026-10-22", "pemasukan", "Bonus", -50000)
        self.assertIn("lebih besar dari 0", str(ctx_neg.exception))

    def test_validasi_tanggal_pemasukan_wajib_diisi(self):
        """Aturan Bisnis: Tanggal transaksi tidak boleh kosong atau hanya spasi."""
        with self.assertRaises(ValueError) as ctx:
            self.tracker.tambah_transaksi("", "pemasukan", "Uang Saku", 100000)
        self.assertIn("Tanggal transaksi wajib diisi", str(ctx.exception))

        with self.assertRaises(ValueError) as ctx_space:
            self.tracker.tambah_transaksi("   ", "pemasukan", "Uang Saku", 100000)
        self.assertIn("Tanggal transaksi wajib diisi", str(ctx_space.exception))

    def test_validasi_keterangan_pemasukan_tidak_boleh_kosong(self):
        """Aturan Bisnis: Keterangan transaksi tidak boleh string kosong."""
        with self.assertRaises(ValueError) as ctx:
            self.tracker.tambah_transaksi("2026-10-22", "pemasukan", "", 200000)
        self.assertIn("Keterangan transaksi tidak boleh kosong", str(ctx.exception))

if __name__ == '__main__':
    unittest.main()
