<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ligne_inscription') && Schema::hasColumn('ligne_inscription', 'id_planification')) {
            DB::statement('ALTER TABLE ligne_inscription MODIFY id_planification INT(11) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ligne_inscription') && Schema::hasColumn('ligne_inscription', 'id_planification')) {
            DB::table('ligne_inscription')->whereNull('id_planification')->update(['id_planification' => 0]);
            DB::statement('ALTER TABLE ligne_inscription MODIFY id_planification INT(11) NOT NULL');
        }
    }
};
