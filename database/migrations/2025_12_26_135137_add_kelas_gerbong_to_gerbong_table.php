<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        Schema::table('gerbong', function (Blueprint $table) {
            $table->enum('kelas_gerbong', ['ekonomi', 'bisnis', 'eksekutif'])
                  ->after('nama_gerbong');
        });
    }

    public function down(): void
    {
        Schema::table('gerbong', function (Blueprint $table) {
            $table->dropColumn('kelas_gerbong');
        });
    }
};
