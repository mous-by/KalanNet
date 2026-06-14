<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('utilisateurs', function (Blueprint $table) {
            if (!Schema::hasColumn('utilisateurs', 'locale_preference')) {
                $table->string('locale_preference', 5)->nullable()->after('theme_preference');
            }
        });
    }

    public function down(): void
    {
        Schema::table('utilisateurs', function (Blueprint $table) {
            if (Schema::hasColumn('utilisateurs', 'locale_preference')) {
                $table->dropColumn('locale_preference');
            }
        });
    }
};