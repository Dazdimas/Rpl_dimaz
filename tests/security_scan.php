<?php
// Pemindai keamanan dinamis (DAST sederhana) + pemeriksaan statis.
//
//   php tests/security_scan.php                 -> memindai kode di repo ini (data sementara)
//   php tests/security_scan.php --legacy <dir>  -> memindai SALINAN versi lama yang datanya
//                                                  di <dir>/backend/data.json. Data di <dir>
//                                                  akan ditimpa, jadi jangan arahkan ke folder asli.
//
// Status: PASS = aman, FAIL = rentan, OPEN = risiko yang sudah diketahui dan belum diperbaiki.
// Kode keluar 1 jika ada FAIL.

require __DIR__ . '/lib.php';

$legacy = ($argv[1] ?? '') === '--legacy';
$root = $legacy ? rtrim((string) ($argv[2] ?? ''), '/\\') : dirname(__DIR__);

if (!is_file("$root/backend/api.php")) {
    fwrite(STDERR, "backend/api.php tidak ditemukan di: $root\n");
    exit(2);
}

$tmp = folderSementara();
$dataFile = $legacy ? "$root/backend/data.json" : "$tmp/data.json";
$logFile = "$tmp/app.log";

[$server, $base] = mulaiServer("$root/backend", [
    'KEUANGAN_DATA_FILE' => $dataFile,
    'KEUANGAN_LOG_FILE' => $logFile,
]);
$api = "$base/api.php";
$jahat = ['Origin' => 'https://evil.example'];

function resetData(string $dataFile): void
{
    @unlink($dataFile);
}

function jumlahTransaksi(string $api): int
{
    return count(http('GET', $api)['json']['data'] ?? []);
}

function bocorPath(string $body): bool
{
    return (bool) preg_match('~[A-Za-z]:[\\\\/]|/home/|/var/www|<b>(Warning|Fatal error|Notice)|Stack trace~i', $body);
}

$hasil = [];
function periksa(string $id, string $judul, string $status, string $catatan): void
{
    global $hasil;
    $hasil[] = [$id, $judul, $status, $catatan];
}

function tegas(bool $aman, string $catatan): array
{
    return [$aman ? 'PASS' : 'FAIL', $catatan];
}

// SEC-01 CORS baca lintas origin
resetData($dataFile);
$r = http('GET', $api, null, $jahat);
$acao = $r['headers']['access-control-allow-origin'] ?? '(tidak ada)';
periksa('SEC-01', 'Situs asing tidak boleh membaca data (CORS)', ...tegas(
    !in_array($acao, ['*', 'https://evil.example'], true),
    "HTTP {$r['status']}, Access-Control-Allow-Origin: $acao"
));

// SEC-02 Preflight DELETE dari origin asing
$r = http('OPTIONS', "$api?id=1", null, $jahat + [
    'Access-Control-Request-Method' => 'DELETE',
    'Access-Control-Request-Headers' => 'content-type',
]);
$acao = $r['headers']['access-control-allow-origin'] ?? '(tidak ada)';
periksa('SEC-02', 'Preflight DELETE dari origin asing ditolak', ...tegas(
    !in_array($acao, ['*', 'https://evil.example'], true),
    "HTTP {$r['status']}, Access-Control-Allow-Origin: $acao"
));

// SEC-03 CSRF: text/plain tidak memicu preflight di browser
resetData($dataFile);
$sebelum = jumlahTransaksi($api);
$r = http('POST', $api, json_encode(transaksiValid()), $jahat + ['Content-Type' => 'text/plain']);
$sesudah = jumlahTransaksi($api);
periksa('SEC-03', 'CSRF: POST text/plain dari origin asing ditolak', ...tegas(
    $sesudah === $sebelum,
    "HTTP {$r['status']}, jumlah transaksi $sebelum -> $sesudah"
));

// SEC-04 Hapus data dengan Origin asing
resetData($dataFile);
$sebelum = jumlahTransaksi($api);
$r = http('DELETE', "$api?id=1", null, $jahat);
$sesudah = jumlahTransaksi($api);
periksa('SEC-04', 'DELETE dari origin asing ditolak server', ...tegas(
    $sesudah === $sebelum,
    "HTTP {$r['status']}, jumlah transaksi $sebelum -> $sesudah"
));

// SEC-05 File data bisa diunduh langsung
resetData($dataFile);
http('GET', $api);
$r = http('GET', "$base/data.json");
periksa('SEC-05', 'File data tidak bisa diunduh lewat URL', ...tegas(
    $r['status'] !== 200,
    "GET /data.json -> HTTP {$r['status']}"
        . ($r['status'] === 200 ? ' (file lama masih di backend/; jalankan aplikasi sekali agar pindah ke storage/)' : '')
));

