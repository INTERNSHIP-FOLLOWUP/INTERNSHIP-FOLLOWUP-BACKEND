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

        // Company channel — broadcast to all supervisors of the company
        if ($this->message->supervisor && $this->message->supervisor->company) {
            // Get all supervisor user IDs for this company and add channel for each
            $companyId = $this->message->supervisor->company_id;
            $supervisorUserIds = \App\Models\CompanySupervisor::where('company_id', $companyId)
                ->pluck('user_id');

            foreach ($supervisorUserIds as $uid) {
                $channels[] = new PrivateChannel('company.' . $uid);
            }
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
            'company_supervisors_id' => $this->message->company_supervisors_id,
            'tutor_id' => $this->message->tutor_id,
        ];
    }
}
