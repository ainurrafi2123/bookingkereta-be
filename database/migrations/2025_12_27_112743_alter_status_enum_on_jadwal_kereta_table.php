<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // (Opsional tapi aman) mapping data lama ke status baru
        DB::statement("
            UPDATE jadwal_kereta SET status = 'active' WHERE status = 'aktif'
        ");
        DB::statement("
            UPDATE jadwal_kereta SET status = 'completed' WHERE status = 'selesai'
        ");
        DB::statement("
            UPDATE jadwal_kereta SET status = 'cancelled' WHERE status = 'dibatalkan'
        ");

        // ubah enum
        DB::statement("
            ALTER TABLE jadwal_kereta 
            MODIFY status ENUM(
                'active',
                'completed',
                'cancelled',
                'maintenance'
            ) NOT NULL DEFAULT 'active'
        ");
    }

    public function down(): void
    {
        // kembalikan enum lama
        DB::statement("
            ALTER TABLE jadwal_kereta 
            MODIFY status ENUM(
                'aktif',
                'selesai',
                'dibatalkan',
                'maintenance'
            ) NOT NULL DEFAULT 'aktif'
        ");

        // rollback data
        DB::statement("
            UPDATE jadwal_kereta SET status = 'aktif' WHERE status = 'active'
        ");
        DB::statement("
            UPDATE jadwal_kereta SET status = 'selesai' WHERE status = 'completed'
        ");
        DB::statement("
            UPDATE jadwal_kereta SET status = 'dibatalkan' WHERE status = 'cancelled'
        ");
    }
};


