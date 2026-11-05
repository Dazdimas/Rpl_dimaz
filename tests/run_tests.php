<?php
// Unit test & test integrasi API tanpa dependensi (Composer/PHPUnit tidak diperlukan).
//   php tests/run_tests.php
// Semua data ditulis ke folder sementara; data asli di storage/ tidak disentuh.

require __DIR__ . '/lib.php';

$tmp = folderSementara();
$dataFile = $tmp . DIRECTORY_SEPARATOR . 'data.json';
$logFile = $tmp . DIRECTORY_SEPARATOR . 'app.log';
putenv("KEUANGAN_DATA_FILE=$dataFile");
putenv("KEUANGAN_LOG_FILE=$logFile");

require dirname(__DIR__) . '/backend/config.php';

$lulus = 0;
$gagal = 0;

function uji(string $nama, callable $fn): void
{
    global $lulus, $gagal;

    try {
        $fn();
        $lulus++;
        echo "[PASS] $nama\n";
    } catch (Throwable $e) {
        $gagal++;
        echo "[FAIL] $nama\n       " . $e->getMessage() . "\n";
    }
}

function sama(mixed $harap, mixed $nyata, string $pesan = ''): void
{
    if ($harap !== $nyata) {
        throw new RuntimeException(trim("$pesan harap " . json_encode($harap) . ', nyata ' . json_encode($nyata)));
    }
}

function benar(bool $kondisi, string $pesan): void
{
    if (!$kondisi) {
        throw new RuntimeException($pesan);
    }
}

function harusDitolak(array $input): void
{
    try {
        validasiTransaksi($input);
    } catch (ValidasiGagal) {
        return;
    }

    throw new RuntimeException('Input seharusnya ditolak: ' . json_encode($input));
}

function buangDataUji(string $dataFile): void
{
    @unlink($dataFile);
    @unlink($dataFile . '.lock');
}

echo "== Unit: perhitungan ==\n";
$contoh = [
    ['jenis' => 'pemasukan', 'nominal' => 1000],
    ['jenis' => 'pemasukan', 'nominal' => 500],
    ['jenis' => 'pengeluaran', 'nominal' => 300],
];

uji('hitungTotal menjumlahkan pemasukan', fn() => sama(1500, hitungTotal('pemasukan', $contoh)));
uji('hitungTotal menjumlahkan pengeluaran', fn() => sama(300, hitungTotal('pengeluaran', $contoh)));
uji('hitungTotal data kosong = 0', fn() => sama(0, hitungTotal('pemasukan', [])));
uji('hitungSaldo = saldo awal + masuk - keluar', fn() => sama(5001200, hitungSaldo($contoh)));
uji('buatRingkasan memuat keempat angka', fn() => sama(
    ['saldo_awal' => 5000000, 'total_pemasukan' => 1500, 'total_pengeluaran' => 300, 'saldo_akhir' => 5001200],
    buatRingkasan($contoh)
));

echo "\n== Unit: validasi ==\n";
uji('transaksi valid diterima', fn() => sama(transaksiValid(), validasiTransaksi(transaksiValid())));
uji('nominal teks angka dari form diubah ke int', fn() => sama(25000, validasiTransaksi(transaksiValid(['nominal' => '25000']))['nominal']));
uji('keterangan kosong diberi nilai bawaan', fn() => sama('Transaksi baru', validasiTransaksi(transaksiValid(['keterangan' => '  ']))['keterangan']));
uji('tanggal kabisat 2028-02-29 diterima', fn() => sama('2028-02-29', validasiTransaksi(transaksiValid(['tanggal' => '2028-02-29']))['tanggal']));
uji('nominal batas atas diterima', fn() => sama(NOMINAL_MAKS, validasiTransaksi(transaksiValid(['nominal' => NOMINAL_MAKS]))['nominal']));
uji('tanggal mustahil ditolak', fn() => harusDitolak(transaksiValid(['tanggal' => '2026-99-99'])));
uji('2027-02-29 (bukan kabisat) ditolak', fn() => harusDitolak(transaksiValid(['tanggal' => '2027-02-29'])));
uji('tanggal dengan baris baru ditolak', fn() => harusDitolak(transaksiValid(['tanggal' => "2026-10-01\n"])));
uji('tanggal berupa array ditolak', fn() => harusDitolak(transaksiValid(['tanggal' => ['2026-10-01']])));
uji('jenis tidak dikenal ditolak', fn() => harusDitolak(transaksiValid(['jenis' => 'hadiah'])));
uji('kategori tidak dikenal ditolak', fn() => harusDitolak(transaksiValid(['kategori' => '<script>'])));
uji('keterangan array ditolak', fn() => harusDitolak(transaksiValid(['keterangan' => ['x']])));
uji('keterangan UTF-8 rusak ditolak', fn() => harusDitolak(transaksiValid(['keterangan' => "\xFF"])));
uji('keterangan 256 karakter ditolak', fn() => harusDitolak(transaksiValid(['keterangan' => str_repeat('a', 256)])));
uji('keterangan 255 karakter multibyte diterima', fn() => sama(
    str_repeat('é', 255),
    validasiTransaksi(transaksiValid(['keterangan' => str_repeat('é', 255)]))['keterangan']
));
foreach (['12abc', '1e30', [5], true, 1.5, -1, 0, null, NOMINAL_MAKS + 1, PHP_INT_MAX] as $nilai) {
    uji('nominal ' . json_encode($nilai) . ' ditolak', fn() => harusDitolak(transaksiValid(['nominal' => $nilai])));
}
uji('budget 0 diterima', fn() => sama(0, validasiBudget(['budget_bulanan' => 0])));
uji('budget negatif ditolak', function () {
    try {
        validasiBudget(['budget_bulanan' => -1]);
    } catch (ValidasiGagal) {
        return;
    }
    throw new RuntimeException('Budget negatif lolos');
});

