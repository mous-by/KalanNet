<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_mode', function (Blueprint $table) {
            $table->id();
            $table->boolean('actif')->default(false);
            $table->text('message')->nullable();
            $table->unsignedInteger('active_par')->nullable();
            $table->timestamp('active_at')->nullable();
            $table->timestamps();
        });

        // Ligne unique (id=1) toujours présente, désactivée par défaut.
        DB::table('maintenance_mode')->insert([
            'actif' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_mode');
    }
};
