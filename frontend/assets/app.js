const apiUrl = 'http://localhost:8000/api.php';

let currentFilter = 'semua';
let currentSearch = '';
let deletingTransactionId = null;

const categoriesByJenis = {
    pemasukan: ['Gaji Pokok', 'Bonus & Usaha', 'Investasi', 'Penjualan', 'Lain-lain'],
    pengeluaran: ['Makanan & Minuman', 'Kebutuhan Rumah', 'Transportasi', 'Tagihan & Listrik', 'Pendidikan & Buku', 'Hiburan', 'Lain-lain']
};

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value || 0);
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    const isSuccess = type === 'success';

    toast.className = `toast flex items-center p-3.5 rounded-xl shadow-lg border text-sm font-medium transition duration-200 ${
        isSuccess ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'
    }`;

    toast.innerHTML = `
        <span class="mr-2 text-base">${isSuccess ? '✓' : '⚠️'}</span>
        <span>${message}</span>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => toast.remove(), 250);
    }, 3500);
}

async function fetchTransactions() {
    try {
        let url = `${apiUrl}?action=transactions`;
        if (currentFilter !== 'semua') {
            url += `&jenis=${encodeURIComponent(currentFilter)}`;
        }
        if (currentSearch.trim() !== '') {
            url += `&search=${encodeURIComponent(currentSearch.trim())}`;
        }

        const response = await fetch(url);
        if (!response.ok) {
            throw new Error('Gagal memuat data dari server.');
        }

        const result = await response.json();
        if (!result.Success) {
            throw new Error(result.Message || 'Terjadi kesalahan saat memuat data.');
        }

        renderSummary(result.Summary);
        renderTable(result.Data || []);
    } catch (error) {
        console.error(error);
        showToast('Tidak dapat terhubung ke backend. Pastikan server PHP berjalan di port 8000.', 'error');
    }
}

function renderSummary(summary) {
    if (!summary) return;

    document.getElementById('saldoAwal').textContent = formatRupiah(summary.SaldoAwal);
    document.getElementById('totalPemasukan').textContent = formatRupiah(summary.TotalPemasukan);
    document.getElementById('totalPengeluaran').textContent = formatRupiah(summary.TotalPengeluaran);
    
    const saldoAkhirEl = document.getElementById('saldoAkhir');
    saldoAkhirEl.textContent = formatRupiah(summary.SaldoAkhir);
    if (summary.SaldoAkhir < 0) {
        saldoAkhirEl.className = 'text-2xl font-extrabold text-rose-600 tracking-tight';
    } else {
        saldoAkhirEl.className = 'text-2xl font-extrabold text-indigo-600 tracking-tight';
    }

    document.getElementById('totalPemasukanCount').textContent = `${summary.PemasukanCount || 0} transaksi masuk`;
    document.getElementById('totalPengeluaranCount').textContent = `${summary.PengeluaranCount || 0} transaksi keluar`;
    document.getElementById('totalTransaksiCount').textContent = `Total ${summary.TotalTransaksi || 0} transaksi tercatat`;
}

function renderTable(transactions) {
    const tbody = document.getElementById('tableBody');
    const emptyState = document.getElementById('emptyState');
    tbody.innerHTML = '';

    if (!transactions || transactions.length === 0) {
        emptyState.classList.remove('hidden');
        return;
    }

    emptyState.classList.add('hidden');

    transactions.forEach(item => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50/80 transition';

        const isPemasukan = item.Jenis.toLowerCase() === 'pemasukan';
        const badgeClass = isPemasukan ? 'badge-income' : 'badge-expense';
        const nominalColor = isPemasukan ? 'text-emerald-600 font-bold' : 'text-rose-600 font-bold';
        const prefix = isPemasukan ? '+ ' : '- ';

        tr.innerHTML = `
            <td class="py-3 px-4 text-xs sm:text-sm text-slate-500 whitespace-nowrap">${item.Tanggal}</td>
            <td class="py-3 px-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize ${badgeClass}">
                    ${item.Jenis}
                </span>
            </td>
            <td class="py-3 px-4">
                <div class="font-semibold text-slate-800 text-sm">${item.Kategori || 'Umum'}</div>
                <div class="text-xs text-slate-500">${item.Keterangan}</div>
            </td>
            <td class="py-3 px-4 text-right whitespace-nowrap ${nominalColor} text-sm">
                ${prefix}${formatRupiah(item.Nominal)}
            </td>
            <td class="py-3 px-4 text-center whitespace-nowrap">
                <div class="flex items-center justify-center space-x-1">
                    <button type="button" class="btn-edit p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" data-id="${item.Id}" title="Ubah Transaksi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    </button>
                    <button type="button" class="btn-delete p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" data-id="${item.Id}" title="Hapus Transaksi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </div>
            </td>
        `;

        tbody.appendChild(tr);
    });

    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', () => openEditModal(btn.dataset.id));
    });

    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', () => openDeleteModal(btn.dataset.id));
    });
}

function updateCategoryOptions(jenis) {
    const select = document.getElementById('kategori');
    const categories = categoriesByJenis[jenis] || categoriesByJenis.pemasukan;
    select.innerHTML = '';
    categories.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat;
        option.textContent = cat;
        select.appendChild(option);
    });
}

document.querySelectorAll('input[name="jenis"]').forEach(radio => {
    radio.addEventListener('change', (e) => {
        updateCategoryOptions(e.target.value);
    });
});

async function submitTransaction(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const payload = {
        Tanggal: formData.get('tanggal'),
        Jenis: formData.get('jenis'),
        Kategori: formData.get('kategori'),
        Nominal: parseInt(formData.get('nominal'), 10),
        Keterangan: formData.get('keterangan')
    };

    try {
        const response = await fetch(`${apiUrl}?action=transactions`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        if (!result.Success) {
            throw new Error(result.Message || 'Gagal menyimpan transaksi.');
        }

        form.reset();
        document.getElementById('tanggal').valueAsDate = new Date();
        updateCategoryOptions('pemasukan');
        fetchTransactions();
        showToast('Transaksi berhasil ditambahkan!');
    } catch (error) {
        console.error(error);
        showToast(error.message, 'error');
    }
}

async function openEditModal(id) {
    try {
        const response = await fetch(`${apiUrl}?action=transactions&id=${id}`);
        const result = await response.json();
        if (!result.Success) {
            throw new Error(result.Message || 'Gagal mengambil detail transaksi.');
        }

        const data = result.Data;
        document.getElementById('editId').value = data.Id;
        document.getElementById('editTanggal').value = data.Tanggal;
        document.getElementById('editJenis').value = data.Jenis;
        document.getElementById('editKategori').value = data.Kategori;
        document.getElementById('editNominal').value = data.Nominal;
        document.getElementById('editKeterangan').value = data.Keterangan;

        document.getElementById('modalEdit').classList.remove('hidden');
    } catch (error) {
        console.error(error);
        showToast(error.message, 'error');
    }
}

async function submitEditTransaction(event) {
    event.preventDefault();
    const id = document.getElementById('editId').value;
    const payload = {
        Id: parseInt(id, 10),
        Tanggal: document.getElementById('editTanggal').value,
        Jenis: document.getElementById('editJenis').value,
        Kategori: document.getElementById('editKategori').value,
        Nominal: parseInt(document.getElementById('editNominal').value, 10),
        Keterangan: document.getElementById('editKeterangan').value
    };

    try {
        const response = await fetch(`${apiUrl}?action=transactions&id=${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        if (!result.Success) {
            throw new Error(result.Message || 'Gagal memperbarui transaksi.');
        }

        document.getElementById('modalEdit').classList.add('hidden');
        fetchTransactions();
        showToast('Transaksi berhasil diperbarui!');
    } catch (error) {
        console.error(error);
        showToast(error.message, 'error');
    }
}

