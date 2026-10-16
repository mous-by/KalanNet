<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Annuler un paiement de frais scolaires : la permission était donnée
     * d'office aux Admins et Gestionnaires. Elle est retirée à tous ; le
     * SupAdmin l'accorde à l'Admin qui la demande, qui peut la transmettre à
     * son gestionnaire. Les accès retirés sont gardés pour pouvoir revenir en arrière.
     */
    public function up(): void
    {
        $id = DB::table('permissions')->where('name', 'paiements_annuler')->value('id');
        if (!$id) {
            return;
        }

        Schema::create('sauvegarde_paiements_annuler', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
        });

        $retires = DB::table('user_permission as up')
            ->join('utilisateurs as u', 'u.idUtilisateur', '=', 'up.user_id')
            ->where('up.permission_id', $id)
            ->where('u.droit', '!=', 'SupAdmin')
            ->pluck('up.user_id');

        foreach ($retires->chunk(1000) as $lot) {
            DB::table('sauvegarde_paiements_annuler')->insertOrIgnore($lot->map(fn ($u) => ['user_id' => $u])->all());
            DB::table('user_permission')->where('permission_id', $id)->whereIn('user_id', $lot)->delete();
        }
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('name', 'paiements_annuler')->value('id');
        if ($id && Schema::hasTable('sauvegarde_paiements_annuler')) {
            foreach (DB::table('sauvegarde_paiements_annuler')->pluck('user_id')->chunk(1000) as $lot) {
                DB::table('user_permission')->insertOrIgnore($lot->map(fn ($u) => ['user_id' => $u, 'permission_id' => $id])->all());
            }
        }
        Schema::dropIfExists('sauvegarde_paiements_annuler');
    }
};
