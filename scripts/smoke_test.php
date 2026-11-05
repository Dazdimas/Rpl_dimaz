<?php
// Memeriksa demo yang sedang berjalan. Hanya membaca, tidak mengubah data.
//   php scripts/smoke_test.php [url-backend] [url-frontend]

$backend = rtrim($argv[1] ?? 'http://localhost:8000', '/');
$frontend = rtrim($argv[2] ?? 'http://localhost:8080', '/');
$konteks = stream_context_create(['http' => [
    'ignore_errors' => true,
    'timeout' => 5,
    'header' => 'Origin: http://localhost:8080',
]]);
$gagal = 0;

$periksa = function (string $url, string $harus) use ($konteks, &$gagal): void {
    $isi = @file_get_contents($url, false, $konteks);
    $status = isset($http_response_header[0]) ? (int) explode(' ', $http_response_header[0])[1] : 0;
    $ok = $status === 200 && str_contains((string) $isi, $harus);
    $gagal += $ok ? 0 : 1;
    printf("[%s] GET %s -> HTTP %d\n", $ok ? 'PASS' : 'FAIL', $url, $status);
};

$periksa("$backend/api.php", '"success":true');
$periksa("$backend/index.php", 'Saldo Akhir');
$periksa("$frontend/", 'Pencatat Keuangan');
$periksa("$frontend/assets/app.js", 'fetchTransactions');
$periksa("$frontend/assets/style.css", '{');

echo $gagal === 0 ? "SMOKE TEST PASS\n" : "SMOKE TEST GAGAL: $gagal pemeriksaan\n";
exit($gagal === 0 ? 0 : 1);
