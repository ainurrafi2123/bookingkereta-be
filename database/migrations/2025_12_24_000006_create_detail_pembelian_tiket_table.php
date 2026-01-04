<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('detail_pembelian_tiket', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_pembelian_tiket')
                ->constrained('pembelian_tiket')
                ->onDelete('cascade');

            $table->string('nik', 100);

            $table->string('nama_penumpang', 100);

            $table->foreignId('id_kursi')
                ->constrained('kursi')
                ->onDelete('cascade');

            $table->enum('kategori', ['anak', 'dewasa', 'lansia']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_pembelian_tiket');
    }
};
