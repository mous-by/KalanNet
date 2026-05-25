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

        $legacy = DB::table('permissions')
            ->where('name', 'controle_création')
            ->orderBy('id')
            ->first();

        if ($legacy) {
            DB::table('permissions')
                ->where('id', $legacy->id)
                ->update(['name' => 'controle_creation']);
        } else {
            DB::table('permissions')->updateOrInsert(
                ['name' => 'controle_creation'],
                ['name' => 'controle_creation']
            );
        }
    }

    public function down(): void
    {
        //
    }
};
