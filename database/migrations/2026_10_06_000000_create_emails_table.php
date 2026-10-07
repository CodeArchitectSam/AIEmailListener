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
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique();
            $table->string('in_reply_to')->nullable()->index();
            $table->string('sender_name')->nullable();
            $table->string('sender_email');
            $table->string('subject');
            $table->longText('body_text');
            $table->json('attachments')->nullable();
            $table->timestamp('received_at');
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->foreignId('task_id')->nullable()->index();
            $table->json('analysis')->nullable();
            $table->text('analysis_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};