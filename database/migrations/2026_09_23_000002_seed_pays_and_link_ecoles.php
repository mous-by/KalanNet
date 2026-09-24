<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mali en premier : c'est le pays de toutes les ecoles existantes,
        // son id doit etre connu pour les rattacher ci-dessous.
        $maliId = DB::table('pays')->insertGetId([
            'nom' => 'Mali',
            'code_iso' => 'ML',
            'indicatif_telephone' => '+223',
            'telephone_longueur' => 8,
            'telephone_premier_chiffre_min' => 2,
            'devise_code' => 'XOF',
            'devise_symbole' => 'FCFA',
            'devise_decimales' => 0,
            'actif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pays')->insert([
            'nom' => 'Guinée',
            'code_iso' => 'GN',
            'indicatif_telephone' => '+224',
            'telephone_longueur' => 9,
            'telephone_premier_chiffre_min' => null,
            'devise_code' => 'GNF',
            'devise_symbole' => 'GNF',
            'devise_decimales' => 0,
            'actif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (!Schema::hasColumn('ecole', 'id_pays')) {
            Schema::table('ecole', function (Blueprint $table) {
                $table->unsignedBigInteger('id_pays')->nullable()->after('idEcole');
                $table->foreign('id_pays')->references('id')->on('pays')->nullOnDelete();
            });
        }

        // Toute ecole existante est malienne : c'etait deja le cas implicitement
        // (FCFA, MaliPhone...) avant l'existence meme de cette colonne.
        DB::table('ecole')->whereNull('id_pays')->update(['id_pays' => $maliId]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('ecole', 'id_pays')) {
            Schema::table('ecole', function (Blueprint $table) {
                $table->dropForeign(['id_pays']);
                $table->dropColumn('id_pays');
            });
        }

        DB::table('pays')->whereIn('code_iso', ['ML', 'GN'])->delete();
    }
};
