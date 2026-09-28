-- Skema Basis Data Keuangan RPL
CREATE DATABASE IF NOT EXISTS `keuangan_rpl` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `keuangan_rpl`;

-- Tabel Pengaturan Sistem (Saldo Awal, dll)
CREATE TABLE IF NOT EXISTS `pengaturan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kunci` VARCHAR(50) NOT NULL UNIQUE,
    `nilai` VARCHAR(255) NOT NULL,
    `keterangan` VARCHAR(255) NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Kategori Transaksi
CREATE TABLE IF NOT EXISTS `kategori` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(100) NOT NULL,
    `jenis` ENUM('pemasukan', 'pengeluaran') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel Transaksi Keuangan
CREATE TABLE IF NOT EXISTS `transaksi` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tanggal` DATE NOT NULL,
    `jenis` ENUM('pemasukan', 'pengeluaran') NOT NULL,
    `kategori` VARCHAR(100) NOT NULL,
    `keterangan` VARCHAR(255) NOT NULL,
    `nominal` BIGINT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data Awal (Seed Data)
INSERT INTO `pengaturan` (`kunci`, `nilai`, `keterangan`) VALUES
('saldo_awal', '5000000', 'Saldo awal kas keuangan')
ON DUPLICATE KEY UPDATE `nilai` = `nilai`;

INSERT INTO `kategori` (`nama`, `jenis`) VALUES
('Gaji Pokok', 'pemasukan'),
('Bonus & THR', 'pemasukan'),
('Penjualan / Usaha', 'pemasukan'),
('Investasi', 'pemasukan'),
('Makanan & Minuman', 'pengeluaran'),
('Transportasi & BBM', 'pengeluaran'),
('Kebutuhan Rumah', 'pengeluaran'),
('Listrik & Air', 'pengeluaran'),
('Hiburan & Rekreasi', 'pengeluaran'),
('Pendidikan & Buku', 'pengeluaran')
ON DUPLICATE KEY UPDATE `nama` = `nama`;

INSERT INTO `transaksi` (`tanggal`, `jenis`, `kategori`, `keterangan`, `nominal`) VALUES
(CURDATE(), 'pemasukan', 'Gaji Pokok', 'Gaji bulan ini', 2500000),
(CURDATE(), 'pengeluaran', 'Kebutuhan Rumah', 'Belanja kebutuhan rumah tangga', 850000),
(DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'pemasukan', 'Penjualan / Usaha', 'Pendapatan usaha sampingan', 500000),
(DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'pengeluaran', 'Makanan & Minuman', 'Makan siang dan persediaan mingguan', 350000)
ON DUPLICATE KEY UPDATE `keterangan` = `keterangan`;
