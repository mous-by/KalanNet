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

        foreach (['filieres_apercu', 'filieres_creation', 'filieres_modification', 'filieres_supprimer'] as $name) {
            DB::table('permissions')->updateOrInsert(['name' => $name], ['name' => $name]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')->whereIn('name', [
            'filieres_apercu', 'filieres_creation', 'filieres_modification', 'filieres_supprimer',
        ])->delete();
    }
};
