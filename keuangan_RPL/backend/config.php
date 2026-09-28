<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$transactions = [
    [
        'id' => 1,
        'tanggal' => '2026-09-28',
        'jenis' => 'pemasukan',
        'keterangan' => 'Gaji bulan ini',
        'nominal' => 2500000,
    ],
    [
        'id' => 2,
        'tanggal' => '2026-09-28',
        'jenis' => 'pengeluaran',
        'keterangan' => 'Belanja kebutuhan rumah',
        'nominal' => 850000,
    ],
    [
        'id' => 3,
        'tanggal' => '2026-09-27',
        'jenis' => 'pemasukan',
        'keterangan' => 'Pendapatan tambahan',
        'nominal' => 500000,
    ],
];

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
