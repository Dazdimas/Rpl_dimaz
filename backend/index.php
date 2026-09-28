<?php
require_once __DIR__ . '/config.php';

$summary = FinancialService::getFinancialSummary();
$pdo = DatabaseConnection::getConnection();
$dbStatus = $pdo ? 'Terhubung ke MySQL' : 'Mode Penyimpanan Lokal (Fallback)';
$categories = FinancialService::getCategories();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backend REST API - Pencatat Keuangan RPL</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            padding: 32px 16px;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background: #ffffff;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
        }
        .badge-green { background: #dcfce7; color: #15803d; }
        .badge-blue { background: #dbeafe; color: #1d4ed8; }
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin: 24px 0;
        }
        .card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
        }
        .card label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
            margin-bottom: 6px;
        }
        .card strong {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
        }
        .card.income strong { color: #16a34a; }
        .card.expense strong { color: #dc2626; }
        .card.balance strong { color: #2563eb; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            font-size: 14px;
        }
        th, td {
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        th { background: #f8fafc; font-weight: 600; color: #475569; }
        code {
            background: #f1f5f9;
            padding: 3px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 13px;
            color: #d97706;
        }
        .method {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
        }
        .method-get { background: #dcfce7; color: #16a34a; }
        .method-post { background: #dbeafe; color: #2563eb; }
        .method-put { background: #fef3c7; color: #d97706; }
        .method-delete { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="font-size: 24px;">Backend REST API Keuangan RPL</h1>
                <p style="color: #64748b; margin-top: 4px;">Pencatat Keuangan Harian, Arus Kas & Saldo</p>
            </div>
            <span class="badge badge-green">API Aktif</span>
        </div>

        <div style="margin-top: 16px;">
            <span class="badge badge-blue">Database: <?= htmlspecialchars($dbStatus); ?></span>
        </div>

        <div class="cards">
            <div class="card">
                <label>Saldo Awal</label>
                <strong>Rp <?= number_format($summary['SaldoAwal'], 0, ',', '.'); ?></strong>
            </div>
            <div class="card income">
                <label>Total Pemasukan</label>
                <strong>Rp <?= number_format($summary['TotalPemasukan'], 0, ',', '.'); ?></strong>
            </div>
            <div class="card expense">
                <label>Total Pengeluaran</label>
                <strong>Rp <?= number_format($summary['TotalPengeluaran'], 0, ',', '.'); ?></strong>
            </div>
            <div class="card balance">
                <label>Saldo Akhir</label>
                <strong>Rp <?= number_format($summary['SaldoAkhir'], 0, ',', '.'); ?></strong>
            </div>
        </div>

        <h3 style="margin-top: 24px; font-size: 18px;">Daftar Endpoint API</h3>
        <table>
            <thead>
                <tr>
                    <th>Metode</th>
                    <th>Endpoint</th>
                    <th>Deskripsi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="method method-get">GET</span></td>
                    <td><code>/api.php?action=transactions</code></td>
                    <td>Mengambil semua transaksi & ringkasan saldo (support query: <code>?jenis=pemasukan|pengeluaran&search=...</code>)</td>
                </tr>
                <tr>
                    <td><span class="method method-get">GET</span></td>
                    <td><code>/api.php?action=transactions&id={id}</code></td>
                    <td>Mengambil detail satu transaksi berdasarkan ID</td>
                </tr>
                <tr>
                    <td><span class="method method-post">POST</span></td>
                    <td><code>/api.php?action=transactions</code></td>
                    <td>Menambahkan transaksi baru (JSON body: Tanggal, Jenis, Kategori, Keterangan, Nominal)</td>
                </tr>
                <tr>
                    <td><span class="method method-put">PUT</span></td>
                    <td><code>/api.php?action=transactions&id={id}</code></td>
                    <td>Memperbarui transaksi yang sudah ada</td>
                </tr>
                <tr>
                    <td><span class="method method-delete">DELETE</span></td>
                    <td><code>/api.php?action=transactions&id={id}</code></td>
                    <td>Menghapus transaksi berdasarkan ID</td>
                </tr>
                <tr>
                    <td><span class="method method-get">GET</span></td>
                    <td><code>/api.php?action=saldo-awal</code></td>
                    <td>Mengambil data saldo awal saat ini</td>
                </tr>
                <tr>
                    <td><span class="method method-post">POST</span></td>
                    <td><code>/api.php?action=saldo-awal</code></td>
                    <td>Mengubah / memperbarui nominal saldo awal (JSON body: Nominal)</td>
                </tr>
                <tr>
                    <td><span class="method method-get">GET</span></td>
                    <td><code>/api.php?action=categories</code></td>
                    <td>Mengambil daftar kategori pemasukan dan pengeluaran</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
