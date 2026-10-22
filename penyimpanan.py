"""
Modul Penyimpanan Data (Persistence Layer)
Menyimpan saldo awal dan riwayat transaksi ke database SQLite
agar data tidak hilang ketika server dimatikan atau di-restart.
Seluruh query memakai parameter binding (?) untuk mencegah SQL Injection.
"""

import os
import sqlite3
from contextlib import contextmanager

SKEMA = """
CREATE TABLE IF NOT EXISTS pengaturan (
    kunci TEXT PRIMARY KEY,
    nilai REAL NOT NULL
);
CREATE TABLE IF NOT EXISTS transaksi (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tanggal TEXT NOT NULL,
    jenis TEXT NOT NULL CHECK (jenis IN ('pemasukan', 'pengeluaran')),
    keterangan TEXT NOT NULL,
    nominal REAL NOT NULL CHECK (nominal > 0)
);
"""


class PenyimpananSQLite:
    def __init__(self, path):
        self.path = path
        folder = os.path.dirname(os.path.abspath(path))
        os.makedirs(folder, exist_ok=True)
        with self._koneksi() as conn:
            conn.executescript(SKEMA)

    @contextmanager
    def _koneksi(self):
        """Membuka koneksi, commit jika sukses, rollback jika gagal, lalu selalu ditutup."""
        conn = sqlite3.connect(self.path)
        try:
            yield conn
            conn.commit()
        except Exception:
            conn.rollback()
            raise
        finally:
            conn.close()

    def muat(self):
        """Mengembalikan (saldo_awal, daftar_transaksi) yang tersimpan."""
        with self._koneksi() as conn:
            baris = conn.execute(
                "SELECT nilai FROM pengaturan WHERE kunci = 'saldo_awal'"
            ).fetchone()
            saldo_awal = baris[0] if baris else 0.0
            transaksi = [
                {'id': r[0], 'tanggal': r[1], 'jenis': r[2], 'keterangan': r[3], 'nominal': r[4]}
                for r in conn.execute(
                    "SELECT id, tanggal, jenis, keterangan, nominal FROM transaksi ORDER BY id"
                )
            ]
        return saldo_awal, transaksi

    def simpan_saldo_awal(self, nominal):
        with self._koneksi() as conn:
            conn.execute(
                "INSERT INTO pengaturan (kunci, nilai) VALUES ('saldo_awal', ?) "
                "ON CONFLICT(kunci) DO UPDATE SET nilai = excluded.nilai",
                (nominal,),
            )

    def tambah_transaksi(self, tanggal, jenis, keterangan, nominal):
        """Menyimpan transaksi baru dan mengembalikan ID yang dibuat database."""
        with self._koneksi() as conn:
            cur = conn.execute(
                "INSERT INTO transaksi (tanggal, jenis, keterangan, nominal) VALUES (?, ?, ?, ?)",
                (tanggal, jenis, keterangan, nominal),
            )
            return cur.lastrowid

    def hapus_transaksi(self, transaksi_id):
        with self._koneksi() as conn:
            conn.execute("DELETE FROM transaksi WHERE id = ?", (transaksi_id,))

    def reset(self):
        with self._koneksi() as conn:
            conn.execute("DELETE FROM transaksi")
            conn.execute("DELETE FROM pengaturan")
            conn.execute("DELETE FROM sqlite_sequence WHERE name = 'transaksi'")
