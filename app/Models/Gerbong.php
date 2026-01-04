<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gerbong extends Model
{
    use HasFactory;

    protected $table = 'gerbong';

    protected $fillable = [
        'id_kereta',
        'nama_gerbong',
        'kelas_gerbong',
        'kuota',
    ];

    public function kereta()
    {
        return $this->belongsTo(Kereta::class, 'id_kereta');
    }

    public function kursi()
    {
        return $this->hasMany(Kursi::class, 'id_gerbong');
    }
}
