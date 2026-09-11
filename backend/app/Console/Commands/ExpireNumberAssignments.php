<?php

namespace App\Console\Commands;

use App\Models\NumberAssignment;
use App\Models\PhoneNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireNumberAssignments extends Command
{
    protected $signature = 'numbers:expire';
    protected $description = 'Expire non-renewing number assignments whose term has ended.';

    public function handle(): int
    {
        $count = 0;

        NumberAssignment::query()
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->where('auto_renew', false)
            ->orderBy('id')
            ->chunkById(100, function ($assignments) use (&$count) {
                foreach ($assignments as $assignment) {
                    DB::transaction(function () use ($assignment, &$count) {
                        $locked = NumberAssignment::query()->lockForUpdate()->find($assignment->id);
                        if (!$locked || $locked->status !== 'active' || !$locked->ends_at || $locked->ends_at->isFuture() || $locked->auto_renew) {
                            return;
                        }

                        $phone = PhoneNumber::query()->lockForUpdate()->find($locked->phone_number_id);
                        if ($phone) {
                            $phone->forceFill([
                                'status' => 'available',
                                'reservation_token' => null,
                                'reserved_until' => null,
                            ])->save();
                        }

                        $locked->forceFill([
                            'status' => 'expired',
                            'released_at' => now(),
                        ])->save();

                        $count++;
                    });
                }
            });

        $this->info("Expired {$count} number assignment(s).");
        return self::SUCCESS;
    }
}