echo "\n== Unit: log aman ==\n";
uji('kunci rahasia disensor', fn() => sama(
    ['password' => '[REDACTED]', 'api_key' => '[REDACTED]', 'Authorization' => '[REDACTED]'],
    sanitasiLog(['password' => 'x', 'api_key' => 'y', 'Authorization' => 'Bearer z'])
));
uji('data keuangan disamarkan', fn() => sama(
    ['nominal' => '[MASKED]', 'keterangan' => '[MASKED]'],
    sanitasiLog(['nominal' => 2500000, 'keterangan' => 'Gaji'])
));
uji('konteks bersarang ikut dibersihkan', fn() => sama(
    ['a' => ['token' => '[REDACTED]']],
    sanitasiLog(['a' => ['token' => 'abc']])
));
uji('path, email, dan rahasia di dalam teks dibuang', function () {
    $hasil = sanitasiTeksLog('gagal C:\\Users\\budi\\app\\x.php budi@contoh.id password=abc123');
    benar(!str_contains($hasil, 'budi'), "path/email masih ada: $hasil");
    benar(!str_contains($hasil, 'abc123'), "rahasia masih ada: $hasil");
});
uji('teks log dipotong 200 karakter', fn() => sama(200, mb_strlen(sanitasiTeksLog(str_repeat('a', 500)))));
uji('baris baru tidak bisa memalsukan log', function () use ($logFile) {
    @unlink($logFile);
    catatLog('WARNING', 'uji', ['alasan' => "x\n{\"level\":\"PALSU\"}"]);
    sama(1, substr_count((string) file_get_contents($logFile), "\n"));
    @unlink($logFile);
});

echo "\n== Unit: penyimpanan ==\n";
uji('file data dibuat otomatis berisi data contoh', function () use ($dataFile) {
    buangDataUji($dataFile);
    sama(3, count(bacaData()['transactions']));
    benar(is_file($dataFile), 'file data tidak dibuat');
});
uji('simpan lalu baca menghasilkan data yang sama', function () use ($dataFile) {
    buangDataUji($dataFile);
    $data = ['budget_bulanan' => 1, 'transactions' => [['id' => 1, 'keterangan' => 'Kopi ☕']]];
    simpanData($data);
    sama($data, bacaData());
});
uji('gagal encode tidak mengosongkan file data', function () use ($dataFile) {
    buangDataUji($dataFile);
    $awal = bacaData();
    try {
        simpanData(['transactions' => [['keterangan' => "\xFF"]]]);
        throw new LogicException('seharusnya gagal');
    } catch (JsonException) {
        sama($awal, bacaData());
    }
});
uji('file data rusak dilaporkan dan tidak ditimpa', function () use ($dataFile) {
    file_put_contents($dataFile, '{rusak');
    try {
        bacaData();
        throw new LogicException('seharusnya gagal');
    } catch (RuntimeException) {
        sama('{rusak', file_get_contents($dataFile));
    }
    buangDataUji($dataFile);
});
uji('kunci mencegah update hilang (4 proses x 25 tulis)', function () use ($dataFile) {
    buangDataUji($dataFile);
    simpanData(['budget_bulanan' => 0, 'transactions' => []]);
    $config = dirname(__DIR__) . '/backend/config.php';
    $kode = "require " . var_export($config, true) . "; for (\$i = 0; \$i < 25; \$i++) { \$k = kunciData(); \$d = bacaData(); \$d['budget_bulanan']++; simpanData(\$d); flock(\$k, LOCK_UN); fclose(\$k); }";
    $null = stripos(PHP_OS, 'WIN') === 0 ? 'NUL' : '/dev/null';
    $proses = [];
    for ($i = 0; $i < 4; $i++) {
        $proses[] = proc_open([PHP_BINARY, '-r', $kode], [['file', $null, 'r'], ['file', $null, 'a'], ['file', $null, 'a']], $p, null, getenv());
    }
    foreach ($proses as $p) {
        sama(0, proc_close($p), 'kode keluar proses:');
    }
    sama(100, bacaData()['budget_bulanan']);
    buangDataUji($dataFile);
});

