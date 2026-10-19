<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Cachet électronique de l'école (image), apposé sur les cartes scolaires. */
    public function up(): void
    {
        if (Schema::hasTable('ecole') && !Schema::hasColumn('ecole', 'cachet')) {
            Schema::table('ecole', function (Blueprint $table) {
                $table->string('cachet', 255)->nullable()->after('logoEcole');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ecole', 'cachet')) {
            Schema::table('ecole', function (Blueprint $table) {
                $table->dropColumn('cachet');
            });
        }
    }
};
