<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phone_number_id')->constrained()->restrictOnDelete();
            $table->foreignId('number_assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_message_id')->nullable()->unique();
            $table->string('direction')->default('inbound');
            $table->string('from_number');
            $table->string('to_number');
            $table->text('body');
            $table->timestamp('received_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['number_assignment_id', 'received_at']);
            $table->index(['user_id', 'received_at']);
        });

        Schema::create('provider_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->string('event_id');
            $table->string('event_type');
            $table->boolean('signature_valid')->default(false);
            $table->string('status')->default('received')->index();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['provider_id', 'event_id']);
            $table->index(['provider_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_webhook_events');
        Schema::dropIfExists('sms_messages');
    }
};
