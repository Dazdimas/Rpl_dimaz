<?php
const KATEGORI = ['Makanan', 'Transportasi', 'Tagihan', 'Hiburan', 'Gaji', 'Lain-lain'];
const NOMINAL_MAKS = 999999999999;
const KETERANGAN_MAKS = 255;
const BODY_MAKS_BYTE = 16384;
const ORIGIN_BAWAAN = 'http://localhost:8080,http://127.0.0.1:8080';

// Data disimpan di luar web root (backend/) agar tidak bisa diunduh lewat URL.
// Di Vercel (env VERCEL=1) filesystem read-only kecuali /tmp, dan /tmp tidak permanen.
define('STORAGE_DIR', getenv('VERCEL')
    ? sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'keuangan'
    : dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage');
define('DATA_FILE', getenv('KEUANGAN_DATA_FILE') ?: STORAGE_DIR . DIRECTORY_SEPARATOR . 'data.json');
define('LOG_FILE', getenv('KEUANGAN_LOG_FILE') ?: STORAGE_DIR . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app.log');

class ValidasiGagal extends RuntimeException
{
}

function originDiizinkan(): array
{
    $daftar = getenv('KEUANGAN_ALLOWED_ORIGINS') ?: ORIGIN_BAWAAN;

    return array_values(array_filter(array_map('trim', explode(',', $daftar))));
}

function pasangHeaderKeamanan(string $csp): void
{
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store');
    header("Content-Security-Policy: $csp");
}

/** Error tidak pernah dikirim ke klien; rinciannya masuk log dan klien menerima kode referensi. */
function pasangPenangananError(callable $kirimError): void
{
    ini_set('display_errors', '0');
    ini_set('log_errors', '0');

    set_error_handler(static function (int $level, string $pesan, string $file, int $baris): bool {
        if (!(error_reporting() & $level)) {
            return false;
        }

        throw new ErrorException($pesan, 0, $level, $file, $baris);
    });

    set_exception_handler(static function (Throwable $e) use ($kirimError): void {
        $ref = bin2hex(random_bytes(4));
        catatLog('ERROR', 'error.tidak_tertangani', [
            'ref' => $ref,
            'jenis' => get_class($e),
            'pesan' => $e->getMessage(),
            'lokasi' => basename($e->getFile()) . ':' . $e->getLine(),
        ]);
        $kirimError($ref);
    });
}

/** Membersihkan konteks log: rahasia dihapus, data keuangan disamarkan, path & email dibuang. */
function sanitasiLog(array $konteks): array
{
    $hasil = [];

    foreach ($konteks as $kunci => $nilai) {
        if (preg_match('/pass|pwd|secret|token|api[-_]?key|authorization|cookie|session|credential/i', (string) $kunci)) {
            $hasil[$kunci] = '[REDACTED]';
        } elseif (preg_match('/nominal|keterangan|saldo|budget|pemasukan|pengeluaran/i', (string) $kunci)) {
            $hasil[$kunci] = '[MASKED]';
        } elseif (is_array($nilai)) {
            $hasil[$kunci] = sanitasiLog($nilai);
        } elseif (is_string($nilai)) {
            $hasil[$kunci] = sanitasiTeksLog($nilai);
        } else {
            $hasil[$kunci] = $nilai;
        }
    }

    return $hasil;
}

function sanitasiTeksLog(string $teks): string
{
    $teks = str_replace([dirname(__DIR__), str_replace('\\', '/', dirname(__DIR__))], '<app>', $teks);
    $teks = preg_replace('~[A-Za-z]:[\\\\/][^\s"\']+|(?:/home|/Users|/var/www)/[^\s"\']+~', '<path>', $teks) ?? '';
    $teks = preg_replace('/[\w.+-]+@[\w-]+\.[\w.-]+/', '[EMAIL]', $teks) ?? '';
    $teks = preg_replace('/\b(bearer|password|passwd|pwd|token|secret|api[-_]?key)\b\s*[=:]?\s*\S+/i', '$1=[REDACTED]', $teks) ?? '';

    return mb_substr(mb_scrub($teks, 'UTF-8'), 0, 200, 'UTF-8');
}

/** Satu event = satu baris JSON, sehingga input berisi baris baru tidak bisa memalsukan log. */
function catatLog(string $level, string $event, array $konteks = []): void
{
    $folder = dirname(LOG_FILE);
    if (!is_dir($folder)) {
        @mkdir($folder, 0750, true);
    }

    $baris = json_encode([
        'waktu' => date('c'),
        'level' => $level,
        'event' => $event,
        'metode' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
        'path' => parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '',
    ] + sanitasiLog($konteks), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    // Kegagalan menulis log tidak boleh menggagalkan permintaan pengguna.
    @file_put_contents(LOG_FILE, $baris . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function dataAwal(): array
{
    return [
        'budget_bulanan' => 3000000,
        'transactions' => [
            [
                'id' => 1,
                'tanggal' => '2026-09-28',
                'jenis' => 'pemasukan',
                'kategori' => 'Gaji',
                'keterangan' => 'Gaji bulan ini',
                'nominal' => 2500000,
            ],
            [
                'id' => 2,
                'tanggal' => '2026-09-28',
                'jenis' => 'pengeluaran',
                'kategori' => 'Tagihan',
                'keterangan' => 'Belanja kebutuhan rumah',
                'nominal' => 850000,
            ],
            [
                'id' => 3,
                'tanggal' => '2026-09-27',
                'jenis' => 'pemasukan',
                'kategori' => 'Lain-lain',
                'keterangan' => 'Pendapatan tambahan',
                'nominal' => 500000,
            ],
        ],
    ];
}

function siapkanFolderData(): void
{
    $folder = dirname(DATA_FILE);

    if (!is_dir($folder) && !mkdir($folder, 0750, true) && !is_dir($folder)) {
        throw new RuntimeException('Tidak bisa membuat folder data.');
    }
}

/** Mengunci data selama satu request agar baca-ubah-tulis tidak saling menimpa. */
function kunciData()
{
    siapkanFolderData();
    $kunci = fopen(DATA_FILE . '.lock', 'c');

    if ($kunci === false || !flock($kunci, LOCK_EX)) {
        throw new RuntimeException('Tidak bisa mengunci file data.');
    }

    return $kunci;
}

/** Memindahkan data lama dari web root ke storage/ sekali saja. */
function pindahkanDataLama(): void
{
    $lama = __DIR__ . DIRECTORY_SEPARATOR . 'data.json';

    if (getenv('KEUANGAN_DATA_FILE') === false && is_file($lama) && !file_exists(DATA_FILE) && rename($lama, DATA_FILE)) {
        catatLog('INFO', 'data.dipindahkan', ['ke' => 'storage/data.json']);
    }
}

function bacaData(): array
{
    siapkanFolderData();
    pindahkanDataLama();

    if (!file_exists(DATA_FILE)) {
        simpanData(dataAwal());
    }

    $data = json_decode((string) file_get_contents(DATA_FILE), true);

    // File rusak dilaporkan sebagai error, bukan diganti data contoh yang lalu menimpa data asli.
    if (!is_array($data) || !is_array($data['transactions'] ?? null)) {
        throw new RuntimeException('File data rusak atau tidak bisa dibaca.');
    }

    return $data;
}

/** Tulis ke file sementara lalu rename, sehingga kegagalan tidak meninggalkan file data kosong. */
function simpanData(array $data): void
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $sementara = tempnam(dirname(DATA_FILE), 'data');

    if ($sementara === false || file_put_contents($sementara, $json) !== strlen($json) || !rename($sementara, DATA_FILE)) {
        if ($sementara !== false && file_exists($sementara)) {
            unlink($sementara);
        }

        throw new RuntimeException('Gagal menyimpan file data.');
    }
}

/** Bilangan bulat dalam rentang; menolak "12abc", "1e30", 1.5, true, dan array. */
function validasiBilangan(mixed $nilai, int $min, int $maks): ?int
{
    if (!is_int($nilai) && !is_string($nilai)) {
        return null;
    }

    $hasil = filter_var($nilai, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $maks]]);

    return $hasil === false ? null : $hasil;
}

function validasiTransaksi(array $input): array
{
    $tanggal = $input['tanggal'] ?? null;
    if (!is_string($tanggal) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})\z/', $tanggal, $bagian)
        || !checkdate((int) $bagian[2], (int) $bagian[3], (int) $bagian[1])) {
        throw new ValidasiGagal('Tanggal tidak valid. Gunakan format YYYY-MM-DD.');
    }

    $jenis = $input['jenis'] ?? null;
    if (!in_array($jenis, ['pemasukan', 'pengeluaran'], true)) {
        throw new ValidasiGagal('Jenis transaksi harus pemasukan atau pengeluaran.');
    }

    $kategori = $input['kategori'] ?? null;
    if (!in_array($kategori, KATEGORI, true)) {
        throw new ValidasiGagal('Kategori tidak dikenal.');
    }

    $keterangan = $input['keterangan'] ?? '';
    if (!is_string($keterangan)) {
        throw new ValidasiGagal('Keterangan harus berupa teks.');
    }

    $keterangan = trim($keterangan);
    // Pola /u sekaligus menolak byte UTF-8 yang rusak.
    if (!preg_match('/^.{0,' . KETERANGAN_MAKS . '}\z/su', $keterangan)) {
        throw new ValidasiGagal('Keterangan maksimal ' . KETERANGAN_MAKS . ' karakter dan harus UTF-8 valid.');
    }

    $nominal = validasiBilangan($input['nominal'] ?? null, 1, NOMINAL_MAKS);
    if ($nominal === null) {
        throw new ValidasiGagal('Nominal harus bilangan bulat antara 1 dan ' . NOMINAL_MAKS . '.');
    }

    return [
        'tanggal' => $tanggal,
        'jenis' => $jenis,
        'kategori' => $kategori,
        'keterangan' => $keterangan !== '' ? $keterangan : 'Transaksi baru',
        'nominal' => $nominal,
    ];
}

