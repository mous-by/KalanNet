<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('app_notifications')) {
            return;
        }

        DB::table('app_notifications')
            ->whereNotNull('link')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    if (!$row->link) {
                        continue;
                    }

                    $parts = parse_url($row->link);
                    if (!isset($parts['host'])) {
                        continue;
                    }

                    $relative = ($parts['path'] ?? '/')
                        . (isset($parts['query']) ? '?' . $parts['query'] : '')
                        . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');

                    DB::table('app_notifications')->where('id', $row->id)->update(['link' => $relative]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
