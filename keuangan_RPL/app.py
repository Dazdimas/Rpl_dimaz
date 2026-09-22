from flask import Flask, render_template, request, redirect

app = Flask(__name__)

saldo_awal = 1000000
transaksi = []

@app.route('/')
def index():
    total_pengeluaran = sum(t['nominal'] for t in transaksi)
    sisa_saldo = saldo_awal - total_pengeluaran
    
    return render_template(
        'index.html', 
        saldo_awal=saldo_awal, 
        transaksi=transaksi, 
        total_pengeluaran=total_pengeluaran, 
        sisa_saldo=sisa_saldo
    )

@app.route('/tambah', methods=['POST'])
def tambah_transaksi():
    keterangan = request.form.get('keterangan')
    nominal = request.form.get('nominal')
    tanggal = request.form.get('tanggal')

    if keterangan and nominal and tanggal:
        transaksi.append({
            'keterangan': keterangan,
            'nominal': int(nominal),
            'tanggal': tanggal
        })
    
    return redirect('/')

@app.route('/reset-saldo', methods=['POST'])
def reset_saldo():
    global saldo_awal, transaksi
    saldo_baru = request.form.get('saldo_baru')
    if saldo_baru:
        saldo_awal = int(saldo_baru)
        transaksi = []
    return redirect('/')

if __name__ == '__main__':
    app.run(debug=True)