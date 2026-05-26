<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('decaissement')) {
            Schema::table('decaissement', function (Blueprint $table) {
                if (!Schema::hasColumn('decaissement', 'validated_by')) {
                    $table->unsignedBigInteger('validated_by')->nullable()->after('valide');
                }
                if (!Schema::hasColumn('decaissement', 'validated_at')) {
                    $table->timestamp('validated_at')->nullable()->after('validated_by');
                }
            });
        }

        if (Schema::hasTable('permissions')) {
            foreach ([
                'caisses_apercu',
                'caisses_creation',
                'decaissements_apercu',
                'decaissements_creation',
                'decaissements_validation',
                'subventions_etat_apercu',
                'subventions_etat_encaisser',
            ] as $permission) {
                DB::table('permissions')->updateOrInsert(['name' => $permission], ['name' => $permission]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('decaissement')) {
            return;
        }

        Schema::table('decaissement', function (Blueprint $table) {
            if (Schema::hasColumn('decaissement', 'validated_at')) {
                $table->dropColumn('validated_at');
            }
            if (Schema::hasColumn('decaissement', 'validated_by')) {
                $table->dropColumn('validated_by');
            }
        });
    }
};
