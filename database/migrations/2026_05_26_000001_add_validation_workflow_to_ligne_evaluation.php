<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ligne_evaluation', function (Blueprint $table) {
            if (!Schema::hasColumn('ligne_evaluation', 'validation_status')) {
                $table->string('validation_status', 30)->default('valide')->after('note');
            }

            if (!Schema::hasColumn('ligne_evaluation', 'validated_by')) {
                $table->unsignedInteger('validated_by')->nullable()->after('validation_status');
            }

            if (!Schema::hasColumn('ligne_evaluation', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('validated_by');
            }
        });

        DB::table('permissions')->updateOrInsert(
            ['name' => 'evaluation_validation_notes'],
            ['name' => 'evaluation_validation_notes']
        );
    }

    public function down(): void
    {
        Schema::table('ligne_evaluation', function (Blueprint $table) {
            if (Schema::hasColumn('ligne_evaluation', 'validated_at')) {
                $table->dropColumn('validated_at');
            }

            if (Schema::hasColumn('ligne_evaluation', 'validated_by')) {
                $table->dropColumn('validated_by');
            }

            if (Schema::hasColumn('ligne_evaluation', 'validation_status')) {
                $table->dropColumn('validation_status');
            }
        });
    }
};
