<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kursi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_gerbong');
            $table->string('no_kursi', 10);
            $table->timestamps();

            $table->foreign('id_gerbong')
                ->references('id')
                ->on('gerbong')
                ->onDelete('cascade');

            $table->unique(['id_gerbong', 'no_kursi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kursi');
    }
};
