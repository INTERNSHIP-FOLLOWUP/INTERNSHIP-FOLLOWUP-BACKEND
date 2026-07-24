<?php

namespace App\Events;

use App\Models\TutorStudentMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewTutorStudentMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TutorStudentMessage $message;

    /**
     * Create a new event instance.
     */
    public function __construct(TutorStudentMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Tutor channel (by user id)
        if ($this->message->tutor_id) {
            $channels[] = new PrivateChannel('tutor.' . $this->message->tutor_id);
        }

        // Student channel (by student's user_id)
        if ($this->message->student && $this->message->student->user_id) {
            $channels[] = new PrivateChannel('student.' . $this->message->student->user_id);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'new-tutor-student-message';
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
            'tutor_id' => $this->message->tutor_id,
            'student_id' => $this->message->student_id,
        ];
    }
}
