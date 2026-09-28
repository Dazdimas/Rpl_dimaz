<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'success' => true,
        'data' => $transactions,
        'summary' => [
            'saldo_awal' => hitungSaldoAwal(),
            'total_pemasukan' => hitungTotal('pemasukan', $transactions),
            'total_pengeluaran' => hitungTotal('pengeluaran', $transactions),
            'saldo_akhir' => hitungSaldo($transactions),
        ],
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        $input = $_POST;
    }

    $tanggal = $input['tanggal'] ?? date('Y-m-d');
    $jenis = in_array(($input['jenis'] ?? ''), ['pemasukan', 'pengeluaran'], true)
        ? $input['jenis']
        : 'pemasukan';

    $keterangan = trim((string) ($input['keterangan'] ?? 'Transaksi baru'));
    $nominal = (int) ($input['nominal'] ?? 0);

    if ($nominal <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Nominal harus lebih dari 0.',
        ]);
        exit;
    }

    $newItem = [
        'id' => time(),
        'tanggal' => $tanggal,
        'jenis' => $jenis,
        'keterangan' => $keterangan,
        'nominal' => $nominal,
    ];

    $transactions[] = $newItem;

    echo json_encode([
        'success' => true,
        'message' => 'Transaksi berhasil ditambahkan.',
        'data' => $newItem,
        'summary' => [
            'saldo_awal' => hitungSaldoAwal(),
            'total_pemasukan' => hitungTotal('pemasukan', $transactions),
            'total_pengeluaran' => hitungTotal('pengeluaran', $transactions),
            'saldo_akhir' => hitungSaldo($transactions),
        ],
    ]);
    exit;
}

http_response_code(405);
echo json_encode([
    'success' => false,
    'message' => 'Metode tidak diizinkan.',
]);
