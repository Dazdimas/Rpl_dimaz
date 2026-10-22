import unittest
from keuangan_service import PencatatKeuangan

class TestTransaksiPengeluaran(unittest.TestCase):
    """
    Unit Test Aturan Bisnis 3: Pencatatan Transaksi Pengeluaran (User Story 3)
    Menguji penambahan pengeluaran, isolasi akumulasi pengeluaran terhadap pemasukan, dan validasi jenis transaksi.
    """

    def setUp(self):
        self.tracker = PencatatKeuangan()

    def test_tambah_transaksi_pengeluaran_sukses(self):
        """Memastikan transaksi pengeluaran berhasil disimpan dengan benar."""
        item = self.tracker.tambah_transaksi(
            tanggal="2026-10-22",
            jenis="pengeluaran",
            keterangan="Beli Buku Kuliah",
            nominal=120000
        )
        self.assertEqual(item['id'], 1)
        self.assertEqual(item['jenis'], 'pengeluaran')
        self.assertEqual(item['keterangan'], 'Beli Buku Kuliah')
        self.assertEqual(item['nominal'], 120000.0)
        self.assertEqual(len(self.tracker.transaksi), 1)

    def test_akumulasi_total_pengeluaran_majemuk(self):
        """Memastikan total pengeluaran menjumlahkan semua pengeluaran tanpa mencampur pemasukan."""
        self.tracker.tambah_transaksi("2026-10-22", "pengeluaran", "Makan Siang", 35000)
        self.tracker.tambah_transaksi("2026-10-22", "pengeluaran", "Bensin Motor", 50000)
        self.tracker.tambah_transaksi("2026-10-22", "pemasukan", "Cashback", 10000)

        total_keluar = self.tracker.hitung_total_pengeluaran()
        self.assertEqual(total_keluar, 85000.0)
        # Pemasukan tidak boleh terhitung ke pengeluaran
        self.assertEqual(self.tracker.hitung_total_pemasukan(), 10000.0)

    def test_validasi_nominal_pengeluaran_harus_lebih_dari_nol(self):
        """Aturan Bisnis: Pengeluaran harus bernilai positif > 0."""
        with self.assertRaises(ValueError) as ctx:
            self.tracker.tambah_transaksi("2026-10-22", "pengeluaran", "Parkir", 0)
        self.assertIn("lebih besar dari 0", str(ctx.exception))

    def test_validasi_jenis_transaksi_invalid_ditolak(self):
        """Aturan Bisnis: Jenis selain 'pemasukan' dan 'pengeluaran' harus ditolak."""
        with self.assertRaises(ValueError) as ctx:
            self.tracker.tambah_transaksi("2026-10-22", "transfer_ilegal", "Kirim", 100000)
        self.assertIn("Jenis transaksi harus 'pemasukan' atau 'pengeluaran'", str(ctx.exception))

if __name__ == '__main__':
    unittest.main()

