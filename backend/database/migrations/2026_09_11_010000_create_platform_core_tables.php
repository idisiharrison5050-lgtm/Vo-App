<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('iso2', 2)->unique();
            $table->string('iso3', 3)->nullable()->unique();
            $table->string('name');
            $table->string('dialing_code', 8)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('status')->default('active')->index();
            $table->json('capabilities')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_health_check_at')->nullable();
            $table->timestamps();
        });

        Schema::create('phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->string('number');
            $table->string('normalized_number')->unique();
            $table->string('type')->default('mobile')->index();
            $table->string('status')->default('available')->index();
            $table->json('capabilities')->nullable();
            $table->string('provider_reference')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['country_id', 'status']);
            $table->index(['provider_id', 'status']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('phone_number_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number')->unique();
            $table->string('kind')->default('number_rental')->index();
            $table->string('term_type')->default('monthly')->index();
            $table->string('status')->default('pending')->index();
            $table->string('currency', 3)->default('USD');
            $table->unsignedBigInteger('subtotal_minor')->default(0);
            $table->unsignedBigInteger('fee_minor')->default(0);
            $table->unsignedBigInteger('total_minor')->default(0);
            $table->string('idempotency_key')->unique();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('number_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phone_number_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('term_type')->default('monthly')->index();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable()->index();
            $table->boolean('auto_renew')->default(false);
            $table->string('status')->default('pending')->index();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['phone_number_id', 'status']);
        });

        Schema::create('wallet_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('idempotency_key')->unique();
            $table->string('type')->index();
            $table->string('direction');
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3);
            $table->bigInteger('balance_after_minor');
            $table->string('status')->default('posted')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_ledger_entries');
        Schema::dropIfExists('number_assignments');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('phone_numbers');
        Schema::dropIfExists('providers');
        Schema::dropIfExists('services');
        Schema::dropIfExists('countries');
    }
};
