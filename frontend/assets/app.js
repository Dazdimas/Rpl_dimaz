const apiUrl = 'http://localhost:8000/api.php';

async function fetchTransactions() {
    try {
        const response = await fetch(apiUrl);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'Gagal memuat data');
        }

        renderSummary(result.summary);
        renderTable(result.data);
    } catch (error) {
        console.error(error);
        alert('Gagal mengambil data dari backend. Pastikan server PHP berjalan.');
    }
}

function renderSummary(summary) {
    document.getElementById('saldoAwal').textContent = formatRupiah(summary.saldo_awal);
    document.getElementById('totalPemasukan').textContent = formatRupiah(summary.total_pemasukan);
    document.getElementById('totalPengeluaran').textContent = formatRupiah(summary.total_pengeluaran);
    document.getElementById('saldoAkhir').textContent = formatRupiah(summary.saldo_akhir);
}

function renderTable(data) {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';

    data.forEach(item => {
        const tr = document.createElement('tr');
        const jenisClass = item.jenis === 'pemasukan' ? 'pemasukan-text' : 'pengeluaran-text';

        tr.innerHTML = `
            <td>${item.tanggal}</td>
            <td class="${jenisClass}">${item.jenis}</td>
            <td>${item.keterangan}</td>
            <td>${formatRupiah(item.nominal)}</td>
        `;

        tbody.appendChild(tr);
    });
}

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}

async function submitTransaction(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());

    try {
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'Gagal menyimpan transaksi');
        }

        form.reset();
        fetchTransactions();
        alert('Transaksi berhasil disimpan');
    } catch (error) {
        console.error(error);
        alert(error.message);
    }
}

document.getElementById('formTransaksi').addEventListener('submit', submitTransaction);

document.getElementById('tanggal').valueAsDate = new Date();
fetchTransactions();
