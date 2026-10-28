<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Who verified / resolved / rejected a report, and why
        Schema::table('reports', function (Blueprint $table) {
            foreach (['verified_by', 'resolved_by', 'rejected_by'] as $column) {
                if (! Schema::hasColumn('reports', $column)) {
                    $table->unsignedBigInteger($column)->nullable();
                }
            }
            foreach (['verified_at', 'rejected_at'] as $column) {
                if (! Schema::hasColumn('reports', $column)) {
                    $table->timestamp($column)->nullable();
                }
            }
            foreach (['resolution_notes', 'rejection_explanation'] as $column) {
                if (! Schema::hasColumn('reports', $column)) {
                    $table->text($column)->nullable();
                }
            }
            if (! Schema::hasColumn('reports', 'rejection_reason')) {
                $table->string('rejection_reason')->nullable();
            }
        });

        // Evidence files (the original image stays in Google Drive; we keep its metadata and link)
        Schema::table('report_attachments', function (Blueprint $table) {
            if (! Schema::hasColumn('report_attachments', 'name')) {
                $table->string('name')->nullable();
            }
            if (! Schema::hasColumn('report_attachments', 'size_bytes')) {
                $table->unsignedBigInteger('size_bytes')->nullable();
            }
            if (! Schema::hasColumn('report_attachments', 'drive_url')) {
                $table->text('drive_url')->nullable();
            }
        });

        // Internal "Maintenance notes" on a report
        if (! Schema::hasTable('report_notes')) {
            Schema::create('report_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->text('body');
                $table->timestamps();
            });
        }

        // Personnel: phone and manual availability switch
        Schema::table('technicians', function (Blueprint $table) {
            if (! Schema::hasColumn('technicians', 'phone')) {
                $table->string('phone', 30)->nullable();
            }
            if (! Schema::hasColumn('technicians', 'is_available')) {
                $table->boolean('is_available')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_notes');
    }
};
