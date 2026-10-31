<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Campus-wide settings (key / value)
        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        // Each administrator's own notification preferences (JSON)
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'notification_preferences')) {
                $table->text('notification_preferences')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
