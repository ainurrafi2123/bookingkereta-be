<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kereta', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kereta', 20)->unique();
            $table->string('nama_kereta', 100);
            $table->enum('kelas_kereta', ['ekonomi', 'bisnis', 'eksekutif']);
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kereta');
    }
};
