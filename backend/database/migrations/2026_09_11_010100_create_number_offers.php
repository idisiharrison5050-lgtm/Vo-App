<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('term_type');
            $table->unsignedBigInteger('price_minor');
            $table->unsignedBigInteger('setup_fee_minor')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('billing_interval_days')->nullable();
            $table->boolean('auto_renew_supported')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['country_id', 'term_type', 'is_active']);
            $table->index(['service_id', 'term_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_offers');
    }
};
