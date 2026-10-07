<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supprimer un enseignant qui n'a encore rien fait : réservé au SupAdmin
     * par défaut, accordé à une école seulement à sa demande.
     */
    public function up(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->updateOrInsert(['name' => 'enseignants_supprimer'], ['name' => 'enseignants_supprimer']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            $id = DB::table('permissions')->where('name', 'enseignants_supprimer')->value('id');
            if ($id) {
                DB::table('user_permission')->where('permission_id', $id)->delete();
                DB::table('permissions')->where('id', $id)->delete();
            }
        }
    }
};
