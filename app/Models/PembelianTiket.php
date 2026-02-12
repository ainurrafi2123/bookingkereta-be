<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PembelianTiket extends Model
{
    use HasFactory;

    protected $table = 'pembelian_tiket';

    protected $fillable = [
        'kode_tiket',
        'tanggal_pembelian',
        'id_penumpang',
        'id_jadwal_kereta',
        'total_harga',
        'status', 
        'metode_pembayaran',
    ];

    protected $casts = [
        'tanggal_pembelian' => 'datetime',
    ];

    public function penumpang()
    {
        return $this->belongsTo(Penumpang::class, 'id_penumpang');
    }

    public function jadwalKereta()
    {
        return $this->belongsTo(JadwalKereta::class, 'id_jadwal_kereta');
    }

    public function detailPembelian()
    {
        return $this->hasMany(
            DetailPembelianTiket::class,
            'id_pembelian_tiket'
        );
    }

    

}


