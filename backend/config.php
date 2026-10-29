<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

const DATA_FILE = __DIR__ . '/data.json';

const KATEGORI = ['Makanan', 'Transportasi', 'Tagihan', 'Hiburan', 'Gaji', 'Lain-lain'];

function dataAwal(): array
{
    return [
        'budget_bulanan' => 3000000,
        'transactions' => [
            [
                'id' => 1,
                'tanggal' => '2026-09-28',
                'jenis' => 'pemasukan',
                'kategori' => 'Gaji',
                'keterangan' => 'Gaji bulan ini',
                'nominal' => 2500000,
            ],
            [
                'id' => 2,
                'tanggal' => '2026-09-28',
                'jenis' => 'pengeluaran',
                'kategori' => 'Tagihan',
                'keterangan' => 'Belanja kebutuhan rumah',
                'nominal' => 850000,
            ],
            [
                'id' => 3,
                'tanggal' => '2026-09-27',
                'jenis' => 'pemasukan',
                'kategori' => 'Lain-lain',
                'keterangan' => 'Pendapatan tambahan',
                'nominal' => 500000,
            ],
        ],
    ];
}

function bacaData(): array
{
    if (!file_exists(DATA_FILE)) {
        simpanData(dataAwal());
    }

    $data = json_decode((string) file_get_contents(DATA_FILE), true);

    return is_array($data) ? $data : dataAwal();
}

function simpanData(array $data): void
{
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

function hitungSaldoAwal(): int
{
    return 5000000;
}

function hitungTotal(string $jenis, array $data): int
{
    $total = 0;

    foreach ($data as $item) {
        if ($item['jenis'] === $jenis) {
            $total += (int) $item['nominal'];
        }
    }

    return $total;
}

function hitungSaldo(array $data): int
{
    $saldoAwal = hitungSaldoAwal();
    $totalPemasukan = hitungTotal('pemasukan', $data);
    $totalPengeluaran = hitungTotal('pengeluaran', $data);

    return $saldoAwal + $totalPemasukan - $totalPengeluaran;
}

function buatRingkasan(array $data): array
{
    return [
        'saldo_awal' => hitungSaldoAwal(),
        'total_pemasukan' => hitungTotal('pemasukan', $data),
        'total_pengeluaran' => hitungTotal('pengeluaran', $data),
        'saldo_akhir' => hitungSaldo($data),
    ];
}
