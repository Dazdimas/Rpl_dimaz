// Konfirmasi sebelum aksi berisiko (hapus/reset) dan indikator loading saat formulir dikirim.
document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        var pesan = form.dataset.confirm;
        if (pesan && !window.confirm(pesan)) {
            event.preventDefault();
            return;
        }
        var tombol = form.querySelector('button[type="submit"]');
        if (tombol) {
            tombol.dataset.label = tombol.textContent;
            tombol.textContent = 'Memproses...';
            tombol.disabled = true;
        }
    });
});

// Pulihkan tombol jika halaman dibuka kembali lewat tombol "Back" browser.
window.addEventListener('pageshow', function () {
    document.querySelectorAll('button[data-label]').forEach(function (tombol) {
        tombol.textContent = tombol.dataset.label;
        tombol.disabled = false;
    });
});
