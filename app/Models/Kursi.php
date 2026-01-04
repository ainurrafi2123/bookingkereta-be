<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kursi extends Model
{
    use HasFactory;

    protected $table = 'kursi';

    protected $fillable = [
        'id_gerbong',
        'no_kursi',
        'baris',
        'kolom',
        'status',
    ];


    public function gerbong()
    {
        return $this->belongsTo(Gerbong::class, 'id_gerbong');
    }

    public function detailPembelian()
    {
        return $this->hasMany(DetailPembelianTiket::class, 'id_kursi');
    }
}
