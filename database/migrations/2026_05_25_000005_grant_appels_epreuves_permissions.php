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

        foreach (['controle_apercu', 'controle_creation', 'controle_création', 'controle_modification'] as $name) {
            DB::table('permissions')->updateOrInsert(['name' => $name], ['name' => $name]);
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['controle_apercu', 'controle_creation', 'controle_création', 'controle_modification'])
            ->pluck('id');

        $userIds = DB::table('utilisateurs')
            ->whereIn('droit', ['Admin', 'enseignant'])
            ->pluck('idUtilisateur');

        foreach ($userIds as $userId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('user_permission')->updateOrInsert([
                    'user_id' => $userId,
                    'permission_id' => $permissionId,
                ], [
                    'user_id' => $userId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
