<?php
require __DIR__ . '/config.php';

function kirim(array $body, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($body, JSON_THROW_ON_ERROR);
    exit;
}

function tolak(string $alasan, string $pesan, int $status): void
{
    catatLog('WARNING', 'permintaan.ditolak', ['alasan' => $alasan, 'status' => $status]);
    kirim(['success' => false, 'message' => $pesan], $status);
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
    if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > BODY_MAKS_BYTE) {
        tolak('body_terlalu_besar', 'Ukuran data terlalu besar.', 413);
    }

    // Hanya JSON: text/plain dan form bisa dikirim situs lain tanpa preflight CORS.
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0) {
        tolak('content_type', 'Content-Type harus application/json.', 415);
    }

    $input = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($input)) {
        tolak('json_tidak_valid', 'Format JSON tidak valid.', 400);
    }

    return $input;
}

function validasiAtauTolak(callable $validasi, array $input): mixed
{
    try {
        return $validasi($input);
    } catch (ValidasiGagal $e) {
        tolak('validasi', $e->getMessage(), 400);
    }
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

header('Content-Type: application/json; charset=utf-8');
pasangHeaderKeamanan("default-src 'none'; frame-ancestors 'none'");
pasangPenangananError(static function (string $ref): void {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada server.', 'ref' => $ref]);
});

$method = $_SERVER['REQUEST_METHOD'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// CORS berupa daftar izin. Permintaan dari origin lain ditolak di server, bukan hanya oleh browser.
header('Vary: Origin');
if ($origin !== '') {
    if (!in_array($origin, originDiizinkan(), true)) {
        tolak('origin_tidak_diizinkan', 'Origin tidak diizinkan.', 403);
    }

    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$kunci = kunciData();
$db = bacaData();
$action = $_GET['action'] ?? '';
$id = (int) ($_GET['id'] ?? 0);

if ($method === 'GET') {
    kirimSemua($db);
}

// POST api.php?action=budget  -> simpan batas anggaran bulanan
if ($method === 'POST' && $action === 'budget') {
    $db['budget_bulanan'] = validasiAtauTolak('validasiBudget', bacaInput());
    simpanData($db);
    catatLog('INFO', 'budget.diubah', ['budget_bulanan' => $db['budget_bulanan']]);
    kirimSemua($db, 'Batas anggaran berhasil disimpan.');
}

// POST api.php  -> tambah transaksi
if ($method === 'POST') {
    $ids = array_column($db['transactions'], 'id');
    $newItem = ['id' => $ids ? max($ids) + 1 : 1] + validasiAtauTolak('validasiTransaksi', bacaInput());

    $db['transactions'][] = $newItem;
    simpanData($db);
    catatLog('INFO', 'transaksi.ditambah', ['id' => $newItem['id'], 'jenis' => $newItem['jenis'], 'kategori' => $newItem['kategori']]);
    kirimSemua($db, 'Transaksi berhasil ditambahkan.');
}

// PUT api.php?id=1  -> ubah transaksi
if ($method === 'PUT') {
    $index = cariIndex($db['transactions'], $id);

    $db['transactions'][$index] = ['id' => $id] + validasiAtauTolak('validasiTransaksi', bacaInput());
    simpanData($db);
    catatLog('INFO', 'transaksi.diubah', ['id' => $id]);
    kirimSemua($db, 'Transaksi berhasil diperbarui.');
}

// DELETE api.php?id=1  -> hapus transaksi
if ($method === 'DELETE') {
    $index = cariIndex($db['transactions'], $id);

    array_splice($db['transactions'], $index, 1);
    simpanData($db);
    catatLog('INFO', 'transaksi.dihapus', ['id' => $id]);
    kirimSemua($db, 'Transaksi berhasil dihapus.');
}

kirim(['success' => false, 'message' => 'Metode tidak diizinkan.'], 405);
