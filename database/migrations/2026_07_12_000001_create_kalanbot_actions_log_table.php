<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kalanbot_actions_log', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('id_ecole')->nullable();
            $table->string('module', 60);
            $table->string('tool_name', 100);
            $table->json('arguments')->nullable();
            $table->enum('status', ['success', 'error', 'denied', 'cancelled'])->default('success');
            $table->text('message')->nullable();
            $table->json('result_data')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('idUtilisateur')->on('utilisateurs')->cascadeOnDelete();
            $table->index(['user_id', 'created_at']);
            $table->index('tool_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kalanbot_actions_log');
    }
};
