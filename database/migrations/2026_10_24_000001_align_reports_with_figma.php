<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Figma flow: Pending -> Verified -> In Progress -> Resolved / Rejected, priorities Low / Medium / High.
 * Works on MySQL and SQLite (Laravel 11+ rebuilds the table on SQLite for ->change()).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('reports')->where('priority', 'critical')->update(['priority' => 'high']);

        Schema::table('reports', function (Blueprint $table) {
            $table->enum('status', ['pending', 'verified', 'in_progress', 'resolved', 'rejected'])->default('pending')->change();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium')->change();
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->enum('status', ['pending', 'in_progress', 'resolved', 'rejected'])->default('pending')->change();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium')->change();
        });
    }
};
