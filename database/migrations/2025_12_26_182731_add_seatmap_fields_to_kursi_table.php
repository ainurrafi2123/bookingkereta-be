<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::table('kursi', function (Blueprint $table) {
            $table->integer('baris')->after('no_kursi');
            $table->enum('kolom', ['A', 'B', 'C', 'D','E','F',])->after('baris');
            $table->enum('status', ['available', 'booked', 'blocked', 'priority'])
                ->default('available')
                ->after('kolom');
        });
    }

    public function down()
    {
        Schema::table('kursi', function (Blueprint $table) {
            $table->dropColumn(['baris', 'kolom', 'status']);
        });
    }

};
