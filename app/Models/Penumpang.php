<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penumpang extends Model
{
    use HasFactory;

    protected $table = 'penumpang';

    protected $fillable = [
        'user_id',
        'nik',
        'nama_penumpang',
        'alamat',
        'no_hp'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pembelianTiket()
    {
        return $this->hasMany(PembelianTiket::class, 'id_user');
    }

}
