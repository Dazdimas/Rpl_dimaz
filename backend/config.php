<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

class DatabaseConnection
{
    private static ?PDO $pdo = null;
    private static bool $usingFallback = false;
    private static string $fallbackFile = __DIR__ . '/data_storage.json';

    public static function getConnection(): ?PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
        $dbname = getenv('DB_NAME') ?: 'keuangan_rpl';
        $portsToTry = [3406, 3306];

        if (getenv('DB_PORT')) {
            array_unshift($portsToTry, (int) getenv('DB_PORT'));
            $portsToTry = array_unique($portsToTry);
        }

        foreach ($portsToTry as $port) {
            try {
                $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                $conn = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);

                $conn->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $conn->exec("USE `{$dbname}`");
                self::$pdo = $conn;
                self::ensureTables();
                return self::$pdo;
            } catch (Exception $e) {
                continue;
            }
        }

        self::$usingFallback = true;
        self::initFallbackStorage();
        return null;
    }

    private static function ensureTables(): void
    {
        if (self::$pdo === null) {
            return;
        }

        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS `pengaturan` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `kunci` VARCHAR(50) NOT NULL UNIQUE,
                `nilai` VARCHAR(255) NOT NULL,
                `keterangan` VARCHAR(255) NULL,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `kategori` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `nama` VARCHAR(100) NOT NULL,
                `jenis` ENUM('pemasukan', 'pengeluaran') NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `transaksi` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `tanggal` DATE NOT NULL,
                `jenis` ENUM('pemasukan', 'pengeluaran') NOT NULL,
                `kategori` VARCHAR(100) NOT NULL,
                `keterangan` VARCHAR(255) NOT NULL,
                `nominal` BIGINT NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $stmt = self::$pdo->query("SELECT COUNT(*) as cnt FROM `pengaturan` WHERE `kunci` = 'saldo_awal'");
        if ((int) $stmt->fetch()['cnt'] === 0) {
            self::$pdo->exec("INSERT INTO `pengaturan` (`kunci`, `nilai`, `keterangan`) VALUES ('saldo_awal', '5000000', 'Saldo awal kas')");
        }

        $stmt = self::$pdo->query("SELECT COUNT(*) as cnt FROM `kategori`");
        if ((int) $stmt->fetch()['cnt'] === 0) {
            self::$pdo->exec("
                INSERT INTO `kategori` (`nama`, `jenis`) VALUES
                ('Gaji Pokok', 'pemasukan'),
                ('Bonus & Usaha', 'pemasukan'),
                ('Investasi', 'pemasukan'),
                ('Makanan & Minuman', 'pengeluaran'),
                ('Transportasi', 'pengeluaran'),
                ('Kebutuhan Rumah', 'pengeluaran'),
                ('Tagihan & Listrik', 'pengeluaran'),
                ('Lain-lain', 'pengeluaran');
            ");
        }

        $stmt = self::$pdo->query("SELECT COUNT(*) as cnt FROM `transaksi`");
        if ((int) $stmt->fetch()['cnt'] === 0) {
            self::$pdo->exec("
                INSERT INTO `transaksi` (`tanggal`, `jenis`, `kategori`, `keterangan`, `nominal`) VALUES
                (CURDATE(), 'pemasukan', 'Gaji Pokok', 'Gaji bulan ini', 2500000),
                (CURDATE(), 'pengeluaran', 'Kebutuhan Rumah', 'Belanja kebutuhan rumah tangga', 850000),
                (DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'pemasukan', 'Bonus & Usaha', 'Pendapatan usaha sampingan', 500000);
            ");
        }
    }

    private static function initFallbackStorage(): void
    {
        if (!file_exists(self::$fallbackFile)) {
            $initialData = [
                'SaldoAwal' => 5000000,
                'Kategori' => [
                    ['Id' => 1, 'Nama' => 'Gaji Pokok', 'Jenis' => 'pemasukan'],
                    ['Id' => 2, 'Nama' => 'Bonus & Usaha', 'Jenis' => 'pemasukan'],
                    ['Id' => 3, 'Nama' => 'Makanan & Minuman', 'Jenis' => 'pengeluaran'],
                    ['Id' => 4, 'Nama' => 'Kebutuhan Rumah', 'Jenis' => 'pengeluaran'],
                    ['Id' => 5, 'Nama' => 'Transportasi', 'Jenis' => 'pengeluaran']
                ],
                'Transaksi' => [
                    [
                        'Id' => 1,
                        'Tanggal' => date('Y-m-d'),
                        'Jenis' => 'pemasukan',
                        'Kategori' => 'Gaji Pokok',
                        'Keterangan' => 'Gaji bulan ini',
                        'Nominal' => 2500000,
                        'CreatedAt' => date('Y-m-d H:i:s')
                    ],
                    [
                        'Id' => 2,
                        'Tanggal' => date('Y-m-d'),
                        'Jenis' => 'pengeluaran',
                        'Kategori' => 'Kebutuhan Rumah',
                        'Keterangan' => 'Belanja kebutuhan rumah tangga',
                        'Nominal' => 850000,
                        'CreatedAt' => date('Y-m-d H:i:s')
                    ],
                    [
                        'Id' => 3,
                        'Tanggal' => date('Y-m-d', strtotime('-1 day')),
                        'Jenis' => 'pemasukan',
                        'Kategori' => 'Bonus & Usaha',
                        'Keterangan' => 'Pendapatan usaha sampingan',
                        'Nominal' => 500000,
                        'CreatedAt' => date('Y-m-d H:i:s')
                    ]
                ]
            ];
            file_put_contents(self::$fallbackFile, json_encode($initialData, JSON_PRETTY_PRINT));
        }
    }

    public static function isFallback(): bool
    {
        return self::$usingFallback;
    }

    public static function readFallback(): array
    {
        self::initFallbackStorage();
        $content = file_get_contents(self::$fallbackFile);
        return json_decode($content, true) ?: [];
    }

    public static function writeFallback(array $data): void
    {
        file_put_contents(self::$fallbackFile, json_encode($data, JSON_PRETTY_PRINT));
    }
}

class FinancialService
{
    public static function getSaldoAwal(): int
    {
        $pdo = DatabaseConnection::getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT `nilai` FROM `pengaturan` WHERE `kunci` = 'saldo_awal'");
            $stmt->execute();
            $row = $stmt->fetch();
            return $row ? (int) $row['nilai'] : 5000000;
        }

        $data = DatabaseConnection::readFallback();
        return (int) ($data['SaldoAwal'] ?? 5000000);
    }

    public static function updateSaldoAwal(int $nominal): bool
    {
        $pdo = DatabaseConnection::getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("UPDATE `pengaturan` SET `nilai` = :val WHERE `kunci` = 'saldo_awal'");
            return $stmt->execute(['val' => (string) $nominal]);
        }

        $data = DatabaseConnection::readFallback();
        $data['SaldoAwal'] = $nominal;
        DatabaseConnection::writeFallback($data);
        return true;
    }

    public static function getCategories(): array
    {
        $pdo = DatabaseConnection::getConnection();
        if ($pdo) {
            $stmt = $pdo->query("SELECT `id` as `Id`, `nama` as `Nama`, `jenis` as `Jenis` FROM `kategori` ORDER BY `jenis` ASC, `nama` ASC");
            return $stmt->fetchAll();
        }

        $data = DatabaseConnection::readFallback();
        return $data['Kategori'] ?? [];
    }

    public static function getAllTransactions(?string $jenis = null, ?string $search = null): array
    {
        $pdo = DatabaseConnection::getConnection();
        if ($pdo) {
            $sql = "SELECT `id` as `Id`, `tanggal` as `Tanggal`, `jenis` as `Jenis`, `kategori` as `Kategori`, 
                           `keterangan` as `Keterangan`, `nominal` as `Nominal`, `created_at` as `CreatedAt` 
                    FROM `transaksi` WHERE 1=1";
            $params = [];

            if ($jenis && in_array($jenis, ['pemasukan', 'pengeluaran'], true)) {
                $sql .= " AND `jenis` = :jenis";
                $params['jenis'] = $jenis;
            }

            if ($search && trim($search) !== '') {
                $sql .= " AND (`keterangan` LIKE :search OR `kategori` LIKE :search)";
                $params['search'] = '%' . trim($search) . '%';
            }

            $sql .= " ORDER BY `tanggal` DESC, `id` DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();

            foreach ($results as &$item) {
                $item['Id'] = (int) $item['Id'];
                $item['Nominal'] = (int) $item['Nominal'];
            }
            return $results;
        }

        $data = DatabaseConnection::readFallback();
        $transactions = $data['Transaksi'] ?? [];

        if ($jenis && in_array($jenis, ['pemasukan', 'pengeluaran'], true)) {
            $transactions = array_filter($transactions, fn($t) => $t['Jenis'] === $jenis);
        }

        if ($search && trim($search) !== '') {
            $s = strtolower(trim($search));
            $transactions = array_filter(
                $transactions,
                fn($t) => str_contains(strtolower($t['Keterangan']), $s) || str_contains(strtolower($t['Kategori']), $s)
            );
        }

        usort($transactions, fn($a, $b) => strcmp($b['Tanggal'], $a['Tanggal']) ?: ($b['Id'] <=> $a['Id']));
        return array_values($transactions);
    }

    public static function getTransactionById(int $id): ?array
    {
        $pdo = DatabaseConnection::getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT `id` as `Id`, `tanggal` as `Tanggal`, `jenis` as `Jenis`, `kategori` as `Kategori`, 
                                          `keterangan` as `Keterangan`, `nominal` as `Nominal`, `created_at` as `CreatedAt` 
                                   FROM `transaksi` WHERE `id` = :id");
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            if ($row) {
                $row['Id'] = (int) $row['Id'];
                $row['Nominal'] = (int) $row['Nominal'];
                return $row;
            }
            return null;
        }

        $data = DatabaseConnection::readFallback();
        foreach ($data['Transaksi'] ?? [] as $t) {
            if ((int) $t['Id'] === $id) {
                return $t;
            }
        }
        return null;
    }

    public static function createTransaction(string $tanggal, string $jenis, string $kategori, string $keterangan, int $nominal): ?array
    {
        $pdo = DatabaseConnection::getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("INSERT INTO `transaksi` (`tanggal`, `jenis`, `kategori`, `keterangan`, `nominal`) 
                                   VALUES (:tanggal, :jenis, :kategori, :keterangan, :nominal)");
            $stmt->execute([
                'tanggal' => $tanggal,
                'jenis' => $jenis,
                'kategori' => $kategori,
                'keterangan' => $keterangan,
                'nominal' => $nominal
            ]);
            $insertId = (int) $pdo->lastInsertId();
            return self::getTransactionById($insertId);
        }

        $data = DatabaseConnection::readFallback();
        $newId = time();
        $newItem = [
            'Id' => $newId,
            'Tanggal' => $tanggal,
            'Jenis' => $jenis,
            'Kategori' => $kategori,
            'Keterangan' => $keterangan,
            'Nominal' => $nominal,
            'CreatedAt' => date('Y-m-d H:i:s')
        ];
        $data['Transaksi'][] = $newItem;
        DatabaseConnection::writeFallback($data);
        return $newItem;
    }

    public static function updateTransaction(int $id, string $tanggal, string $jenis, string $kategori, string $keterangan, int $nominal): ?array
    {
        $pdo = DatabaseConnection::getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("UPDATE `transaksi` SET `tanggal` = :tanggal, `jenis` = :jenis, `kategori` = :kategori, 
                                          `keterangan` = :keterangan, `nominal` = :nominal WHERE `id` = :id");
            $stmt->execute([
                'id' => $id,
                'tanggal' => $tanggal,
                'jenis' => $jenis,
                'kategori' => $kategori,
                'keterangan' => $keterangan,
                'nominal' => $nominal
            ]);
            return self::getTransactionById($id);
        }

        $data = DatabaseConnection::readFallback();
        foreach ($data['Transaksi'] as &$t) {
            if ((int) $t['Id'] === $id) {
                $t['Tanggal'] = $tanggal;
                $t['Jenis'] = $jenis;
                $t['Kategori'] = $kategori;
                $t['Keterangan'] = $keterangan;
                $t['Nominal'] = $nominal;
                DatabaseConnection::writeFallback($data);
                return $t;
            }
        }
        return null;
    }

    public static function deleteTransaction(int $id): bool
    {
        $pdo = DatabaseConnection::getConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("DELETE FROM `transaksi` WHERE `id` = :id");
            return $stmt->execute(['id' => $id]);
        }

        $data = DatabaseConnection::readFallback();
        $initialCount = count($data['Transaksi'] ?? []);
        $data['Transaksi'] = array_values(array_filter($data['Transaksi'] ?? [], fn($t) => (int) $t['Id'] !== $id));
        DatabaseConnection::writeFallback($data);
        return count($data['Transaksi']) < $initialCount;
    }

    public static function getFinancialSummary(): array
    {
        $saldoAwal = self::getSaldoAwal();
        $pdo = DatabaseConnection::getConnection();

        if ($pdo) {
            $stmtPemasukan = $pdo->query("SELECT COALESCE(SUM(`nominal`), 0) as total, COUNT(*) as cnt FROM `transaksi` WHERE `jenis` = 'pemasukan'");
            $pemasukanData = $stmtPemasukan->fetch();
            $totalPemasukan = (int) $pemasukanData['total'];
            $pemasukanCount = (int) $pemasukanData['cnt'];

            $stmtPengeluaran = $pdo->query("SELECT COALESCE(SUM(`nominal`), 0) as total, COUNT(*) as cnt FROM `transaksi` WHERE `jenis` = 'pengeluaran'");
            $pengeluaranData = $stmtPengeluaran->fetch();
            $totalPengeluaran = (int) $pengeluaranData['total'];
            $pengeluaranCount = (int) $pengeluaranData['cnt'];
        } else {
            $data = DatabaseConnection::readFallback();
            $transactions = $data['Transaksi'] ?? [];
            $totalPemasukan = 0;
            $pemasukanCount = 0;
            $totalPengeluaran = 0;
            $pengeluaranCount = 0;

            foreach ($transactions as $t) {
                if ($t['Jenis'] === 'pemasukan') {
                    $totalPemasukan += (int) $t['Nominal'];
                    $pemasukanCount++;
                } else {
                    $totalPengeluaran += (int) $t['Nominal'];
                    $pengeluaranCount++;
                }
            }
        }

        $saldoAkhir = $saldoAwal + $totalPemasukan - $totalPengeluaran;

        return [
            'SaldoAwal' => $saldoAwal,
            'TotalPemasukan' => $totalPemasukan,
            'TotalPengeluaran' => $totalPengeluaran,
            'SaldoAkhir' => $saldoAkhir,
            'TotalTransaksi' => $pemasukanCount + $pengeluaranCount,
            'PemasukanCount' => $pemasukanCount,
            'PengeluaranCount' => $pengeluaranCount
        ];
    }
}
