import unittest
from keuangan_service import PencatatKeuangan


class TestValidasiInput(unittest.TestCase):
    """
    Unit Test Aturan Bisnis 6: Penanganan Input Tidak Valid
    Menguji format tanggal, nilai angka khusus (NaN/Infinity), batas panjang keterangan,
    dan batas maksimum nominal.
    """

    def setUp(self):
        self.tracker = PencatatKeuangan()

    def test_format_tanggal_tidak_valid_ditolak(self):
        """Tanggal harus berformat YYYY-MM-DD dan merupakan tanggal yang benar-benar ada."""
        for tanggal in ["22-10-2026", "2026-13-01", "2026-02-30", "kemarin"]:
            with self.assertRaises(ValueError) as ctx:
                self.tracker.tambah_transaksi(tanggal, "pemasukan", "Gaji", 100000)
            self.assertIn("Format tanggal tidak valid", str(ctx.exception))

    def test_nominal_nan_dan_infinity_ditolak(self):
        """String 'nan' dan 'inf' lolos float() tetapi harus ditolak sebagai nominal."""
        for nilai in ["nan", "inf", "-inf"]:
            with self.assertRaises(ValueError):
                self.tracker.tambah_transaksi("2026-10-22", "pemasukan", "Gaji", nilai)
            with self.assertRaises(ValueError):
                self.tracker.set_saldo_awal(nilai)
        self.assertEqual(self.tracker.transaksi, [])
        self.assertEqual(self.tracker.saldo_awal, 0.0)

    def test_keterangan_melebihi_100_karakter_ditolak(self):
        with self.assertRaises(ValueError) as ctx:
            self.tracker.tambah_transaksi("2026-10-22", "pengeluaran", "x" * 101, 5000)
        self.assertIn("maksimal 100 karakter", str(ctx.exception))
        item = self.tracker.tambah_transaksi("2026-10-22", "pengeluaran", "x" * 100, 5000)
        self.assertEqual(len(item['keterangan']), 100)

    def test_nominal_melebihi_batas_maksimum_ditolak(self):
        with self.assertRaises(ValueError):
            self.tracker.tambah_transaksi("2026-10-22", "pemasukan", "Lotre", 10 ** 13)
        with self.assertRaises(ValueError):
            self.tracker.set_saldo_awal(10 ** 13)

    def test_input_dibersihkan_dari_spasi(self):
        item = self.tracker.tambah_transaksi(" 2026-10-22 ", "pemasukan", "  Gaji  ", " 1500 ")
        self.assertEqual(item['tanggal'], "2026-10-22")
        self.assertEqual(item['keterangan'], "Gaji")
        self.assertEqual(item['nominal'], 1500.0)

    def test_nominal_kosong_atau_none_ditolak(self):
        for nilai in ["", None]:
            with self.assertRaises(ValueError) as ctx:
                self.tracker.tambah_transaksi("2026-10-22", "pemasukan", "Gaji", nilai)
            self.assertIn("harus berupa angka valid", str(ctx.exception))


if __name__ == '__main__':
    unittest.main()
