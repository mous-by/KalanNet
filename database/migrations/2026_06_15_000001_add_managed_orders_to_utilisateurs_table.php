<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('utilisateurs', 'managed_orders')) {
            Schema::table('utilisateurs', function (Blueprint $table) {
                $table->json('managed_orders')->nullable()->after('idEcole');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('utilisateurs', 'managed_orders')) {
            Schema::table('utilisateurs', function (Blueprint $table) {
                $table->dropColumn('managed_orders');
            });
        }
    }
};