echo "\n== Integrasi: API ==\n";
buangDataUji($dataFile);
[$server, $base] = mulaiServer(dirname(__DIR__) . '/backend', [
    'KEUANGAN_DATA_FILE' => $dataFile,
    'KEUANGAN_LOG_FILE' => $logFile,
]);
$api = "$base/api.php";
$frontend = ['Origin' => 'http://localhost:8080'];

uji('GET mengembalikan data, kategori, dan ringkasan', function () use ($api) {
    $r = http('GET', $api);
    sama(200, $r['status']);
    sama(3, count($r['json']['data']));
    sama(KATEGORI, $r['json']['kategori']);
    sama(7150000, $r['json']['summary']['saldo_akhir']);
});
uji('POST menambah transaksi dengan id baru', function () use ($api, &$idBaru) {
    $r = kirimJson('POST', $api, transaksiValid());
    sama(200, $r['status']);
    $idBaru = end($r['json']['data'])['id'];
    sama(4, $idBaru);
    sama(875000, $r['json']['summary']['total_pengeluaran']);
});
uji('PUT mengubah transaksi', function () use ($api, &$idBaru) {
    $r = kirimJson('PUT', "$api?id=$idBaru", transaksiValid(['nominal' => 30000]));
    sama(200, $r['status']);
    sama(30000, end($r['json']['data'])['nominal']);
});
uji('DELETE menghapus transaksi', function () use ($api, &$idBaru) {
    $r = http('DELETE', "$api?id=$idBaru");
    sama(200, $r['status']);
    sama(3, count($r['json']['data']));
});
uji('PUT id tidak ada -> 404', fn() => sama(404, kirimJson('PUT', "$api?id=999", transaksiValid())['status']));
uji('DELETE id tidak ada -> 404', fn() => sama(404, http('DELETE', "$api?id=999")['status']));
uji('POST budget menyimpan batas anggaran', function () use ($api) {
    $r = kirimJson('POST', "$api?action=budget", ['budget_bulanan' => 4500000]);
    sama(200, $r['status']);
    sama(4500000, $r['json']['budget_bulanan']);
});
uji('POST nominal tidak valid -> 400 dengan pesan', function () use ($api) {
    $r = kirimJson('POST', $api, transaksiValid(['nominal' => -5]));
    sama(400, $r['status']);
    sama(false, $r['json']['success']);
});
uji('POST bukan JSON -> 415', fn() => sama(415, http('POST', $api, 'a=1', ['Content-Type' => 'application/x-www-form-urlencoded'])['status']));
uji('POST JSON rusak -> 400', fn() => sama(400, http('POST', $api, '{rusak', ['Content-Type' => 'application/json'])['status']));
uji('POST body > 16 KB -> 413', fn() => sama(413, kirimJson('POST', $api, transaksiValid(['keterangan' => str_repeat('a', 20000)]))['status']));
uji('metode PATCH -> 405', fn() => sama(405, http('PATCH', $api)['status']));
uji('Origin frontend diizinkan oleh CORS', function () use ($api, $frontend) {
    $r = http('GET', $api, null, $frontend);
    sama(200, $r['status']);
    sama('http://localhost:8080', $r['headers']['access-control-allow-origin'] ?? null);
});
uji('preflight Origin frontend -> 204', function () use ($api, $frontend) {
    $r = http('OPTIONS', "$api?id=1", null, $frontend);
    sama(204, $r['status']);
    benar(str_contains($r['headers']['access-control-allow-methods'] ?? '', 'DELETE'), 'DELETE tidak diizinkan');
});
uji('Origin asing -> 403', fn() => sama(403, http('GET', $api, null, ['Origin' => 'https://evil.example'])['status']));
uji('halaman status backend tampil', function () use ($base) {
    $r = http('GET', "$base/index.php");
    sama(200, $r['status']);
    benar(str_contains($r['body'], 'Saldo Akhir'), 'isi halaman tidak sesuai');
});
uji('error 500 generik dengan kode referensi yang ada di log', function () use ($api, $dataFile, $logFile) {
    file_put_contents($dataFile, '{rusak');
    $r = http('GET', $api);
    sama(500, $r['status']);
    sama('Terjadi kesalahan pada server.', $r['json']['message']);
    benar(str_contains((string) file_get_contents($logFile), $r['json']['ref']), 'ref tidak ada di log');
    @unlink($dataFile);
});

proc_terminate($server);
hapusFolder($tmp);

$total = $lulus + $gagal;
echo "\n" . ($gagal === 0 ? "ALL PASSED: $lulus/$total test lulus" : "FAILED: $gagal dari $total test gagal") . "\n";

exit($gagal === 0 ? 0 : 1);
