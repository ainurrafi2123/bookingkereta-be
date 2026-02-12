<?php
// database/migrations/xxxx_remove_status_from_kursi_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kursi', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('kursi', function (Blueprint $table) {
            $table->enum('status', ['available', 'booked'])->default('available');
        });
    }
};