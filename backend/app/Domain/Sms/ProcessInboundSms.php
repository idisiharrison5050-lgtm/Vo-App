<?php

namespace App\Domain\Sms;

use App\Models\NumberAssignment;
use App\Models\PhoneNumber;
use App\Models\SmsMessage;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;

class ProcessInboundSms
{
    public function __construct(private DatabaseManager $database)
    {
    }

    public function execute(array $event): ?SmsMessage
    {
        $providerMessageId = $event['provider_message_id'] ?? null;
        $phoneNumber = PhoneNumber::where('normalized_number', $event['to_number_normalized'] ?? '')
            ->first();

        if (!$phoneNumber) {
            return null;
        }

        $assignment = NumberAssignment::where('phone_number_id', $phoneNumber->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        return $this->database->transaction(function () use ($event, $providerMessageId, $phoneNumber, $assignment) {
            if ($providerMessageId) {
                $existing = SmsMessage::where('provider_message_id', $providerMessageId)->first();
                if ($existing) {
                    return $existing;
                }
            }

            return SmsMessage::create([
                'phone_number_id' => $phoneNumber->id,
                'number_assignment_id' => $assignment?->id,
                'user_id' => $assignment?->user_id,
                'provider_message_id' => $providerMessageId,
                'direction' => 'inbound',
                'from_number' => $event['from_number'] ?? 'unknown',
                'to_number' => $event['to_number'] ?? $phoneNumber->number,
                'body' => $event['body'] ?? '',
                'received_at' => $event['received_at'] ?? now(),
                'metadata' => $event['metadata'] ?? [],
            ]);
        });
    }
}