function validasiBudget(array $input): int
{
    $budget = validasiBilangan($input['budget_bulanan'] ?? null, 0, NOMINAL_MAKS);
    if ($budget === null) {
        throw new ValidasiGagal('Batas anggaran harus bilangan bulat antara 0 dan ' . NOMINAL_MAKS . '.');
    }

    return $budget;
}

function hitungSaldoAwal(): int
{
    return 5000000;
}

function hitungTotal(string $jenis, array $data): int
{
    $total = 0;

    foreach ($data as $item) {
        if ($item['jenis'] === $jenis) {
            $total += (int) $item['nominal'];
        }
    }

    return $total;
}

function hitungSaldo(array $data): int
{
    $saldoAwal = hitungSaldoAwal();
    $totalPemasukan = hitungTotal('pemasukan', $data);
    $totalPengeluaran = hitungTotal('pengeluaran', $data);

    return $saldoAwal + $totalPemasukan - $totalPengeluaran;
}

function buatRingkasan(array $data): array
{
    return [
        'saldo_awal' => hitungSaldoAwal(),
        'total_pemasukan' => hitungTotal('pemasukan', $data),
        'total_pengeluaran' => hitungTotal('pengeluaran', $data),
        'saldo_akhir' => hitungSaldo($data),
    ];
}
