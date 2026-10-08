<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * « Voir profils » contrôle désormais l'accès aux informations de son
     * profil. Enseignants, parents et revendeurs ne l'avaient jamais reçue par
     * défaut : on la leur donne pour que rien ne change pour eux. Les Admin et
     * Gestionnaires l'ont par défaut ; s'ils ne l'ont pas, elle leur a été
     * retirée volontairement et on n'y touche pas.
     */
    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('user_permission')) {
            return;
        }

        DB::table('permissions')->updateOrInsert(['name' => 'profiles_apercu'], ['name' => 'profiles_apercu']);
        $permissionId = DB::table('permissions')->where('name', 'profiles_apercu')->min('id');

        $comptes = DB::table('utilisateurs')
            ->whereIn('droit', ['enseignant', 'parent', 'revendeur'])
            ->whereNotExists(fn ($q) => $q->from('user_permission')
                ->whereColumn('user_permission.user_id', 'utilisateurs.idUtilisateur')
                ->whereIn('permission_id', DB::table('permissions')->where('name', 'profiles_apercu')->pluck('id')))
            ->pluck('idUtilisateur');

        foreach ($comptes as $id) {
            DB::table('user_permission')->insert(['user_id' => $id, 'permission_id' => $permissionId]);
        }
    }

    public function down(): void
    {
        // Pas de retrait : on ne sait plus quelles attributions venaient d'ici.
    }
};
