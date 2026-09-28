💰 Sistem Informasi Pencatat Keuangan Harian

Sistem Informasi Pencatat Keuangan Harian Berbasis Web

📌 Deskripsi
Sistem Informasi Pencatat Keuangan Harian adalah aplikasi berbasis web yang dibuat untuk membantu pengguna mencatat dan memantau arus kas harian secara sederhana dan terorganisir. Aplikasi ini memungkinkan pengguna untuk mencatat pemasukan, pengeluaran, serta saldo yang tersisa berdasarkan transaksi yang telah dilakukan.

Pengguna dapat menginput saldo awal, menambahkan transaksi, dan melihat ringkasan keuangan dalam satu tampilan. Data yang tercatat akan membantu pengguna memahami kondisi keuangan mereka dalam periode tertentu.

🎯 Tujuan
Sistem ini dibuat dengan tujuan:

Mempermudah pengguna dalam mencatat pemasukan dan pengeluaran harian.
Menampilkan total pemasukan dan total pengeluaran secara otomatis.
Menghitung saldo akhir berdasarkan saldo awal dan transaksi yang telah ditambahkan.
Menyediakan riwayat transaksi agar pengguna dapat memantau kegiatan keuangannya.
Membantu proses pencatatan keuangan menjadi lebih terstruktur dan mudah dipahami.
💡 Manfaat
👤 Bagi Pengguna
Dapat mencatat pemasukan dan pengeluaran dengan lebih cepat.
Tidak perlu menghitung manual karena sistem menghitung saldo secara otomatis.
Dapat melihat ringkasan keuangan secara langsung.
Dapat memantau riwayat transaksi dengan lebih jelas.
👨‍💼 Bagi Admin / Pengelola
Mempermudah pengelolaan data transaksi keuangan.
Mengurangi risiko kesalahan pencatatan manual.
Mempermudah analisis pemasukan dan pengeluaran.
Membantu dalam pengambilan keputusan keuangan secara lebih cepat.
👥 Pengguna Sistem
Sistem memiliki 2 jenis pengguna:

1. Pengguna
Pengguna dapat:

Mengatur saldo awal.
Menambahkan transaksi pemasukan dan pengeluaran.
Melihat ringkasan saldo.
Melihat riwayat transaksi.
2. Admin
Admin dapat:

Mengelola data transaksi.
Melihat data pengguna dan semua catatan keuangan.
Mengawasi saldo serta catatan pemasukan dan pengeluaran.
Mengelola sistem secara keseluruhan.
⚙️ Fitur Utama
🔐 1. Pengaturan Saldo Awal
Pengguna dapat memasukkan nominal saldo awal yang menjadi dasar perhitungan saldo akhir.

💵 2. Pencatatan Transaksi
Pengguna dapat menambahkan transaksi dengan data berikut:

Tanggal transaksi
Jenis transaksi (pemasukan/pengeluaran)
Keterangan
Nominal
📊 3. Ringkasan Keuangan
Sistem menampilkan:

Saldo awal
Total pemasukan
Total pengeluaran
Saldo akhir
Perhitungan saldo dilakukan dengan rumus:

Saldo akhir = Saldo awal + Total pemasukan - Total pengeluaran
🧾 4. Riwayat Transaksi
Transaksi yang telah ditambahkan akan ditampilkan dalam tabel agar pengguna dapat memantau aktivitas keuangan.

🔄 Alur Sistem
Pengguna
    ↓
Login / Akses Aplikasi
    ↓
Input Saldo Awal
    ↓
Tambah Transaksi
    ↓
Pilih Jenis Transaksi
    ↓
Input Tanggal, Keterangan, Nominal
    ↓
Sistem Menghitung Saldo
    ↓
Lihat Ringkasan & Riwayat Transaksi
🗄️ Database
Database dibuat sederhana dengan beberapa tabel utama.

users
id
nama
email
password
role
transaksi
id
user_id
tanggal
jenis_transaksi
keterangan
nominal
Relasi
users
  │
  │ 1
  │
  │ *
  ▼
transaksi
🖥️ Halaman Sistem
👤 Pengguna
Login
Dashboard
Form Saldo Awal
Form Transaksi
Ringkasan Keuangan
Riwayat Transaksi
👨‍💼 Admin
Login Admin
Dashboard Admin
Manajemen Transaksi
Lihat Semua Data Keuangan
Kelola Data Pengguna
🛠️ Teknologi
Frontend
HTML5
CSS
JavaScript
Backend
PHP
API REST
Database
MySQL
Tools
Git
GitHub
Visual Studio Code
Postman
📋 Metode Pengembangan
Project dikembangkan menggunakan konsep Rekayasa Perangkat Lunak (RPL) melalui beberapa tahapan:

Analisis kebutuhan
Perancangan sistem
Perancangan database
Perancangan UI
Implementasi
Pengujian
Evaluasi
🧪 Pengujian
Pengujian dilakukan untuk memastikan fitur sistem berjalan sesuai dengan kebutuhan.

Fitur yang diuji meliputi:

Input saldo awal
Penambahan transaksi pemasukan
Penambahan transaksi pengeluaran
Perhitungan saldo otomatis
Tampilan ringkasan keuangan
Tampilan riwayat transaksi
💻 Persiapan dan Cara Menjalankan
Pastikan PHP sudah terpasang di komputer Anda. Jalankan perintah berikut di terminal:

1. Menjalankan backend
cd backend
php -S localhost:8000
2. Menjalankan frontend
cd frontend
php -S localhost:8080
Kemudian buka alamat berikut di browser:

http://localhost:8080
Untuk endpoint API backend, dapat diakses melalui:

http://localhost:8000/api.php
🚀 Status Project
Status: 🚧 Dalam Pengembangan

🚧 Status Project
Status: Dalam pengembangan
