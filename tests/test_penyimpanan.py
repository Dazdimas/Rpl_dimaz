import os
import sqlite3
import tempfile
import unittest
from unittest import mock

from keuangan_service import PencatatKeuangan
from penyimpanan import PenyimpananSQLite


class TestPenyimpanan(unittest.TestCase):
    """
    Unit Test Aturan Bisnis 7: Penyimpanan Data Permanen (SQLite)
    Menguji bahwa data tetap ada setelah aplikasi dimuat ulang dan
    data di memori tidak berubah jika penyimpanan gagal.
    """

    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.db_path = os.path.join(self.tmp.name, 'uji.db')

    def tearDown(self):
        self.tmp.cleanup()

    def _tracker_baru(self):
        return PencatatKeuangan(penyimpanan=PenyimpananSQLite(self.db_path))

    def test_data_tetap_ada_setelah_dimuat_ulang(self):
        tracker = self._tracker_baru()
        tracker.set_saldo_awal(500000)
        tracker.tambah_transaksi("2026-10-22", "pemasukan", "Gaji", 300000)
        tracker.tambah_transaksi("2026-10-22", "pengeluaran", "Makan", 50000)

        dimuat_ulang = self._tracker_baru()
        self.assertEqual(dimuat_ulang.saldo_awal, 500000.0)
        self.assertEqual(len(dimuat_ulang.transaksi), 2)
        self.assertEqual(dimuat_ulang.hitung_sisa_saldo(), 750000.0)

    def test_hapus_transaksi_tersimpan_permanen(self):
        tracker = self._tracker_baru()
        t1 = tracker.tambah_transaksi("2026-10-22", "pemasukan", "Gaji", 300000)
        tracker.tambah_transaksi("2026-10-22", "pengeluaran", "Makan", 50000)
        tracker.hapus_transaksi(t1['id'])

        dimuat_ulang = self._tracker_baru()
        self.assertEqual([t['keterangan'] for t in dimuat_ulang.transaksi], ["Makan"])

    def test_reset_mengosongkan_database_dan_id_mulai_dari_satu(self):
        tracker = self._tracker_baru()
        tracker.set_saldo_awal(100000)
        tracker.tambah_transaksi("2026-10-22", "pemasukan", "Gaji", 300000)
        tracker.reset_data()

        dimuat_ulang = self._tracker_baru()
        self.assertEqual(dimuat_ulang.saldo_awal, 0.0)
        self.assertEqual(dimuat_ulang.transaksi, [])
        baru = dimuat_ulang.tambah_transaksi("2026-10-23", "pemasukan", "Bonus", 1000)
        self.assertEqual(baru['id'], 1)

    def test_payload_sql_injection_disimpan_sebagai_teks_biasa(self):
        tracker = self._tracker_baru()
        payload = "x'); DROP TABLE transaksi; --"
        tracker.tambah_transaksi("2026-10-22", "pengeluaran", payload, 1000)

        dimuat_ulang = self._tracker_baru()
        self.assertEqual(dimuat_ulang.transaksi[0]['keterangan'], payload)

    def test_gagal_simpan_tidak_mengubah_data_di_memori(self):
        tracker = self._tracker_baru()
        tracker.set_saldo_awal(100000)
        with mock.patch.object(tracker.penyimpanan, 'tambah_transaksi',
                               side_effect=sqlite3.OperationalError("database is locked")):
            with self.assertRaises(sqlite3.Error):
                tracker.tambah_transaksi("2026-10-22", "pemasukan", "Gaji", 300000)
        with mock.patch.object(tracker.penyimpanan, 'simpan_saldo_awal',
                               side_effect=sqlite3.OperationalError("disk I/O error")):
            with self.assertRaises(sqlite3.Error):
                tracker.set_saldo_awal(999)
        self.assertEqual(tracker.transaksi, [])
        self.assertEqual(tracker.saldo_awal, 100000.0)


if __name__ == '__main__':
    unittest.main()
