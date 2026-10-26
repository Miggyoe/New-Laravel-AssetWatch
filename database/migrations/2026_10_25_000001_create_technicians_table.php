<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Maintenance personnel. The Maintenance Personnel module will extend this table.
        Schema::create('technicians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // set when the technician signs in with Microsoft
            $table->string('staff_id')->unique();          // MCC-M003
            $table->string('name');
            $table->string('specialty');                   // short label: "Carpentry"
            $table->string('specialization')->nullable();  // long label: "Carpentry & furniture repair"
            $table->string('email')->nullable();
            $table->string('extension', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technicians');
    }
};
