<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pays', function (Blueprint $table) {
            // Niveau (numero de classe) + nom de l'examen national qui sanctionne
            // chaque cycle -- varie par pays (ex: Mali = DEF en 9e/BAC en 12e,
            // Guinee = BEPC/BAC a d'autres niveaux). Nullable partout : un pays
            // sans ces deux paires configurees n'a simplement pas de logique
            // d'examen national tant qu'un SupAdmin ne l'a pas renseignee.
            $table->unsignedTinyInteger('niveau_examen_intermediaire')->nullable()->after('actif');
            $table->string('nom_examen_intermediaire', 30)->nullable()->after('niveau_examen_intermediaire');
            $table->unsignedTinyInteger('niveau_examen_final')->nullable()->after('nom_examen_intermediaire');
            $table->string('nom_examen_final', 30)->nullable()->after('niveau_examen_final');
        });

        // resultats_def_terminal.niveau_examen etait un ENUM('DEF','BAC') MySQL --
        // contrainte au niveau du schema, pas seulement de la validation
        // applicative. Elargi en VARCHAR pour accepter le nom d'examen de
        // n'importe quel pays (ex: BEPC), sans casser les lignes existantes
        // (DEF/BAC restent des valeurs valides, juste plus enum-contraintes).
        DB::statement("ALTER TABLE resultats_def_terminal MODIFY niveau_examen VARCHAR(30) NULL");

        DB::table('pays')->where('code_iso', 'ML')->update([
            'niveau_examen_intermediaire' => 9,
            'nom_examen_intermediaire' => 'DEF',
            'niveau_examen_final' => 12,
            'nom_examen_final' => 'BAC',
        ]);
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE resultats_def_terminal MODIFY niveau_examen ENUM('DEF','BAC') NULL");

        Schema::table('pays', function (Blueprint $table) {
            $table->dropColumn([
                'niveau_examen_intermediaire',
                'nom_examen_intermediaire',
                'niveau_examen_final',
                'nom_examen_final',
            ]);
        });
    }
};
