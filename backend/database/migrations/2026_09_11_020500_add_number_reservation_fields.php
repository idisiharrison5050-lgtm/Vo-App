<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->string('reservation_token')->nullable()->unique();
            $table->timestamp('reserved_until')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->dropUnique(['reservation_token']);
            $table->dropIndex(['reserved_until']);
            $table->dropColumn(['reservation_token', 'reserved_until']);
        });
    }
};
