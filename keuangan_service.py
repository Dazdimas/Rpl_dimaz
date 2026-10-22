"""
Modul Layanan Bisnis Pencatat Keuangan Harian
Memisahkan logika bisnis dari lapisan presentasi web (Flask)
agar mudah diuji secara modular (Unit Testable) dan sesuai kaidah Clean Architecture.
"""

class PencatatKeuangan:
    def __init__(self, saldo_awal=0):
        self.saldo_awal = 0.0
        self.transaksi = []
        self._next_id = 1
        if saldo_awal:
            self.set_saldo_awal(saldo_awal)

    def set_saldo_awal(self, nominal):
        """
        User Story 1: Mengatur nominal saldo awal.
        Validasi: harus berupa angka dan tidak boleh bernilai negatif (< 0).
        """
        try:
            val = float(nominal)
        except (ValueError, TypeError):
            raise ValueError("Saldo awal harus berupa angka valid.")

        if val < 0:
            raise ValueError("Saldo awal tidak boleh bernilai negatif.")

        self.saldo_awal = val
        return self.saldo_awal

    def tambah_transaksi(self, tanggal, jenis, keterangan, nominal):
        """
        User Story 2 & 3: Menambahkan transaksi pemasukan atau pengeluaran.
        Validasi: tanggal tidak boleh kosong, jenis harus 'pemasukan'/'pengeluaran',
        keterangan tidak boleh kosong, nominal harus angka > 0.
        """
        if not tanggal or not str(tanggal).strip():
            raise ValueError("Tanggal transaksi wajib diisi.")

        if jenis not in ['pemasukan', 'pengeluaran']:
            raise ValueError("Jenis transaksi harus 'pemasukan' atau 'pengeluaran'.")

        if not keterangan or not str(keterangan).strip():
            raise ValueError("Keterangan transaksi tidak boleh kosong.")

        try:
            nom = float(nominal)
        except (ValueError, TypeError):
            raise ValueError("Nominal transaksi harus berupa angka valid.")

        if nom <= 0:
            raise ValueError("Nominal transaksi harus lebih besar dari 0.")

        transaksi_baru = {
            'id': self._next_id,
            'tanggal': str(tanggal).strip(),
            'jenis': jenis,
            'keterangan': str(keterangan).strip(),
            'nominal': nom
        }
        self._next_id += 1
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
                return self.transaksi.pop(i)

        raise ValueError(f"Transaksi dengan ID {transaksi_id} tidak ditemukan.")

    def reset_data(self):
        """Mereset seluruh saldo dan riwayat transaksi ke kondisi awal."""
        self.saldo_awal = 0.0
        self.transaksi = []
        self._next_id = 1

