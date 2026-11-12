<?php
// Entrypoint Vercel (runtime vercel-php hanya menjalankan file di folder api/).
// Logika tetap di backend/api.php agar menjalankan secara lokal tidak berubah.
require dirname(__DIR__) . '/api.php';
