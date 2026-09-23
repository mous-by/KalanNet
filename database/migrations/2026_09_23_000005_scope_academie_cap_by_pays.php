<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academie', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pays')->nullable()->after('id_academie');
            $table->foreign('id_pays')->references('id')->on('pays')->nullOnDelete();
        });

        Schema::table('cap', function (Blueprint $table) {
            // Denormalise le pays sur le CAP aussi (plutot que de toujours
            // passer par l'academie) pour simplifier le filtrage cote requete
            // et cote formulaire ecole (les deux selects se filtrent par pays
            // independamment avant que l'academie ne filtre en plus le CAP).
            $table->unsignedBigInteger('id_pays')->nullable()->after('id_cap');
            $table->foreign('id_pays')->references('id')->on('pays')->nullOnDelete();
        });

        $maliId = DB::table('pays')->where('code_iso', 'ML')->value('id');
        if ($maliId) {
            DB::table('academie')->update(['id_pays' => $maliId]);
            DB::table('cap')->update(['id_pays' => $maliId]);
        }
    }

    public function down(): void
    {
        Schema::table('cap', function (Blueprint $table) {
            $table->dropForeign(['id_pays']);
            $table->dropColumn('id_pays');
        });

        Schema::table('academie', function (Blueprint $table) {
            $table->dropForeign(['id_pays']);
            $table->dropColumn('id_pays');
        });
    }
};
