<?php

namespace App\Events;

use App\Models\SmsMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SmsReceived implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public SmsMessage $message)
    {
    }

    public function broadcastOn(): array
    {
        if (!$this->message->user_id) {
            return [];
        }

        return [new PrivateChannel('users.' . $this->message->user_id)];
    }

    public function broadcastAs(): string
    {
        return 'sms.received';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'assignment_id' => $this->message->number_assignment_id,
            'from' => $this->message->from_number,
            'to' => $this->message->to_number,
            'body' => $this->message->body,
            'received_at' => $this->message->received_at?->toISOString(),
        ];
    }
}
