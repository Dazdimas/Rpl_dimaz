const apiUrl = 'http://localhost:8000/api.php';

const state = {
    transactions: [],
    kategori: [],
    budgetBulanan: 0,
    editId: null,
};

let chart = null;

async function request(url, options = {}) {
    const response = await fetch(url, {
        headers: { 'Content-Type': 'application/json' },
        ...options,
    });
    const result = await response.json();

    if (!result.success) {
        throw new Error(result.message || 'Permintaan ke backend gagal');
    }

    return result;
}

function applyResult(result) {
    state.transactions = result.data;
    state.kategori = result.kategori;
    state.budgetBulanan = result.budget_bulanan;

    renderKategoriOptions();
    renderSummary(result.summary);
    renderFiltered();
    renderBudget();
}

async function fetchTransactions() {
    try {
        applyResult(await request(apiUrl));
    } catch (error) {
        console.error(error);
        alert('Gagal mengambil data dari backend. Pastikan server PHP berjalan.');
    }
}

function renderKategoriOptions() {
    const formSelect = document.getElementById('kategori');
    const filterSelect = document.getElementById('filterKategori');

    if (formSelect.options.length > 0) {
        return;
    }

    state.kategori.forEach(nama => {
        formSelect.add(new Option(nama, nama));
        filterSelect.add(new Option(nama, nama));
    });
}

function renderSummary(summary) {
    document.getElementById('saldoAwal').textContent = formatRupiah(summary.saldo_awal);
    document.getElementById('totalPemasukan').textContent = formatRupiah(summary.total_pemasukan);
    document.getElementById('totalPengeluaran').textContent = formatRupiah(summary.total_pengeluaran);
    document.getElementById('saldoAkhir').textContent = formatRupiah(summary.saldo_akhir);
}

function getFilteredTransactions() {
    const dari = document.getElementById('filterDari').value;
    const sampai = document.getElementById('filterSampai').value;
    const kategori = document.getElementById('filterKategori').value;

    return state.transactions
        .filter(item => !dari || item.tanggal >= dari)
        .filter(item => !sampai || item.tanggal <= sampai)
        .filter(item => !kategori || item.kategori === kategori)
        .sort((a, b) => b.tanggal.localeCompare(a.tanggal) || b.id - a.id);
}

function renderFiltered() {
    const data = getFilteredTransactions();
    renderTable(data);
    renderChart(data);
}

function renderTable(data) {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="muted">Tidak ada transaksi yang cocok.</td></tr>';
        return;
    }

    data.forEach(item => {
        const tr = document.createElement('tr');
        const jenisClass = item.jenis === 'pemasukan' ? 'pemasukan-text' : 'pengeluaran-text';

        tr.innerHTML = `
            <td>${item.tanggal}</td>
            <td class="${jenisClass}">${item.jenis}</td>
            <td><span class="badge">${escapeHtml(item.kategori || 'Lain-lain')}</span></td>
            <td>${escapeHtml(item.keterangan)}</td>
            <td>${formatRupiah(item.nominal)}</td>
            <td class="aksi">
                <button type="button" class="btn-small btn-edit" data-id="${item.id}">Edit</button>
                <button type="button" class="btn-small btn-hapus" data-id="${item.id}">Hapus</button>
            </td>
        `;

        tbody.appendChild(tr);
    });
}

function renderChart(data) {
    const totals = {};

    data
        .filter(item => item.jenis === 'pengeluaran')
        .forEach(item => {
            const kategori = item.kategori || 'Lain-lain';
            totals[kategori] = (totals[kategori] || 0) + Number(item.nominal);
        });

    const labels = Object.keys(totals);
    const isEmpty = labels.length === 0;
    document.getElementById('chartKosong').hidden = !isEmpty;
    document.getElementById('chartPengeluaran').hidden = isEmpty;

    if (typeof Chart === 'undefined') {
        return;
    }

    const colors = ['#2563eb', '#dc2626', '#f59e0b', '#16a34a', '#9333ea', '#6b7280'];
    const dataset = {
        data: Object.values(totals),
        backgroundColor: labels.map(nama => colors[state.kategori.indexOf(nama) % colors.length] || colors[5]),
    };

    if (chart) {
        chart.data.labels = labels;
        chart.data.datasets[0] = dataset;
        chart.update();
        return;
    }

    chart = new Chart(document.getElementById('chartPengeluaran'), {
        type: 'pie',
        data: { labels, datasets: [dataset] },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: context => `${context.label}: ${formatRupiah(context.parsed)}`,
                    },
                },
            },
        },
    });
}

