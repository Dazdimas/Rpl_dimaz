"""
Modul Layanan Bisnis Pencatat Keuangan Harian
Memisahkan logika bisnis dari lapisan presentasi web (Flask)
agar mudah diuji secara modular (Unit Testable) dan sesuai kaidah Clean Architecture.
"""

import math
from datetime import date

JENIS_VALID = ('pemasukan', 'pengeluaran')
MAKS_PANJANG_KETERANGAN = 100
MAKS_NOMINAL = 1_000_000_000_000  # Rp 1 triliun


def _ke_angka(nilai, pesan_invalid):
    """Mengonversi input ke float; menolak teks, NaN, dan Infinity."""
    try:
        val = float(nilai)
    except (ValueError, TypeError):
        raise ValueError(pesan_invalid)
    if math.isnan(val) or math.isinf(val):
        raise ValueError(pesan_invalid)
    return val


class PencatatKeuangan:
    def __init__(self, saldo_awal=0, penyimpanan=None):
        """
        penyimpanan: objek opsional (mis. PenyimpananSQLite) untuk menyimpan data permanen.
        Tanpa penyimpanan, data hanya disimpan di memori (dipakai pada unit test).
        """
        self.penyimpanan = penyimpanan
        self.saldo_awal = 0.0
        self.transaksi = []
        self._next_id = 1
        if penyimpanan is not None:
            self.saldo_awal, self.transaksi = penyimpanan.muat()
        if saldo_awal:
            self.set_saldo_awal(saldo_awal)

    def set_saldo_awal(self, nominal):
        """
        User Story 1: Mengatur nominal saldo awal.
        Validasi: harus berupa angka dan tidak boleh bernilai negatif (< 0).
        """
        val = _ke_angka(nominal, "Saldo awal harus berupa angka valid.")

        if val < 0:
            raise ValueError("Saldo awal tidak boleh bernilai negatif.")

        if val > MAKS_NOMINAL:
            raise ValueError("Saldo awal melebihi batas maksimum Rp 1.000.000.000.000.")

        if self.penyimpanan is not None:
            self.penyimpanan.simpan_saldo_awal(val)
        self.saldo_awal = val
        return self.saldo_awal

    def tambah_transaksi(self, tanggal, jenis, keterangan, nominal):
        """
        User Story 2 & 3: Menambahkan transaksi pemasukan atau pengeluaran.
        Validasi: tanggal wajib dengan format YYYY-MM-DD, jenis harus 'pemasukan'/'pengeluaran',
        keterangan wajib (maks. 100 karakter), nominal harus angka > 0.
        """
        if not tanggal or not str(tanggal).strip():
            raise ValueError("Tanggal transaksi wajib diisi.")

        try:
            tanggal_bersih = date.fromisoformat(str(tanggal).strip()).isoformat()
        except ValueError:
            raise ValueError("Format tanggal tidak valid. Gunakan format YYYY-MM-DD.")

        if jenis not in JENIS_VALID:
            raise ValueError("Jenis transaksi harus 'pemasukan' atau 'pengeluaran'.")

        if not keterangan or not str(keterangan).strip():
            raise ValueError("Keterangan transaksi tidak boleh kosong.")

        keterangan_bersih = str(keterangan).strip()
        if len(keterangan_bersih) > MAKS_PANJANG_KETERANGAN:
            raise ValueError(f"Keterangan maksimal {MAKS_PANJANG_KETERANGAN} karakter.")

        nom = _ke_angka(nominal, "Nominal transaksi harus berupa angka valid.")

        if nom <= 0:
            raise ValueError("Nominal transaksi harus lebih besar dari 0.")

        if nom > MAKS_NOMINAL:
            raise ValueError("Nominal transaksi melebihi batas maksimum Rp 1.000.000.000.000.")

        if self.penyimpanan is not None:
            t_id = self.penyimpanan.tambah_transaksi(tanggal_bersih, jenis, keterangan_bersih, nom)
        else:
            t_id = self._next_id
            self._next_id += 1

        transaksi_baru = {
            'id': t_id,
            'tanggal': tanggal_bersih,
            'jenis': jenis,
            'keterangan': keterangan_bersih,
            'nominal': nom
        }
        self.transaksi.append(transaksi_baru)
        return transaksi_baru

    def hitung_total_pemasukan(self):
        """Menghitung jumlah seluruh nominal transaksi pemasukan."""
        return sum(t['nominal'] for t in self.transaksi if t['jenis'] == 'pemasukan')

    def hitung_total_pengeluaran(self):
        """Menghitung jumlah seluruh nominal transaksi pengeluaran."""
        return sum(t['nominal'] for t in self.transaksi if t['jenis'] == 'pengeluaran')

    def hitung_sisa_saldo(self):
        """
        User Story 3: Menghitung sisa saldo akhir.
        Formula: Saldo Akhir = Saldo Awal + Total Pemasukan - Total Pengeluaran
        """
        return self.saldo_awal + self.hitung_total_pemasukan() - self.hitung_total_pengeluaran()

    def get_ringkasan(self):
        """
        Mengembalikan ringkasan kondisi keuangan pengguna saat ini.
        """
        tot_masuk = self.hitung_total_pemasukan()
        tot_keluar = self.hitung_total_pengeluaran()
        sisa = self.saldo_awal + tot_masuk - tot_keluar
        return {
            'saldo_awal': self.saldo_awal,
            'total_pemasukan': tot_masuk,
            'total_pengeluaran': tot_keluar,
            'sisa_saldo': sisa,
            'is_defisit': sisa < 0
        }

    def hapus_transaksi(self, transaksi_id):
        """
        User Story 4: Menghapus catatan transaksi berdasarkan ID transaksi.
        """
        try:
            t_id = int(transaksi_id)
        except (ValueError, TypeError):
            raise ValueError("ID transaksi harus berupa angka.")

        for i, item in enumerate(self.transaksi):
            if item['id'] == t_id:
                if self.penyimpanan is not None:
                    self.penyimpanan.hapus_transaksi(t_id)
                return self.transaksi.pop(i)

        raise ValueError(f"Transaksi dengan ID {transaksi_id} tidak ditemukan.")

    def reset_data(self):
        """Mereset seluruh saldo dan riwayat transaksi ke kondisi awal."""
        if self.penyimpanan is not None:
            self.penyimpanan.reset()
        self.saldo_awal = 0.0
        self.transaksi = []
        self._next_id = 1
