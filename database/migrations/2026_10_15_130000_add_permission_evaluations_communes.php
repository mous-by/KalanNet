<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Module Évaluations communes masqué : la permission n'est accordée à
     * personne ; le SupAdmin la donne ensuite à qui il veut (lui seul peut).
     */
    public function up(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->updateOrInsert(['name' => 'evaluations_communes_apercu'], ['name' => 'evaluations_communes_apercu']);
        }
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('name', 'evaluations_communes_apercu')->value('id');
        if ($id) {
            DB::table('user_permission')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        }
    }
};
