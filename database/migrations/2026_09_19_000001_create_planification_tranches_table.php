<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('planification_tranches')) {
            return;
        }

        // Pas de cle etrangere : convention du schema legacy (voir
        // planification), la suppression des tranches est geree dans
        // FinanceController::deleteLegacyPlanification().
        Schema::create('planification_tranches', function (Blueprint $table) {
            $table->id();
            $table->integer('id_planification')->index();
            $table->unsignedTinyInteger('numero');
            $table->string('libelle', 60);
            $table->decimal('montant', 12, 2);
            $table->date('date_limite');

            $table->unique(['id_planification', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planification_tranches');
    }
};
