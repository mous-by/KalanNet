<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Load the legacy SQL dump and execute it.
        $path = database_path('legacy_dump.sql');
        if (file_exists($path)) {
            $sql = file_get_contents($path);
            DB::unprepared($sql);
        } else {
            // Fallback: you can paste the dump content here if file not present.
            // DB::unprepared('...');
            throw new \Exception('Legacy dump file not found: ' . $path);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Dropping all tables imported from the dump (order matters for foreign keys).
        $tables = [
            'acces_academie', 'acces_cap', 'acces_ecole', /* add remaining table names */
        ];
        foreach (array_reverse($tables) as $table) {
            Schema::dropIfExists($table);
        }
    }
};
?>
