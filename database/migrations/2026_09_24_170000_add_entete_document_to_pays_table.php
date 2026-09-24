<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * En-tete officiel affiche sur les documents imprimes (bulletins, recus...) :
 * "MINISTERE DE ... / REPUBLIQUE DE ... / devise nationale". C'etait fige sur
 * le Mali dans 8 templates PDF, quel que soit le pays reel de l'ecole. Ce sont
 * des faits nationaux (identiques pour toutes les ecoles d'un meme pays), donc
 * portes par Pays plutot que par Ecole, et configurables uniquement par un
 * Admin de ce pays (cf. paysConfig() / configuration.pays.blade.php) -- pas
 * par le SupAdmin base au Mali, qui ne peut pas deviner l'en-tete officiel de
 * chaque pays. Nullable partout : une ecole dont le pays n'a pas encore
 * configure ces champs voit simplement le bloc omis, jamais une valeur
 * inventee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pays', function (Blueprint $table) {
            $table->text('entete_document_gauche')->nullable()->after('nom_examen_final');
            $table->text('entete_document_droite')->nullable()->after('entete_document_gauche');
        });

        DB::table('pays')->where('code_iso', 'ML')->update([
            'entete_document_gauche' => "MINISTERE DE L'EDUCATION NATIONALE",
            'entete_document_droite' => "REPUBLIQUE DU MALI\nUN PEUPLE - UN BUT - UNE FOI",
        ]);
    }

    public function down(): void
    {
        Schema::table('pays', function (Blueprint $table) {
            $table->dropColumn(['entete_document_gauche', 'entete_document_droite']);
        });
    }
};
