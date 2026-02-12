<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'profile_photo',
    ];

    protected $hidden = [
        'password',
    ];

 // Relasi ke tabel penumpang
    public function penumpang()
    {
        return $this->hasOne(Penumpang::class, 'user_id');
    }

    // Relasi ke tabel petugas
    public function petugas()
    {
        return $this->hasOne(Petugas::class, 'user_id');
    }
}
