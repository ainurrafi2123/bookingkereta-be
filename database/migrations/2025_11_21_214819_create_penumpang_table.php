<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penumpang', function (Blueprint $table) {
            $table->id();

            // relasi ke tabel users
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->string('nik')->nullable();
            $table->string('nama_penumpang')->nullable();
            $table->string('alamat')->nullable();
            $table->string('no_hp')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penumpang');
    }
};
