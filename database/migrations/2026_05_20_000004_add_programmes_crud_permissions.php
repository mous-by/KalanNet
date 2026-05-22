<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        foreach (['programmes_creation', 'programmes_modification', 'programmes_supprimer'] as $name) {
            DB::table('permissions')->updateOrInsert(['name' => $name], ['name' => $name]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')
            ->whereIn('name', ['programmes_creation', 'programmes_modification', 'programmes_supprimer'])
            ->delete();
    }
};
