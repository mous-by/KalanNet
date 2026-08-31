<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('utilisateurs') || !Schema::hasTable('user_permission')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['dcap_apercu', 'academies_apercu'])
            ->pluck('id');

        $userIds = DB::table('utilisateurs')
            ->where('droit', 'Admin')
            ->pluck('idUtilisateur');

        DB::table('user_permission')
            ->whereIn('user_id', $userIds)
            ->whereIn('permission_id', $permissionIds)
            ->delete();
    }

    public function down(): void
    {
        //
    }
};
