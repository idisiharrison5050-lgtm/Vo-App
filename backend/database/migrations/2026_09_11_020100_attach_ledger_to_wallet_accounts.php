<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_ledger_entries', function (Blueprint $table) {
            $table->foreignId('wallet_account_id')
                ->nullable()
                ->after('id')
                ->constrained('wallet_accounts')
                ->restrictOnDelete();

            $table->index(['wallet_account_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('wallet_ledger_entries', function (Blueprint $table) {
            $table->dropForeign(['wallet_account_id']);
            $table->dropIndex(['wallet_account_id', 'created_at']);
            $table->dropColumn('wallet_account_id');
        });
    }
};
