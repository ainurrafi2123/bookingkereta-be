<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePembelianTiketTable extends Migration
{
    public function up()
    {
        Schema::create('pembelian_tiket', function (Blueprint $table) {
            $table->id();
            $table->string('kode_tiket', 50)->unique();
            $table->dateTime('tanggal_pembelian');
            $table->unsignedBigInteger('id_penumpang');
            $table->unsignedBigInteger('id_jadwal_kereta');
            $table->double('total_harga');
            $table->timestamps();

            $table->foreign('id_penumpang')
                ->references('id')->on('penumpang')
                ->onDelete('cascade');

            $table->foreign('id_jadwal_kereta')
                ->references('id')
                ->on('jadwal_kereta')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pembelian_tiket');
    }
}
