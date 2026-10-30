<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Previous status" and "New status" columns of the Activity Logs table
        Schema::table('activity_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('activity_logs', 'from_status')) {
                $table->string('from_status', 30)->nullable();
            }
            if (! Schema::hasColumn('activity_logs', 'to_status')) {
                $table->string('to_status', 30)->nullable();
            }
        });
    }

    public function down(): void
    {
        // Intentionally empty: these columns may hold audit data.
    }
};
