<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('jadwal_kereta', function (Blueprint $table) {

            $table->string('kode_jadwal')->unique()->after('id_kereta');

            $table->enum('status', [
                'aktif',
                'selesai',
                'dibatalkan',
                'maintenance'
            ])->default('aktif')->after('harga_lansia');

            $table->integer('kuota_total')->after('status');
            $table->integer('kursi_tersedia')->after('kuota_total');
            $table->integer('kursi_terjual')->default(0)->after('kursi_tersedia');
        });
    }

    public function down()
    {
        Schema::table('jadwal_kereta', function (Blueprint $table) {
            $table->dropColumn([
                'kode_jadwal',
                'status',
                'kuota_total',
                'kursi_tersedia',
                'kursi_terjual'
            ]);
        });
    }

};
