<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [];

        if (!Schema::hasColumn('users', 'status')) {
            $columns['status'] = true;
        }
        if (!Schema::hasColumn('users', 'default_currency')) {
            $columns['default_currency'] = true;
        }
        if (!Schema::hasColumn('users', 'last_seen_at')) {
            $columns['last_seen_at'] = true;
        }

        if ($columns) {
            Schema::table('users', function (Blueprint $table) use ($columns): void {
                if (isset($columns['status'])) {
                    $table->string('status')->default('active')->index();
                }
                if (isset($columns['default_currency'])) {
                    $table->string('default_currency', 3)->default('USD');
                }
                if (isset($columns['last_seen_at'])) {
                    $table->timestamp('last_seen_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        $columns = [];

        foreach (['status', 'default_currency', 'last_seen_at'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $columns[] = $column;
            }
        }

        if ($columns) {
            Schema::table('users', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
