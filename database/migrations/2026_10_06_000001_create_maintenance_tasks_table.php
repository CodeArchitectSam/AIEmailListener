<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_number')->nullable()->unique();
            $table->string('title', 80);
            $table->text('description')->nullable();
            $table->string('category')->index();
            $table->string('priority')->index();
            $table->string('status')->default('Open')->index();
            $table->string('location_building')->nullable();
            $table->string('location_floor')->nullable();
            $table->string('location_room')->nullable();
            $table->string('asset')->nullable();
            $table->decimal('confidence', 3, 2)->nullable();
            $table->string('requester_name')->nullable();
            $table->string('requester_email')->nullable();
            $table->foreignId('source_email_id')->nullable()->index();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_tasks');
    }
};