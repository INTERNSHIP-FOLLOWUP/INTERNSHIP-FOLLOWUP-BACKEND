<?php

namespace App\Events;

use App\Models\CompanyMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public CompanyMessage $message;

    /**
     * Create a new event instance.
     */
    public function __construct(CompanyMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Company channel
        if ($this->message->company) {
            $channels[] = new PrivateChannel('company.' . $this->message->company->user_id);
        }

        // Tutor channel
        if ($this->message->tutor) {
            $channels[] = new PrivateChannel('tutor.' . $this->message->tutor->user_id);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'new-message';
    }

    /**
     * Data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'message' => $this->message->message,
            'sender_type' => $this->message->sender_type,
            'is_read' => $this->message->is_read,
            'created_at' => $this->message->created_at->toISOString(),
            'company_id' => $this->message->company_id,
            'tutor_id' => $this->message->tutor_id,
        ];
    }
}
