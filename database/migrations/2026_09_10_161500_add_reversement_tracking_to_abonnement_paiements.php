<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abonnement_paiements', function (Blueprint $table) {
            // Uniquement renseigné pour un paiement d'une école apportée par un
            // revendeur : le revendeur a encaissé le prix de revente (chez lui),
            // le développeur reste créancier du prix de gros tant que le
            // revendeur ne lui a pas reversé sa part.
            $table->decimal('montant_du_developpeur', 12, 2)->nullable()->after('montant');
            $table->string('reverse_statut', 20)->nullable()->after('montant_du_developpeur'); // en_attente | recu
            $table->timestamp('reverse_at')->nullable()->after('reverse_statut');
            $table->unsignedInteger('reverse_par')->nullable()->after('reverse_at');
        });
    }

    public function down(): void
    {
        Schema::table('abonnement_paiements', function (Blueprint $table) {
            $table->dropColumn(['montant_du_developpeur', 'reverse_statut', 'reverse_at', 'reverse_par']);
        });
    }
};
