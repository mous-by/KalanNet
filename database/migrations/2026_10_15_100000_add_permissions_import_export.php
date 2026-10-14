<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permissions d'import / export. Accordées d'office aux administrateurs et
     * gestionnaires qui ont déjà le droit correspondant, pour ne rien retirer
     * à personne (l'export des élèves n'était jusqu'ici soumis à aucune
     * permission) ; retirables ensuite au cas par cas.
     */
    private const PERMISSIONS = [
        'eleves_exporter' => ['eleves_apercu'],
        'enseignants_importer' => ['enseignants_creation', 'enseignants_création'],
        'enseignants_exporter' => ['enseignants_apercu'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('user_permission')) {
            return;
        }

        foreach (self::PERMISSIONS as $nom => $prealables) {
            DB::table('permissions')->updateOrInsert(['name' => $nom], ['name' => $nom]);
            $permissionId = DB::table('permissions')->where('name', $nom)->min('id');
            $prealablesIds = DB::table('permissions')->whereIn('name', $prealables)->pluck('id');

            $comptes = DB::table('user_permission as up')
                ->join('utilisateurs as u', 'u.idUtilisateur', '=', 'up.user_id')
                ->whereIn('u.droit', ['Admin', 'Gestionnaire'])
                ->whereIn('up.permission_id', $prealablesIds)
                ->whereNotIn('up.user_id', DB::table('user_permission')->where('permission_id', $permissionId)->select('user_id'))
                ->distinct()
                ->pluck('up.user_id');

            foreach ($comptes as $id) {
                DB::table('user_permission')->insert(['user_id' => $id, 'permission_id' => $permissionId]);
            }
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::PERMISSIONS) as $nom) {
            $id = DB::table('permissions')->where('name', $nom)->value('id');
            if ($id) {
                DB::table('user_permission')->where('permission_id', $id)->delete();
                DB::table('permissions')->where('id', $id)->delete();
            }
        }
    }
};
