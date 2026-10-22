import unittest
from keuangan_service import PencatatKeuangan

class TestSaldoAwal(unittest.TestCase):
    """
    Unit Test Aturan Bisnis 1: Manajemen Saldo Awal (User Story 1)
    Menguji inisialisasi, perubahan nilai saldo awal, dan validasi nilai tidak boleh negatif.
    """

    def setUp(self):
        self.tracker = PencatatKeuangan()

    def test_default_saldo_awal_adalah_nol(self):
        """Memastikan saldo awal default adalah 0 saat sistem pertama kali dibuat."""
        self.assertEqual(self.tracker.saldo_awal, 0.0)

    def test_set_saldo_awal_positif(self):
        """Memastikan pengguna dapat mengatur saldo awal dengan nilai angka positif."""
        hasil = self.tracker.set_saldo_awal(500000)
        self.assertEqual(hasil, 500000.0)
        self.assertEqual(self.tracker.saldo_awal, 500000.0)

    def test_set_saldo_awal_nol(self):
        """Memastikan nilai 0 adalah nilai saldo awal yang valid."""
        hasil = self.tracker.set_saldo_awal(0)
        self.assertEqual(hasil, 0.0)
        self.assertEqual(self.tracker.saldo_awal, 0.0)

    def test_set_saldo_awal_string_numerik(self):
        """Memastikan input string berupa angka dapat dikonversi dengan baik."""
        hasil = self.tracker.set_saldo_awal("750000")
        self.assertEqual(hasil, 750000.0)

    def test_set_saldo_awal_desimal(self):
        """Memastikan saldo awal dengan nilai desimal/float didukung."""
        hasil = self.tracker.set_saldo_awal(125000.50)
        self.assertEqual(hasil, 125000.50)

    def test_validasi_saldo_awal_negatif_menghasilkan_error(self):
        """Aturan Bisnis: Saldo awal tidak boleh bernilai negatif (< 0)."""
        with self.assertRaises(ValueError) as context:
            self.tracker.set_saldo_awal(-10000)
        self.assertIn("tidak boleh bernilai negatif", str(context.exception))

    def test_validasi_saldo_awal_non_numerik_menghasilkan_error(self):
        """Aturan Bisnis: Saldo awal yang bukan angka harus ditolak."""
        with self.assertRaises(ValueError) as context:
            self.tracker.set_saldo_awal("abc_invalid")
        self.assertIn("harus berupa angka valid", str(context.exception))

    def test_update_saldo_awal_berulang(self):
        """Memastikan perubahan saldo awal memperbarui nilai sebelumnya."""
        self.tracker.set_saldo_awal(100000)
        self.tracker.set_saldo_awal(250000)
        self.assertEqual(self.tracker.saldo_awal, 250000.0)

if __name__ == '__main__':
    unittest.main()

