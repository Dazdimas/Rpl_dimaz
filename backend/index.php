<?php
require __DIR__ . '/config.php';

$saldoAwal = hitungSaldoAwal();
$totalPemasukan = hitungTotal('pemasukan', $transactions);
$totalPengeluaran = hitungTotal('pengeluaran', $transactions);
$saldoAkhir = hitungSaldo($transactions);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Keuangan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            margin: 0;
            padding: 32px;
            color: #1f2937;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
        }
        h1 {
            margin-bottom: 8px;
        }
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin: 20px 0;
        }
        .card {
            background: #eef2ff;
            border-radius: 10px;
            padding: 16px;
        }
        .card strong {
            display: block;
            font-size: 12px;
            margin-bottom: 6px;
            color: #4b5563;
        }
        .card span {
            font-size: 24px;
            font-weight: bold;
        }
        code {
            display: block;
            background: #111827;
            color: #e5e7eb;
            padding: 12px;
            border-radius: 8px;
            overflow: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Backend Keuangan PHP</h1>
        <p>API ini berfungsi untuk memproses data pemasukan, pengeluaran, dan saldo.</p>

        <div class="cards">
            <div class="card">
                <strong>Saldo Awal</strong>
                <span>Rp <?= number_format($saldoAwal, 0, ',', '.'); ?></span>
            </div>
            <div class="card">
                <strong>Total Pemasukan</strong>
                <span>Rp <?= number_format($totalPemasukan, 0, ',', '.'); ?></span>
            </div>
            <div class="card">
                <strong>Total Pengeluaran</strong>
                <span>Rp <?= number_format($totalPengeluaran, 0, ',', '.'); ?></span>
            </div>
            <div class="card">
                <strong>Saldo Akhir</strong>
                <span>Rp <?= number_format($saldoAkhir, 0, ',', '.'); ?></span>
            </div>
        </div>

        <h3>Endpoint utama</h3>
        <code>GET /backend/api.php</code>
        <code>POST /backend/api.php</code>

        <p>Gunakan endpoint ini untuk dipanggil oleh frontend.</p>
    </div>
</body>
</html>
