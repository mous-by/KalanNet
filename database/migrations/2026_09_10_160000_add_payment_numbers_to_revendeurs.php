<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revendeurs', function (Blueprint $table) {
            // Numéros où les écoles de ce revendeur doivent déposer leur paiement —
            // distincts des numéros par défaut de la plateforme (AbonnementPaymentService::MANUAL_MODES).
            // null = pas encore configuré, on retombe sur les numéros par défaut.
            $table->string('numero_orange_wave', 30)->nullable()->after('nom');
            $table->string('numero_mobicash', 30)->nullable()->after('numero_orange_wave');
        });
    }

    public function down(): void
    {
        Schema::table('revendeurs', function (Blueprint $table) {
            $table->dropColumn(['numero_orange_wave', 'numero_mobicash']);
        });
    }
};
