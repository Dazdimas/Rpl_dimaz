from datetime import date
from flask import Flask, render_template, request, redirect, url_for, flash
from keuangan_service import PencatatKeuangan

app = Flask(__name__)
app.secret_key = 'kunci-rahasia-pencatat-keuangan-rpl'

# Inisialisasi Service Pencatat Keuangan
service = PencatatKeuangan(saldo_awal=0)

@app.route('/')
def index():
    """Menampilkan dashboard pencatatan keuangan dan ringkasan."""
    ringkasan = service.get_ringkasan()
    transaksi = service.transaksi
    return render_template('index.html', ringkasan=ringkasan, transaksi=transaksi,
                           hari_ini=date.today().isoformat())

@app.route('/set-saldo', methods=['POST'])
def set_saldo():
    """User Story 1: Mengatur nominal saldo awal."""
    nominal = request.form.get('saldo_awal')
    try:
        service.set_saldo_awal(nominal)
        flash("Saldo awal berhasil disimpan!", "success")
    except ValueError as e:
        flash(str(e), "danger")
    return redirect(url_for('index'))

@app.route('/tambah', methods=['POST'])
def tambah_transaksi():
    """User Story 2 & 3: Menambahkan transaksi baru (pemasukan/pengeluaran)."""
    tanggal = request.form.get('tanggal')
    jenis = request.form.get('jenis')
    keterangan = request.form.get('keterangan')
    nominal = request.form.get('nominal')

    try:
        service.tambah_transaksi(tanggal=tanggal, jenis=jenis, keterangan=keterangan, nominal=nominal)
        flash(f"Transaksi {jenis} '{keterangan}' berhasil ditambahkan!", "success")
    except ValueError as e:
        flash(str(e), "danger")

    return redirect(url_for('index'))

@app.route('/hapus/<int:transaksi_id>', methods=['POST'])
def hapus_transaksi(transaksi_id):
    """User Story 4: Menghapus data transaksi berdasarkan ID."""
    try:
        hapus_item = service.hapus_transaksi(transaksi_id)
        flash(f"Transaksi '{hapus_item['keterangan']}' berhasil dihapus.", "success")
    except ValueError as e:
        flash(str(e), "danger")

    return redirect(url_for('index'))

@app.route('/reset', methods=['POST'])
def reset():
    """Mereset seluruh data saldo dan transaksi."""
    service.reset_data()
    flash("Seluruh data keuangan berhasil direset ke 0.", "success")
    return redirect(url_for('index'))

if __name__ == '__main__':
    app.run(debug=True, port=5000)