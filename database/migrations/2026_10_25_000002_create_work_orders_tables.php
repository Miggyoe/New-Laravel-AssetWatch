<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();                       // WO-2026-014
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users');           // "Assigned by"
            $table->string('title');
            $table->text('instructions');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium')->index();
            $table->enum('status', ['assigned', 'accepted', 'in_progress', 'completed', 'cancelled'])->default('assigned')->index();
            $table->unsignedTinyInteger('progress')->default(0);             // 0-100
            $table->dateTime('target_at');
            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('evidence_name')->nullable();                     // chair_repaired.jpg
            $table->text('evidence_url')->nullable();                        // Google Drive link
            $table->timestamps();
        });

        // Repair updates & completion timeline
        Schema::create('work_order_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->nullable();                            // work order status at the time
            $table->text('note');
            $table->timestamps();
        });

        // Internal administrative notes
        Schema::create('work_order_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_notes');
        Schema::dropIfExists('work_order_updates');
        Schema::dropIfExists('work_orders');
    }
};
