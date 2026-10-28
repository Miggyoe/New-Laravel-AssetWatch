<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The core tables (categories, buildings, locations, assets, reports, report_attachments, report_updates)
 * existed in your database but no migration created them, so a fresh clone or migrate:fresh would not rebuild them.
 * Every step is guarded, so on a database that already has these tables this migration only fills the gaps.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('buildings')) {
            Schema::create('buildings', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('locations')) {
            Schema::create('locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('building_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->timestamps();
                $table->unique(['building_id', 'name']);
            });
        }

        if (! Schema::hasTable('assets')) {
            Schema::create('assets', function (Blueprint $table) {
                $table->id();
                $table->string('asset_tag')->unique();
                $table->string('name');
                $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
                $table->string('condition')->default('good');
                $table->date('acquired_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('reports')) {
            Schema::create('reports', function (Blueprint $table) {
                $table->id();
                $table->string('reference_no')->unique();
                $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('priority')->default('medium')->index();
                $table->string('status')->default('pending')->index();
                $table->timestamp('resolved_at')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('report_attachments')) {
            Schema::create('report_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_id')->constrained()->cascadeOnDelete();
                $table->string('path');
                $table->string('mime_type')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('report_updates')) {
            Schema::create('report_updates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('from_status')->nullable();
                $table->string('to_status')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        } elseif (! Schema::hasColumn('report_updates', 'remarks')) {
            // Your table had no "remarks" column; creating a work order writes one.
            Schema::table('report_updates', function (Blueprint $table) {
                $table->text('remarks')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty: these tables hold live data.
    }
};
