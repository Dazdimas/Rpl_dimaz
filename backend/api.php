<?php
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'transactions';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!$input) {
    $input = $_POST;
}

if ($method === 'POST' && isset($input['_method'])) {
    $method = strtoupper($input['_method']);
}

if ($action === 'saldo-awal') {
    if ($method === 'GET') {
        echo json_encode([
            'Success' => true,
            'Data' => [
                'SaldoAwal' => FinancialService::getSaldoAwal()
            ]
        ]);
        exit;
    }

    if ($method === 'POST') {
        $nominal = isset($input['Nominal']) ? (int) $input['Nominal'] : (int) ($input['nominal'] ?? 0);
        if ($nominal < 0) {
            http_response_code(400);
            echo json_encode([
                'Success' => false,
                'Message' => 'Nominal saldo awal tidak boleh negatif.'
            ]);
            exit;
        }

        FinancialService::updateSaldoAwal($nominal);
        echo json_encode([
            'Success' => true,
            'Message' => 'Saldo awal berhasil diperbarui.',
            'Summary' => FinancialService::getFinancialSummary()
        ]);
        exit;
    }
}

if ($action === 'categories') {
    if ($method === 'GET') {
        echo json_encode([
            'Success' => true,
            'Data' => FinancialService::getCategories()
        ]);
        exit;
    }
}

if ($action === 'summary' || $action === 'dashboard') {
    if ($method === 'GET') {
        echo json_encode([
            'Success' => true,
            'Summary' => FinancialService::getFinancialSummary()
        ]);
        exit;
    }
}

if ($action === 'transactions' || $action === '') {
    if ($method === 'GET') {
        if ($id !== null) {
            $transaction = FinancialService::getTransactionById($id);
            if (!$transaction) {
                http_response_code(404);
                echo json_encode([
                    'Success' => false,
                    'Message' => 'Transaksi tidak ditemukan.'
                ]);
                exit;
            }

            echo json_encode([
                'Success' => true,
                'Data' => $transaction
            ]);
            exit;
        }

        $jenis = $_GET['jenis'] ?? null;
        $search = $_GET['search'] ?? null;
        $list = FinancialService::getAllTransactions($jenis, $search);

        echo json_encode([
            'Success' => true,
            'Data' => $list,
            'Summary' => FinancialService::getFinancialSummary()
        ]);
        exit;
    }

    if ($method === 'POST') {
        $tanggal = $input['Tanggal'] ?? ($input['tanggal'] ?? date('Y-m-d'));
        $rawJenis = strtolower($input['Jenis'] ?? ($input['jenis'] ?? 'pemasukan'));
        $jenis = in_array($rawJenis, ['pemasukan', 'pengeluaran'], true) ? $rawJenis : 'pemasukan';
        $kategori = trim((string) ($input['Kategori'] ?? ($input['kategori'] ?? 'Lain-lain')));
        $keterangan = trim((string) ($input['Keterangan'] ?? ($input['keterangan'] ?? '')));
        $nominal = isset($input['Nominal']) ? (int) $input['Nominal'] : (int) ($input['nominal'] ?? 0);

        if ($keterangan === '') {
            http_response_code(400);
            echo json_encode([
                'Success' => false,
                'Message' => 'Keterangan transaksi wajib diisi.'
            ]);
            exit;
        }

        if ($nominal <= 0) {
            http_response_code(400);
            echo json_encode([
                'Success' => false,
                'Message' => 'Nominal harus lebih dari 0.'
            ]);
            exit;
        }

        $created = FinancialService::createTransaction($tanggal, $jenis, $kategori, $keterangan, $nominal);

        http_response_code(201);
        echo json_encode([
            'Success' => true,
            'Message' => 'Transaksi berhasil ditambahkan.',
            'Data' => $created,
            'Summary' => FinancialService::getFinancialSummary()
        ]);
        exit;
    }

    if ($method === 'PUT') {
        if ($id === null) {
            $id = isset($input['Id']) ? (int) $input['Id'] : (int) ($input['id'] ?? 0);
        }

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode([
                'Success' => false,
                'Message' => 'ID transaksi wajib disertakan.'
            ]);
            exit;
        }

        $existing = FinancialService::getTransactionById($id);
        if (!$existing) {
            http_response_code(404);
            echo json_encode([
                'Success' => false,
                'Message' => 'Transaksi tidak ditemukan.'
            ]);
            exit;
        }

        $tanggal = $input['Tanggal'] ?? ($input['tanggal'] ?? $existing['Tanggal']);
        $rawJenis = strtolower($input['Jenis'] ?? ($input['jenis'] ?? $existing['Jenis']));
        $jenis = in_array($rawJenis, ['pemasukan', 'pengeluaran'], true) ? $rawJenis : 'pemasukan';
        $kategori = trim((string) ($input['Kategori'] ?? ($input['kategori'] ?? $existing['Kategori'])));
        $keterangan = trim((string) ($input['Keterangan'] ?? ($input['keterangan'] ?? $existing['Keterangan'])));
        $nominal = isset($input['Nominal']) ? (int) $input['Nominal'] : (int) ($input['nominal'] ?? $existing['Nominal']);

        if ($keterangan === '') {
            http_response_code(400);
            echo json_encode([
                'Success' => false,
                'Message' => 'Keterangan transaksi wajib diisi.'
            ]);
            exit;
        }

        if ($nominal <= 0) {
            http_response_code(400);
            echo json_encode([
                'Success' => false,
                'Message' => 'Nominal harus lebih dari 0.'
            ]);
            exit;
        }

        $updated = FinancialService::updateTransaction($id, $tanggal, $jenis, $kategori, $keterangan, $nominal);

        echo json_encode([
            'Success' => true,
            'Message' => 'Transaksi berhasil diperbarui.',
            'Data' => $updated,
            'Summary' => FinancialService::getFinancialSummary()
        ]);
        exit;
    }

    if ($method === 'DELETE') {
        if ($id === null) {
            $id = isset($input['Id']) ? (int) $input['Id'] : (int) ($input['id'] ?? 0);
        }

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode([
                'Success' => false,
                'Message' => 'ID transaksi tidak valid.'
            ]);
            exit;
        }

        $deleted = FinancialService::deleteTransaction($id);
        if (!$deleted) {
            http_response_code(404);
            echo json_encode([
                'Success' => false,
                'Message' => 'Transaksi gagal dihapus atau tidak ditemukan.'
            ]);
            exit;
        }

        echo json_encode([
            'Success' => true,
            'Message' => 'Transaksi berhasil dihapus.',
            'Summary' => FinancialService::getFinancialSummary()
        ]);
        exit;
    }
}

http_response_code(405);
echo json_encode([
    'Success' => false,
    'Message' => 'Metode atau aksi tidak diizinkan.'
]);
