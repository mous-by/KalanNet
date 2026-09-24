<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes_officielles', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pays')->nullable()->after('id_classe_officielle');
            $table->foreign('id_pays')->references('id')->on('pays')->nullOnDelete();
        });

        // ordre_enseignement etait un ENUM MySQL limite aux 4 libelles maliens
        // ('Fondamentale I', ...). Jamais utilise pour de la logique metier
        // (juste tri/affichage/validation, confirme par audit) -- elargi en
        // VARCHAR pour stocker les memes slugs que Classe.ordreEnseignement
        // (fondamentale1, fondamentale2, secondairegenerale,
        // secondairetechniqueetprofessionnel), affiches ensuite via
        // ExamenNational::ordresLabels() selon le pays -- au lieu de figer le
        // texte malien dans le schema. Les 30 lignes maliennes existantes sont
        // converties de leur libelle vers le slug correspondant.
        DB::statement("ALTER TABLE classes_officielles MODIFY ordre_enseignement VARCHAR(50) NULL");

        DB::table('classes_officielles')->where('ordre_enseignement', 'Fondamentale I')->update(['ordre_enseignement' => 'fondamentale1']);
        DB::table('classes_officielles')->where('ordre_enseignement', 'Fondamentale II')->update(['ordre_enseignement' => 'fondamentale2']);
        DB::table('classes_officielles')->where('ordre_enseignement', 'Secondaire Generale')->update(['ordre_enseignement' => 'secondairegenerale']);
        DB::table('classes_officielles')->where('ordre_enseignement', 'Secondaire Technique et Professionnel')->update(['ordre_enseignement' => 'secondairetechniqueetprofessionnel']);

        $maliId = DB::table('pays')->where('code_iso', 'ML')->value('id');
        if ($maliId) {
            DB::table('classes_officielles')->update(['id_pays' => $maliId]);
        }
    }

    public function down(): void
    {
        DB::table('classes_officielles')->where('ordre_enseignement', 'fondamentale1')->update(['ordre_enseignement' => 'Fondamentale I']);
        DB::table('classes_officielles')->where('ordre_enseignement', 'fondamentale2')->update(['ordre_enseignement' => 'Fondamentale II']);
        DB::table('classes_officielles')->where('ordre_enseignement', 'secondairegenerale')->update(['ordre_enseignement' => 'Secondaire Generale']);
        DB::table('classes_officielles')->where('ordre_enseignement', 'secondairetechniqueetprofessionnel')->update(['ordre_enseignement' => 'Secondaire Technique et Professionnel']);

        DB::statement("ALTER TABLE classes_officielles MODIFY ordre_enseignement ENUM('Fondamentale I','Fondamentale II','Secondaire Generale','Secondaire Technique et Professionnel') NULL");

        Schema::table('classes_officielles', function (Blueprint $table) {
            $table->dropForeign(['id_pays']);
            $table->dropColumn('id_pays');
        });
    }
};
