<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->updateOrInsert(
                ['name' => 'annees_scolaires_creation'],
                ['name' => 'annees_scolaires_creation']
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('name', 'annees_scolaires_creation')
            ->pluck('id');

        if (Schema::hasTable('user_permission')) {
            DB::table('user_permission')->whereIn('permission_id', $permissionIds)->delete();
        }

        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};