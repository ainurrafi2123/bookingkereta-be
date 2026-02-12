<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pembelian_tiket', function (Blueprint $table) {
            $table->string('metode_pembayaran')->nullable()->after('total_harga');
        });
    }

    public function down()
    {
        Schema::table('pembelian_tiket', function (Blueprint $table) {
            $table->dropColumn('metode_pembayaran');
        });
    }
};