function openDeleteModal(id) {
    deletingTransactionId = id;
    document.getElementById('modalHapus').classList.remove('hidden');
}

async function confirmDelete() {
    if (!deletingTransactionId) return;

    try {
        const response = await fetch(`${apiUrl}?action=transactions&id=${deletingTransactionId}`, {
            method: 'DELETE'
        });

        const result = await response.json();
        if (!result.Success) {
            throw new Error(result.Message || 'Gagal menghapus transaksi.');
        }

        document.getElementById('modalHapus').classList.add('hidden');
        deletingTransactionId = null;
        fetchTransactions();
        showToast('Transaksi berhasil dihapus!');
    } catch (error) {
        console.error(error);
        showToast(error.message, 'error');
    }
}

async function openSaldoModal() {
    try {
        const response = await fetch(`${apiUrl}?action=saldo-awal`);
        const result = await response.json();
        if (result.Success && result.Data) {
            document.getElementById('inputSaldoAwal').value = result.Data.SaldoAwal || 0;
        }
        document.getElementById('modalSaldo').classList.remove('hidden');
    } catch (error) {
        console.error(error);
        showToast('Gagal memuat saldo awal.', 'error');
    }
}

async function submitSaldoAwal(event) {
    event.preventDefault();
    const nominal = parseInt(document.getElementById('inputSaldoAwal').value, 10);

    try {
        const response = await fetch(`${apiUrl}?action=saldo-awal`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ Nominal: nominal })
        });

        const result = await response.json();
        if (!result.Success) {
            throw new Error(result.Message || 'Gagal memperbarui saldo awal.');
        }

        document.getElementById('modalSaldo').classList.add('hidden');
        fetchTransactions();
        showToast('Saldo awal berhasil diperbarui!');
    } catch (error) {
        console.error(error);
        showToast(error.message, 'error');
    }
}

document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => {
            b.className = 'filter-btn px-3 py-1.5 rounded-md text-slate-600 hover:text-slate-900 transition';
        });
        btn.className = 'filter-btn px-3 py-1.5 rounded-md bg-white text-indigo-600 font-semibold shadow-sm transition';

        currentFilter = btn.dataset.filter;
        fetchTransactions();
    });
});

let searchTimeout;
document.getElementById('searchInput').addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentSearch = e.target.value;
        fetchTransactions();
    }, 300);
});

document.getElementById('formTransaksi').addEventListener('submit', submitTransaction);
document.getElementById('formEditTransaksi').addEventListener('submit', submitEditTransaction);
document.getElementById('formSaldoAwal').addEventListener('submit', submitSaldoAwal);

document.getElementById('btnAturSaldoAwal').addEventListener('click', openSaldoModal);
document.getElementById('btnKonfirmasiHapus').addEventListener('click', confirmDelete);
document.getElementById('btnBatalHapus').addEventListener('click', () => {
    document.getElementById('modalHapus').classList.add('hidden');
    deletingTransactionId = null;
});

document.querySelectorAll('.close-modal').forEach(btn => {
    btn.addEventListener('click', () => document.getElementById('modalSaldo').classList.add('hidden'));
});

document.querySelectorAll('.close-modal-edit').forEach(btn => {
    btn.addEventListener('click', () => document.getElementById('modalEdit').classList.add('hidden'));
});

document.getElementById('tanggal').valueAsDate = new Date();
updateCategoryOptions('pemasukan');
fetchTransactions();
