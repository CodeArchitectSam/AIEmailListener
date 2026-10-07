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
        Schema::create('fake_mailbox_emails', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique()->index();
            $table->string('in_reply_to')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_email');
            $table->string('subject');
            $table->longText('body_text');
            $table->json('attachments')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fake_mailbox_emails');
    }
};