// SEC-06 File konfigurasi/versi
$rincian = [];
$aman = true;
foreach (['.env', '.git/config', '../.git/config', '../storage/data.json'] as $path) {
    $r = http('GET', "$base/$path");
    $rincian[] = "/$path={$r['status']}";
    $aman = $aman && $r['status'] !== 200;
}
periksa('SEC-06', 'File sensitif & path traversal tidak terbuka', ...tegas($aman, implode(', ', $rincian)));

// SEC-07 Tanggal mustahil
resetData($dataFile);
$r = kirimJson('POST', $api, transaksiValid(['tanggal' => '2026-99-99']));
periksa('SEC-07', 'Tanggal mustahil (2026-99-99) ditolak', ...tegas(
    $r['status'] === 400,
    "HTTP {$r['status']}"
));

// SEC-08 Integer overflow membuat aplikasi 500 permanen
resetData($dataFile);
kirimJson('POST', $api, transaksiValid(['jenis' => 'pemasukan', 'nominal' => PHP_INT_MAX]));
kirimJson('POST', $api, transaksiValid(['jenis' => 'pemasukan', 'nominal' => PHP_INT_MAX]));
$r = http('GET', $api);
// Status saja tidak cukup: dengan display_errors aktif, fatal error PHP tetap HTTP 200.
$jsonValid = ($r['json']['success'] ?? false) === true;
periksa('SEC-08', 'Overflow nominal tidak melumpuhkan aplikasi', ...tegas(
    $r['status'] === 200 && $jsonValid,
    "GET setelah 2x nominal PHP_INT_MAX -> HTTP {$r['status']}, JSON valid: " . ($jsonValid ? 'ya' : 'TIDAK')
));

// SEC-09 Byte UTF-8 rusak menghapus seluruh data
resetData($dataFile);
$sebelum = jumlahTransaksi($api);
http('POST', $api, "tanggal=2026-10-01&jenis=pemasukan&kategori=Gaji&nominal=1&keterangan=%FF", [
    'Content-Type' => 'application/x-www-form-urlencoded',
]);
clearstatcache();
$ukuran = is_file($dataFile) ? filesize($dataFile) : 0;
periksa('SEC-09', 'Input UTF-8 rusak tidak menghapus file data', ...tegas(
    $ukuran > 0,
    "transaksi awal: $sebelum, ukuran file data sesudah request: $ukuran byte"
));

// SEC-10 Kebocoran path lewat pesan error
resetData($dataFile);
$r1 = kirimJson('POST', $api, transaksiValid(['keterangan' => ['x']]));
kirimJson('POST', $api, transaksiValid(['jenis' => 'pemasukan', 'nominal' => PHP_INT_MAX]));
kirimJson('POST', $api, transaksiValid(['jenis' => 'pemasukan', 'nominal' => PHP_INT_MAX]));
$r2 = http('GET', $api);
periksa('SEC-10', 'Pesan error tidak membocorkan path/stack trace', ...tegas(
    !bocorPath($r1['body']) && !bocorPath($r2['body']),
    'array di keterangan: ' . (bocorPath($r1['body']) ? 'BOCOR' : 'bersih')
        . ', error overflow: ' . (bocorPath($r2['body']) ? 'BOCOR' : 'bersih')
));

// SEC-11 Nominal tidak valid
resetData($dataFile);
$lolos = [];
foreach (['12abc', '1e30', [5], true, 1.5, -1, 0, PHP_INT_MAX] as $nilai) {
    $r = kirimJson('POST', $api, transaksiValid(['nominal' => $nilai]));
    if ($r['status'] === 200) {
        $lolos[] = json_encode($nilai);
    }
}
periksa('SEC-11', 'Nominal tidak valid/berlebihan ditolak', ...tegas(
    $lolos === [],
    $lolos === [] ? '8 dari 8 nilai ditolak' : 'lolos: ' . implode(', ', $lolos)
));

// SEC-12 Keterangan sangat panjang
resetData($dataFile);
$r = kirimJson('POST', $api, transaksiValid(['keterangan' => str_repeat('A', 1000000)]));
periksa('SEC-12', 'Keterangan 1 MB ditolak', ...tegas(
    in_array($r['status'], [400, 413], true),
    "HTTP {$r['status']}"
));

// SEC-13 Header keamanan API
$r = http('GET', $api);
$wajib = ['x-content-type-options', 'content-security-policy', 'referrer-policy', 'cache-control'];
$kurang = array_diff($wajib, array_keys($r['headers']));
periksa('SEC-13', 'Header keamanan pada API', ...tegas(
    $kurang === [],
    $kurang === [] ? 'lengkap' : 'kurang: ' . implode(', ', $kurang)
));

