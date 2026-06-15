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

        foreach (['utilisateurs_creation', 'permissions_assigner', 'permission_assigner', 'permission_voir'] as $permission) {
            DB::table('permissions')->updateOrInsert(['name' => $permission], ['name' => $permission]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')
            ->whereIn('name', ['utilisateurs_creation', 'permissions_assigner', 'permission_assigner', 'permission_voir'])
            ->delete();
    }
};
