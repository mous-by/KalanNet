<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Message de bienvenue montré une fois à l'Admin d'une nouvelle école.
     * Les comptes déjà existants sont marqués « vu » : seuls les nouveaux le verront.
     */
    public function up(): void
    {
        if (Schema::hasColumn('utilisateurs', 'bienvenue_vue_at')) {
            return;
        }
        Schema::table('utilisateurs', function (Blueprint $table) {
            $table->timestamp('bienvenue_vue_at')->nullable();
        });
        DB::table('utilisateurs')->update(['bienvenue_vue_at' => now()]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('utilisateurs', 'bienvenue_vue_at')) {
            Schema::table('utilisateurs', function (Blueprint $table) {
                $table->dropColumn('bienvenue_vue_at');
            });
        }
    }
};
