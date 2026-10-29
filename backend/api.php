<?php
require __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function kirim(array $body, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

function kirimSemua(array $db, string $message = ''): void
{
    kirim([
        'success' => true,
        'message' => $message,
        'data' => $db['transactions'],
        'kategori' => KATEGORI,
        'budget_bulanan' => (int) $db['budget_bulanan'],
        'summary' => buatRingkasan($db['transactions']),
    ]);
}

function bacaInput(): array
{
    $input = json_decode(file_get_contents('php://input'), true);

    return is_array($input) ? $input : $_POST;
}

function validasiTransaksi(array $input): array
{
    $tanggal = (string) ($input['tanggal'] ?? '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        $tanggal = date('Y-m-d');
    }

    $jenis = in_array(($input['jenis'] ?? ''), ['pemasukan', 'pengeluaran'], true)
        ? $input['jenis']
        : 'pemasukan';

    $kategori = in_array(($input['kategori'] ?? ''), KATEGORI, true)
        ? $input['kategori']
        : 'Lain-lain';

    $keterangan = trim((string) ($input['keterangan'] ?? ''));
    $nominal = (int) ($input['nominal'] ?? 0);

    if ($nominal <= 0) {
        kirim(['success' => false, 'message' => 'Nominal harus lebih dari 0.'], 400);
    }

    return [
        'tanggal' => $tanggal,
        'jenis' => $jenis,
        'kategori' => $kategori,
        'keterangan' => $keterangan !== '' ? $keterangan : 'Transaksi baru',
        'nominal' => $nominal,
    ];
}

function cariIndex(array $transactions, int $id): int
{
    foreach ($transactions as $index => $item) {
        if ((int) $item['id'] === $id) {
            return $index;
        }
    }

    kirim(['success' => false, 'message' => 'Transaksi tidak ditemukan.'], 404);
}

$db = bacaData();
$action = $_GET['action'] ?? '';
$id = (int) ($_GET['id'] ?? 0);

if ($method === 'GET') {
    kirimSemua($db);
}

// POST api.php?action=budget  -> simpan batas anggaran bulanan
if ($method === 'POST' && $action === 'budget') {
    $budget = (int) (bacaInput()['budget_bulanan'] ?? 0);

    if ($budget < 0) {
        kirim(['success' => false, 'message' => 'Batas anggaran tidak boleh negatif.'], 400);
    }

    $db['budget_bulanan'] = $budget;
    simpanData($db);
    kirimSemua($db, 'Batas anggaran berhasil disimpan.');
}

// POST api.php  -> tambah transaksi
if ($method === 'POST') {
    $ids = array_column($db['transactions'], 'id');
    $newItem = ['id' => $ids ? max($ids) + 1 : 1] + validasiTransaksi(bacaInput());

    $db['transactions'][] = $newItem;
    simpanData($db);
    kirimSemua($db, 'Transaksi berhasil ditambahkan.');
}

// PUT api.php?id=1  -> ubah transaksi
if ($method === 'PUT') {
    $index = cariIndex($db['transactions'], $id);

    $db['transactions'][$index] = ['id' => $id] + validasiTransaksi(bacaInput());
    simpanData($db);
    kirimSemua($db, 'Transaksi berhasil diperbarui.');
}

// DELETE api.php?id=1  -> hapus transaksi
if ($method === 'DELETE') {
    $index = cariIndex($db['transactions'], $id);

    array_splice($db['transactions'], $index, 1);
    simpanData($db);
    kirimSemua($db, 'Transaksi berhasil dihapus.');
}

kirim(['success' => false, 'message' => 'Metode tidak diizinkan.'], 405);
