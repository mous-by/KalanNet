<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reinscription', function (Blueprint $table) {
            if (!Schema::hasColumn('reinscription', 'statut_propose')) {
                $table->string('statut_propose', 20)->nullable()->after('statut');
            }

            if (!Schema::hasColumn('reinscription', 'motif_decision')) {
                $table->text('motif_decision')->nullable()->after('moyenneGeneral');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reinscription', function (Blueprint $table) {
            if (Schema::hasColumn('reinscription', 'motif_decision')) {
                $table->dropColumn('motif_decision');
            }

            if (Schema::hasColumn('reinscription', 'statut_propose')) {
                $table->dropColumn('statut_propose');
            }
        });
    }
};
