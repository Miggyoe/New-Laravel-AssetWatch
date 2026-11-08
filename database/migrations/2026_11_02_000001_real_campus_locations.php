<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Room details: code (MB101), floor, type and a short note
        Schema::table('locations', function (Blueprint $table) {
            if (! Schema::hasColumn('locations', 'code')) {
                $table->string('code', 40)->nullable();
            }
            if (! Schema::hasColumn('locations', 'floor')) {
                $table->string('floor', 80)->nullable();
            }
            if (! Schema::hasColumn('locations', 'type')) {
                $table->string('type', 30)->nullable();
            }
            if (! Schema::hasColumn('locations', 'notes')) {
                $table->string('notes', 200)->nullable();
            }
        });

        // Buildings no longer have a code (the room codes, e.g. MB101, carry it)
        if (Schema::hasColumn('buildings', 'code')) {
            try {
                Schema::table('buildings', function (Blueprint $table) {
                    foreach (Schema::getIndexes('buildings') as $index) {
                        if (! $index['primary'] && in_array('code', $index['columns'], true)) {
                            $table->dropIndex($index['name']);
                        }
                    }
                });
                Schema::table('buildings', fn (Blueprint $table) => $table->dropColumn('code'));
            } catch (\Throwable $e) {
                // If the column cannot be dropped on this database, at least stop requiring it
                Schema::table('buildings', fn (Blueprint $table) => $table->string('code')->nullable()->change());
            }
        }
    }

    public function down(): void
    {
        // Intentionally empty: these columns hold real campus data.
    }
};
