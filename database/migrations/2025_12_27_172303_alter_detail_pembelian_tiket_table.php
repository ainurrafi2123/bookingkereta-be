<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('detail_pembelian_tiket', function (Blueprint $table) {

            // Ubah FK kursi
            $table->dropForeign(['id_kursi']);

            $table->foreign('id_kursi')
                ->references('id')
                ->on('kursi')
                ->onDelete('restrict');

            // Ubah kolom nik
            $table->string('nik', 16)->nullable()->change();

            // Tambah harga
            $table->decimal('harga', 12, 2)->after('kategori');

            // Unique constraint
            $table->unique(
                ['id_pembelian_tiket', 'id_kursi'],
                'unique_kursi_pembelian'
            );
        });
    }

    public function down()
    {
        Schema::table('detail_pembelian_tiket', function (Blueprint $table) {

            $table->dropUnique('unique_kursi_pembelian');
            $table->dropColumn('harga');

            $table->dropForeign(['id_kursi']);

            $table->foreign('id_kursi')
                ->references('id')
                ->on('kursi')
                ->onDelete('cascade');

            $table->string('nik', 100)->change();
        });
    }

};
