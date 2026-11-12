// Build frontend untuk Vercel: salin file ke dist/ lalu suntikkan API_URL
// ke assets/config.js dan ke connect-src CSP di index.html.
import { cpSync, readFileSync, rmSync, writeFileSync } from 'node:fs';

const apiUrl = process.env.API_URL;
if (!apiUrl) {
    console.error('BUILD GAGAL: environment variable API_URL belum diisi (contoh: https://nama-backend.vercel.app/api.php)');
    process.exit(1);
}
const origin = new URL(apiUrl).origin;

rmSync('dist', { recursive: true, force: true });
cpSync('index.html', 'dist/index.html');
cpSync('assets', 'dist/assets', { recursive: true });

writeFileSync('dist/assets/config.js', `window.APP_CONFIG = { apiUrl: ${JSON.stringify(apiUrl)} };\n`);

const html = readFileSync('dist/index.html', 'utf8');
const hasil = html.replace('connect-src http://localhost:8000', `connect-src ${origin}`);
if (hasil === html) {
    console.error('BUILD GAGAL: connect-src http://localhost:8000 tidak ditemukan di index.html');
    process.exit(1);
}
writeFileSync('dist/index.html', hasil);

console.log(`BUILD BERHASIL: API_URL=${apiUrl}, connect-src=${origin}`);
