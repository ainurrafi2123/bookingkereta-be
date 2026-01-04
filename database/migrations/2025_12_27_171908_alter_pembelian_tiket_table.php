<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pembelian_tiket', function (Blueprint $table) {

            // Ubah tipe total_harga
            $table->decimal('total_harga', 12, 2)->change();

            // Tambah status
            $table->enum('status', ['booked', 'cancelled'])
                ->default('booked')
                ->after('total_harga');

            // Ubah FK jadwal (cascade → restrict)
            $table->dropForeign(['id_jadwal_kereta']);

            $table->foreign('id_jadwal_kereta')
                ->references('id')
                ->on('jadwal_kereta')
                ->onDelete('restrict');
        });
    }


    public function down()
    {
        Schema::table('pembelian_tiket', function (Blueprint $table) {

            $table->dropForeign(['id_jadwal_kereta']);
            $table->dropColumn('status');

            $table->double('total_harga')->change();

            $table->foreign('id_jadwal_kereta')
                ->references('id')
                ->on('jadwal_kereta')
                ->onDelete('cascade');
        });
    }

};
