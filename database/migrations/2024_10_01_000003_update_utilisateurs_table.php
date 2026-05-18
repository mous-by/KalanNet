<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('utilisateurs', function (Blueprint $table) {
            if (!Schema::hasColumn('utilisateurs', 'theme_preference')) {
                $table->string('theme_preference')->default('bleu-sombre');
            }
            if (!Schema::hasColumn('utilisateurs', 'remember_token')) {
                $table->rememberToken();
            }
            if (!Schema::hasColumn('utilisateurs', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('utilisateurs', function (Blueprint $table) {
            $table->dropColumn(['theme_preference', 'remember_token', 'created_at', 'updated_at']);
        });
    }
};
