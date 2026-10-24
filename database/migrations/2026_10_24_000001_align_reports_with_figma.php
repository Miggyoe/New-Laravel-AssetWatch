<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Figma flow is Pending -> Verified -> In Progress -> Resolved (or Rejected) with
 * Low / Medium / High priority. Run this only if you already migrated the first schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE reports MODIFY status ENUM('pending','verified','in_progress','resolved','rejected') NOT NULL DEFAULT 'pending'");
        DB::statement("UPDATE reports SET priority = 'high' WHERE priority = 'critical'");
        DB::statement("ALTER TABLE reports MODIFY priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE reports MODIFY status ENUM('pending','in_progress','resolved','rejected') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE reports MODIFY priority ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium'");
    }
};
