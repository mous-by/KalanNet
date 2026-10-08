<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * « Modifier son propre profil » : informations et mot de passe de son
     * propre compte, sans aucun droit sur les autres utilisateurs. Accordée à
     * tous les comptes qui voient déjà leur profil, pour que chacun garde la
     * possibilité de changer son mot de passe (retirable au cas par cas).
     */
    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('user_permission')) {
            return;
        }

        DB::table('permissions')->updateOrInsert(['name' => 'profiles_modification'], ['name' => 'profiles_modification']);
        $permissionId = DB::table('permissions')->where('name', 'profiles_modification')->min('id');
        $voirIds = DB::table('permissions')->where('name', 'profiles_apercu')->pluck('id');

        $comptes = DB::table('user_permission')
            ->whereIn('permission_id', $voirIds)
            ->whereNotIn('user_id', DB::table('user_permission')->where('permission_id', $permissionId)->select('user_id'))
            ->distinct()
            ->pluck('user_id');

        foreach ($comptes as $id) {
            DB::table('user_permission')->insert(['user_id' => $id, 'permission_id' => $permissionId]);
        }
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('name', 'profiles_modification')->value('id');
        if ($id) {
            DB::table('user_permission')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        }
    }
};
