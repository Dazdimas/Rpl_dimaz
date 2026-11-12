<?php
// Build paket demo: lint -> test -> pindai keamanan -> rakit dist/keuangan-demo.
//   php scripts/build.php
// PHP dan JavaScript di project ini tidak dikompilasi, jadi "build" berarti memvalidasi
// kode dan merakit folder berisi file runtime saja (tanpa data pengguna, test, atau video).

$root = dirname(__DIR__);
$tujuan = "$root/dist/keuangan-demo";
$fileRuntime = [
    'backend/api.php',
    'backend/config.php',
    'backend/index.php',
    'frontend/index.html',
    'frontend/assets/app.js',
    'frontend/assets/config.js',
    'frontend/assets/style.css',
    'storage/.htaccess',
];

function langkah(string $judul, array $perintah): void
{
    echo "==> $judul\n";
    $proses = proc_open($perintah, [STDIN, STDOUT, STDERR], $pipes);
    if (proc_close($proses) !== 0) {
        fwrite(STDERR, "BUILD GAGAL pada langkah: $judul\n");
        exit(1);
    }
}

foreach (['backend/api.php', 'backend/config.php', 'backend/index.php'] as $file) {
    langkah("Lint $file", [PHP_BINARY, '-l', "$root/$file"]);
}
langkah('Unit & integration test', [PHP_BINARY, "$root/tests/run_tests.php"]);
langkah('Pindai keamanan', [PHP_BINARY, "$root/tests/security_scan.php"]);

echo "==> Merakit dist/keuangan-demo\n";
foreach ($fileRuntime as $file) {
    $target = "$tujuan/$file";
    if (!is_dir(dirname($target))) {
        mkdir(dirname($target), 0755, true);
    }
    if (!copy("$root/$file", $target)) {
        fwrite(STDERR, "BUILD GAGAL: tidak bisa menyalin $file\n");
        exit(1);
    }
}

printf("BUILD BERHASIL: %d file di dist/keuangan-demo\n", count($fileRuntime));
