<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Mark all read" stores the moment; anything newer than that is unread
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'notifications_read_at')) {
                $table->timestamp('notifications_read_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Intentionally empty.
    }
};
