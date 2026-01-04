<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('jadwal_kereta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_kereta');

            $table->string('asal_keberangkatan', 100);
            $table->string('tujuan_keberangkatan', 100);

            $table->dateTime('tanggal_berangkat');
            $table->dateTime('tanggal_kedatangan');

            $table->double('harga_dewasa');
            $table->double('harga_anak');
            $table->double('harga_lansia');

            $table->timestamps();

            $table->foreign('id_kereta')
                ->references('id')
                ->on('kereta')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_kereta');
    }
};
