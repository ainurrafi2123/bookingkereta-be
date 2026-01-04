<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPembelianTiket extends Model
{
    use HasFactory;

    protected $table = 'detail_pembelian_tiket';

    protected $fillable = [
        'id_pembelian_tiket',
        'id_kursi',
        'nik',
        'nama_penumpang',
        'kategori',
        'harga', 
    ];

    public function pembelianTiket()
    {
        return $this->belongsTo(PembelianTiket::class,'id_pembelian_tiket');
    }

    public function kursi()
    {
        return $this->belongsTo(Kursi::class,'id_kursi');
    }
}
