import unittest
from keuangan_service import PencatatKeuangan

class TestKalkulasiSaldo(unittest.TestCase):
    """
    Unit Test Aturan Bisnis 4: Kalkulasi Saldo dan Ringkasan Finansial (User Story 3)
    Menguji keakuratan formula Saldo Akhir = Saldo Awal + Total Pemasukan - Total Pengeluaran,
    kondisi surplus, defisit, dan mekanisme reset.
    """

    def setUp(self):
        self.tracker = PencatatKeuangan(saldo_awal=1000000)

    def test_formula_saldo_akhir_surplus(self):
        """Memastikan perhitungan saldo akhir benar saat kondisi surplus."""
        self.tracker.tambah_transaksi("2026-10-22", "pemasukan", "Dividen", 300000)
        self.tracker.tambah_transaksi("2026-10-22", "pengeluaran", "Belanja", 200000)
        
        # 1.000.000 + 300.000 - 200.000 = 1.100.000
        sisa = self.tracker.hitung_sisa_saldo()
        self.assertEqual(sisa, 1100000.0)

    def test_kondisi_saldo_defisit(self):
        """Memastikan status defisit bernilai True ketika pengeluaran melebihi total dana."""
        self.tracker.tambah_transaksi("2026-10-22", "pengeluaran", "Servis Laptop", 1500000)
        
        # 1.000.000 - 1.500.000 = -500.000
        ringkasan = self.tracker.get_ringkasan()
        self.assertEqual(ringkasan['sisa_saldo'], -500000.0)
        self.assertTrue(ringkasan['is_defisit'])

    def test_struktur_data_get_ringkasan(self):
        """Memastikan dictionary get_ringkasan menyediakan seluruh atribut yang dibutuhkan tampilan UI."""
        self.tracker.tambah_transaksi("2026-10-22", "pemasukan", "Investasi", 500000)
        self.tracker.tambah_transaksi("2026-10-22", "pengeluaran", "Sewa Kos", 700000)

        ringkasan = self.tracker.get_ringkasan()
        self.assertIn('saldo_awal', ringkasan)
        self.assertIn('total_pemasukan', ringkasan)
        self.assertIn('total_pengeluaran', ringkasan)
        self.assertIn('sisa_saldo', ringkasan)
        self.assertIn('is_defisit', ringkasan)
        self.assertEqual(ringkasan['sisa_saldo'], 800000.0)
        self.assertFalse(ringkasan['is_defisit'])

    def test_reset_data_mengembalikan_status_ke_awal(self):
        """Memastikan fungsi reset menghapus seluruh transaksi dan mengembalikan saldo ke nol."""
        self.tracker.tambah_transaksi("2026-10-22", "pemasukan", "Gaji", 2000000)
        self.tracker.reset_data()

        self.assertEqual(self.tracker.saldo_awal, 0.0)
        self.assertEqual(len(self.tracker.transaksi), 0)
        self.assertEqual(self.tracker.hitung_sisa_saldo(), 0.0)

if __name__ == '__main__':
    unittest.main()
