from flask import Flask, render_template, request, redirect

app = Flask(__name__)

saldo_awal = 0
transaksi = []

@app.route('/')
def index():
    total_pemasukan = sum(t['nominal'] for t in transaksi if t['jenis'] == 'pemasukan')
    total_pengeluaran = sum(t['nominal'] for t in transaksi if t['jenis'] == 'pengeluaran')
    sisa_saldo = saldo_awal + total_pemasukan - total_pengeluaran
    
    return render_template(
        'index.html', 
        saldo_awal=saldo_awal, 
        transaksi=transaksi, 
        total_pemasukan=total_pemasukan,
        total_pengeluaran=total_pengeluaran, 
        sisa_saldo=sisa_saldo
    )

@app.route('/set-saldo', methods=['POST'])
def set_saldo():
    global saldo_awal
    nominal_saldo = request.form.get('saldo_awal')
    if nominal_saldo:
        saldo_awal = int(nominal_saldo)
    return redirect('/')

@app.route('/tambah', methods=['POST'])
def tambah_transaksi():
    keterangan = request.form.get('keterangan')
    nominal = request.form.get('nominal')
    tanggal = request.form.get('tanggal')
    jenis = request.form.get('jenis')

    if keterangan and nominal and tanggal and jenis:
        transaksi.append({
            'keterangan': keterangan,
            'nominal': int(nominal),
            'tanggal': tanggal,
            'jenis': jenis
        })
    
    return redirect('/')

if __name__ == '__main__':
    app.run(debug=True)