<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Offre "ACHAT" (licence à vie) : duree_jours = 0 => fin_at NULL à l'activation,
 * école jamais bloquée. Attribuée par le SuperAdmin uniquement (masquée du self-service).
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('abonnement_offres')->updateOrInsert(
            ['code' => 'achat'],
            [
                'nom'         => 'Licence achetée (à vie)',
                'description' => 'Accès complet et illimité à KalanNet pour un établissement (achat unique, sans échéance).',
                'montant'     => 0,
                'devise'      => 'XOF',
                'duree_jours' => 0,
                'actif'       => true,
                'updated_at'  => now(),
                'created_at'  => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('abonnement_offres')->where('code', 'achat')->delete();
    }
};
