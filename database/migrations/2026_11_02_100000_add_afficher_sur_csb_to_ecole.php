<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** L'école apparaît dans la bande « Ils nous font confiance » du site de CSB, sauf si son Admin la retire. */
    public function up(): void
    {
        if (Schema::hasColumn('ecole', 'afficher_sur_csb')) {
            return;
        }
        Schema::table('ecole', function (Blueprint $table) {
            $table->boolean('afficher_sur_csb')->default(true);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('ecole', 'afficher_sur_csb')) {
            Schema::table('ecole', function (Blueprint $table) {
                $table->dropColumn('afficher_sur_csb');
            });
        }
    }
};
