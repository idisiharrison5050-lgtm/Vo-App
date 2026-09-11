<?php

namespace Tests\Unit;

use App\Domain\Wallet\WalletLedger;
use App\Models\User;
use App\Models\WalletAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_is_idempotent(): void
    {
        $user = User::factory()->create();
        $ledger = app(WalletLedger::class);

        $first = $ledger->credit($user->id, 5000, 'USD', 'funding-1', 'funding');
        $second = $ledger->credit($user->id, 5000, 'USD', 'funding-1', 'funding');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(5000, WalletAccount::where('user_id', $user->id)->value('available_minor'));
    }

    public function test_debit_is_rejected_when_funds_are_insufficient(): void
    {
        $user = User::factory()->create();
        $ledger = app(WalletLedger::class);
        $ledger->credit($user->id, 1000, 'USD', 'funding-2', 'funding');

        $this->expectExceptionMessage('Insufficient wallet funds.');
        $ledger->debit($user->id, 1001, 'USD', 'order-1', 'number_purchase');
    }

    public function test_debit_updates_the_account_and_creates_an_immutable_entry(): void
    {
        $user = User::factory()->create();
        $ledger = app(WalletLedger::class);
        $ledger->credit($user->id, 10000, 'USD', 'funding-3', 'funding');

        $entry = $ledger->debit($user->id, 3500, 'USD', 'order-2', 'number_purchase');

        $this->assertSame('debit', $entry->direction);
        $this->assertSame(6500, $entry->balance_after_minor);
        $this->assertSame(6500, WalletAccount::where('user_id', $user->id)->value('available_minor'));
    }
}
