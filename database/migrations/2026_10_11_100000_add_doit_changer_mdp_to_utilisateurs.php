<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mot de passe choisi par quelqu'un d'autre (création ou réinitialisation
     * par un Admin) : l'utilisateur doit le remplacer à sa prochaine connexion.
     * Les comptes encore en « 123456 » sont marqués dès maintenant.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('utilisateurs', 'doit_changer_mdp')) {
            Schema::table('utilisateurs', function (Blueprint $table) {
                $table->boolean('doit_changer_mdp')->default(false);
            });
        }

        // Comptes de démonstration Google Play : jamais bloqués.
        $demos = ['playstore.demo@kalannet.com', 'playstore.prive@kalannet.com'];

        DB::table('utilisateurs')
            ->whereNotNull('pwd')
            ->where(fn ($q) => $q->whereNull('email')->orWhereNotIn('email', $demos))
            ->orderBy('idUtilisateur')
            ->select(['idUtilisateur', 'pwd'])
            ->chunk(200, function ($comptes) {
                $faibles = $comptes->filter(function ($compte) {
                    try {
                        return Hash::check('123456', $compte->pwd);
                    } catch (\Throwable) {
                        return false;
                    }
                })->pluck('idUtilisateur');

                if ($faibles->isNotEmpty()) {
                    DB::table('utilisateurs')->whereIn('idUtilisateur', $faibles)->update(['doit_changer_mdp' => true]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('utilisateurs', 'doit_changer_mdp')) {
            Schema::table('utilisateurs', fn (Blueprint $table) => $table->dropColumn('doit_changer_mdp'));
        }
    }
};
