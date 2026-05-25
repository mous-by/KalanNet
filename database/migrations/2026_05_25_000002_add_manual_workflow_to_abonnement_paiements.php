<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abonnement_paiements', function (Blueprint $table) {
            if (!Schema::hasColumn('abonnement_paiements', 'mode_paiement')) {
                $table->string('mode_paiement', 30)->default('MANUEL');
            }
            if (!Schema::hasColumn('abonnement_paiements', 'transaction_ref')) {
                $table->string('transaction_ref', 120)->nullable();
            }
            if (!Schema::hasColumn('abonnement_paiements', 'owner_note')) {
                $table->text('owner_note')->nullable();
            }
            if (!Schema::hasColumn('abonnement_paiements', 'preuve_url')) {
                $table->string('preuve_url', 255)->nullable();
            }
            if (!Schema::hasColumn('abonnement_paiements', 'review_note')) {
                $table->text('review_note')->nullable();
            }
            if (!Schema::hasColumn('abonnement_paiements', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable();
            }
            if (!Schema::hasColumn('abonnement_paiements', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable();
            }
        });

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->updateOrInsert(['name' => 'abonnements_validation'], ['name' => 'abonnements_validation']);
        }
    }

    public function down(): void
    {
        Schema::table('abonnement_paiements', function (Blueprint $table) {
            foreach (['mode_paiement', 'transaction_ref', 'owner_note', 'preuve_url', 'review_note', 'reviewed_by', 'reviewed_at'] as $column) {
                if (Schema::hasColumn('abonnement_paiements', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
