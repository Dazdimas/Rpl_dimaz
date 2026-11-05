<?php
// Helper bersama untuk test dan pemindai keamanan. Tanpa dependensi selain PHP.

function portKosong(): int
{
    $socket = stream_socket_server('tcp://127.0.0.1:0');
    $port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
    fclose($socket);

    return $port;
}

/** Menjalankan php -S dan menunggu port terbuka. Kembalikan [proses, baseUrl]. */
function mulaiServer(string $docroot, array $env = []): array
{
    $port = portKosong();
    $null = stripos(PHP_OS, 'WIN') === 0 ? 'NUL' : '/dev/null';
    $proses = proc_open(
        [PHP_BINARY, '-S', "127.0.0.1:$port", '-t', $docroot],
        [['file', $null, 'r'], ['file', $null, 'a'], ['file', $null, 'a']],
        $pipes,
        null,
        $env + getenv()
    );
    // Server tetap dimatikan walau test berhenti karena error.
    register_shutdown_function(static fn() => proc_terminate($proses));

    for ($i = 0; $i < 100; $i++) {
        $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
        if ($socket) {
            fclose($socket);

            return [$proses, "http://127.0.0.1:$port"];
        }
        usleep(100000);
    }

    proc_terminate($proses);
    throw new RuntimeException("Server tidak bisa dijalankan pada port $port");
}

/** Mengirim request HTTP. Kembalikan ['status', 'headers' (huruf kecil), 'body', 'json']. */
function http(string $method, string $url, ?string $body = null, array $headers = []): array
{
    $baris = [];
    foreach ($headers as $nama => $nilai) {
        $baris[] = "$nama: $nilai";
    }

    $konteks = stream_context_create(['http' => [
        'method' => $method,
        'header' => implode("\r\n", $baris),
        'content' => $body ?? '',
        'ignore_errors' => true,
        'timeout' => 15,
    ]]);

    $isi = @file_get_contents($url, false, $konteks);
    $mentah = $http_response_header ?? [];
    $status = isset($mentah[0]) ? (int) explode(' ', $mentah[0])[1] : 0;

    $hasil = [];
    foreach (array_slice($mentah, 1) as $baris) {
        [$nama, $nilai] = array_pad(explode(':', $baris, 2), 2, '');
        $hasil[strtolower(trim($nama))] = trim($nilai);
    }

    return [
        'status' => $status,
        'headers' => $hasil,
        'body' => (string) $isi,
        'json' => json_decode((string) $isi, true),
    ];
}

function kirimJson(string $method, string $url, array $data, array $headers = []): array
{
    return http($method, $url, json_encode($data), $headers + ['Content-Type' => 'application/json']);
}

function transaksiValid(array $ubah = []): array
{
    return $ubah + [
        'tanggal' => '2026-10-01',
        'jenis' => 'pengeluaran',
        'kategori' => 'Makanan',
        'keterangan' => 'Makan siang',
        'nominal' => 25000,
    ];
}

function folderSementara(): string
{
    $folder = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'keuangan_test_' . bin2hex(random_bytes(4));
    mkdir($folder, 0700, true);

    return $folder;
}

function hapusFolder(string $folder): void
{
    foreach (glob($folder . DIRECTORY_SEPARATOR . '*') ?: [] as $item) {
        is_dir($item) ? hapusFolder($item) : @unlink($item);
    }
    @rmdir($folder);
}
