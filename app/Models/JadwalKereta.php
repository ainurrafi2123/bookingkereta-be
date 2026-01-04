<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalKereta extends Model
{
    use HasFactory;

    protected $table = 'jadwal_kereta';

    protected $fillable = [
        'id_kereta',
        'kode_jadwal',
        'asal_keberangkatan',
        'tujuan_keberangkatan',
        'tanggal_berangkat',
        'tanggal_kedatangan',
        'harga_dewasa',
        'harga_anak',
        'harga_lansia',
        'status',
        'kuota_total',
        'kursi_tersedia',
        'kursi_terjual',
    ];

    protected $casts = [
        'tanggal_berangkat'  => 'datetime',
        'tanggal_kedatangan' => 'datetime',
    ];


    public function kereta()
    {
        return $this->belongsTo(Kereta::class, 'id_kereta');
    }

    public function pembelianTiket()
    {
        return $this->hasMany(PembelianTiket::class, 'id_jadwal_kereta');
    }
}
