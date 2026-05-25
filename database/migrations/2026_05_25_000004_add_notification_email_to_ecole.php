<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ecole') && !Schema::hasColumn('ecole', 'notification_email')) {
            Schema::table('ecole', function (Blueprint $table) {
                $table->boolean('notification_email')->default(true)->after('notification_sms');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ecole') && Schema::hasColumn('ecole', 'notification_email')) {
            Schema::table('ecole', function (Blueprint $table) {
                $table->dropColumn('notification_email');
            });
        }
    }
};