// SEC-14 Kebocoran versi PHP
$r = http('GET', $api);
$powered = $r['headers']['x-powered-by'] ?? null;
periksa('SEC-14', 'Versi PHP tidak diumumkan', ...tegas(
    $powered === null,
    'X-Powered-By: ' . ($powered ?? '(tidak ada)')
));

// SEC-15 File data rusak tidak boleh ditimpa data contoh
file_put_contents($dataFile, '{rusak');
$r = kirimJson('POST', $api, transaksiValid());
$isi = (string) file_get_contents($dataFile);
periksa('SEC-15', 'File data rusak tidak ditimpa diam-diam', ...tegas(
    $isi === '{rusak' && $r['status'] === 500 && !bocorPath($r['body']),
    "HTTP {$r['status']}, file " . ($isi === '{rusak' ? 'utuh' : 'DITIMPA')
));

// SEC-16 Payload XSS/SQLi disimpan sebagai teks biasa, tidak disajikan sebagai HTML
resetData($dataFile);
$payload = "<script>alert(1)</script>' OR 1=1 --";
$r = kirimJson('POST', $api, transaksiValid(['keterangan' => $payload]));
$tersimpan = end($r['json']['data'])['keterangan'] ?? '';
$tipe = $r['headers']['content-type'] ?? '';
periksa('SEC-16', 'Payload XSS/SQLi tetap teks biasa di API', ...tegas(
    $tersimpan === $payload && stripos($tipe, 'application/json') === 0,
    "Content-Type: $tipe; backend tidak memakai SQL"
));

// SEC-17 Frontend: SRI, CSP, escape (statis)
$html = (string) file_get_contents("$root/frontend/index.html");
$js = (string) file_get_contents("$root/frontend/assets/app.js");
$masalah = [];
if (!preg_match('~<script[^>]+cdnjs[^>]+integrity="sha512-~', $html)) {
    $masalah[] = 'CDN tanpa SRI';
}
if (stripos($html, 'Content-Security-Policy') === false) {
    $masalah[] = 'tanpa CSP';
}
if (preg_match('~\$\{item\.(tanggal|jenis|id)\}~', $js)) {
    $masalah[] = 'field tanpa escape di innerHTML';
}
periksa('SEC-17', 'Frontend: SRI, CSP, dan escape output', ...tegas(
    $masalah === [],
    $masalah === [] ? 'lengkap' : implode(', ', $masalah)
));

// SEC-18 Log aman
resetData($dataFile);
@unlink($logFile);
kirimJson('POST', $api, transaksiValid(['keterangan' => 'password=RahasiaUji123', 'nominal' => 7654321]));
kirimJson('POST', $api, transaksiValid(['nominal' => -1, 'keterangan' => 'token=RahasiaUji123']));
$log = is_file($logFile) ? (string) file_get_contents($logFile) : null;
if ($log === null || $log === '') {
    periksa('SEC-18', 'Log aktivitas ada dan bebas data sensitif', 'FAIL', 'aplikasi tidak menulis log');
} else {
    periksa('SEC-18', 'Log aktivitas ada dan bebas data sensitif', ...tegas(
        !str_contains($log, 'RahasiaUji123') && !str_contains($log, '7654321'),
        substr_count($log, "\n") . ' baris log, tanpa keterangan/nominal'
    ));
}

// SEC-19 Autentikasi: fitur belum dibangun, dilaporkan terbuka apa adanya
resetData($dataFile);
$r = http('GET', $api);
periksa('SEC-19', 'Autentikasi & otorisasi', $r['status'] === 200 ? 'OPEN' : 'PASS',
    "GET tanpa kredensial -> HTTP {$r['status']}; login belum diimplementasikan");

proc_terminate($server);
hapusFolder($tmp);

$hitung = ['PASS' => 0, 'FAIL' => 0, 'OPEN' => 0];
printf("Target : %s\nPHP    : %s\n\n", $legacy ? 'versi lama (--legacy)' : 'working tree', PHP_VERSION);
foreach ($hasil as [$id, $judul, $status, $catatan]) {
    $hitung[$status]++;
    printf("[%s] %s  %s\n       %s\n", $status, $id, $judul, $catatan);
}
printf("\nRingkasan: %d PASS, %d FAIL, %d OPEN (total %d)\n", $hitung['PASS'], $hitung['FAIL'], $hitung['OPEN'], count($hasil));

exit($hitung['FAIL'] > 0 ? 1 : 0);
