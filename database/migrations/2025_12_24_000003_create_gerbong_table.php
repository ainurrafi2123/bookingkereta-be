<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('gerbong', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_kereta');

            $table->string('nama_gerbong', 50);
            $table->integer('kuota');

            $table->timestamps();

            $table->foreign('id_kereta')
                ->references('id')
                ->on('kereta')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gerbong');
    }
};