function renderBudget() {
    const bulanInput = document.getElementById('bulanBudget');
    const budgetInput = document.getElementById('budgetBulanan');

    if (!bulanInput.value) {
        bulanInput.value = new Date().toISOString().slice(0, 7);
    }
    if (document.activeElement !== budgetInput) {
        budgetInput.value = state.budgetBulanan;
    }

    const bulan = bulanInput.value;
    const terpakai = state.transactions
        .filter(item => item.jenis === 'pengeluaran' && item.tanggal.startsWith(bulan))
        .reduce((total, item) => total + Number(item.nominal), 0);

    const budget = state.budgetBulanan;
    const persen = budget > 0 ? Math.round((terpakai / budget) * 100) : 0;
    const bar = document.getElementById('budgetBar');
    const info = document.getElementById('budgetInfo');

    bar.style.width = `${Math.min(persen, 100)}%`;
    bar.className = 'progress-bar';
    if (persen >= 100) {
        bar.classList.add('danger');
    } else if (persen >= 80) {
        bar.classList.add('warning');
    }

    if (budget <= 0) {
        info.textContent = 'Batas anggaran belum ditetapkan.';
        return;
    }

    const sisa = budget - terpakai;
    info.textContent = sisa >= 0
        ? `Terpakai ${formatRupiah(terpakai)} dari ${formatRupiah(budget)} (${persen}%). Sisa ${formatRupiah(sisa)}.`
        : `Melebihi batas! Terpakai ${formatRupiah(terpakai)} dari ${formatRupiah(budget)} (${persen}%), lebih ${formatRupiah(-sisa)}.`;
}

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function startEdit(id) {
    const item = state.transactions.find(trx => trx.id === id);
    if (!item) {
        return;
    }

    state.editId = id;
    document.getElementById('tanggal').value = item.tanggal;
    document.getElementById('jenis').value = item.jenis;
    document.getElementById('kategori').value = item.kategori || 'Lain-lain';
    document.getElementById('nominal').value = item.nominal;
    document.getElementById('keterangan').value = item.keterangan;

    document.getElementById('formTitle').textContent = 'Edit Transaksi';
    document.getElementById('btnSimpan').textContent = 'Perbarui Transaksi';
    document.getElementById('btnBatal').hidden = false;
    document.getElementById('formTransaksi').scrollIntoView({ behavior: 'smooth' });
}

function resetForm() {
    state.editId = null;
    document.getElementById('formTransaksi').reset();
    document.getElementById('tanggal').valueAsDate = new Date();
    document.getElementById('formTitle').textContent = 'Tambah Transaksi';
    document.getElementById('btnSimpan').textContent = 'Simpan Transaksi';
    document.getElementById('btnBatal').hidden = true;
}

async function deleteTransaction(id) {
    if (!confirm('Yakin ingin menghapus transaksi ini?')) {
        return;
    }

    try {
        applyResult(await request(`${apiUrl}?id=${id}`, { method: 'DELETE' }));
        if (state.editId === id) {
            resetForm();
        }
    } catch (error) {
        console.error(error);
        alert(error.message);
    }
}

async function submitTransaction(event) {
    event.preventDefault();

    const payload = Object.fromEntries(new FormData(event.target).entries());
    const isEdit = state.editId !== null;
    const url = isEdit ? `${apiUrl}?id=${state.editId}` : apiUrl;

    try {
        const result = await request(url, {
            method: isEdit ? 'PUT' : 'POST',
            body: JSON.stringify(payload),
        });

        applyResult(result);
        resetForm();
        alert(result.message);
    } catch (error) {
        console.error(error);
        alert(error.message);
    }
}

async function submitBudget(event) {
    event.preventDefault();

    const budget = Number(document.getElementById('budgetBulanan').value);

    try {
        const result = await request(`${apiUrl}?action=budget`, {
            method: 'POST',
            body: JSON.stringify({ budget_bulanan: budget }),
        });

        document.getElementById('budgetBulanan').blur();
        applyResult(result);
    } catch (error) {
        console.error(error);
        alert(error.message);
    }
}

document.getElementById('formTransaksi').addEventListener('submit', submitTransaction);
document.getElementById('formBudget').addEventListener('submit', submitBudget);
document.getElementById('btnBatal').addEventListener('click', resetForm);
document.getElementById('bulanBudget').addEventListener('change', renderBudget);

document.getElementById('tableBody').addEventListener('click', event => {
    const id = Number(event.target.dataset.id);

    if (event.target.classList.contains('btn-edit')) {
        startEdit(id);
    } else if (event.target.classList.contains('btn-hapus')) {
        deleteTransaction(id);
    }
});

['filterDari', 'filterSampai', 'filterKategori'].forEach(id => {
    document.getElementById(id).addEventListener('change', renderFiltered);
});

document.getElementById('btnResetFilter').addEventListener('click', () => {
    document.getElementById('filterDari').value = '';
    document.getElementById('filterSampai').value = '';
    document.getElementById('filterKategori').value = '';
    renderFiltered();
});

document.getElementById('tanggal').valueAsDate = new Date();
fetchTransactions();
