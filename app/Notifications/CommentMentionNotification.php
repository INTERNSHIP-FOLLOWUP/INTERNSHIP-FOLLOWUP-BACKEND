<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommentMentionNotification extends Notification
{
    use Queueable;

    public Comment $comment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation for database channel.
     */
    public function toArray(object $notifiable): array
    {
        $authorName = $this->comment->user->name ?? 'Someone';
        $message = $this->comment->message;

        return [
            'title' => 'You were mentioned in a comment',
            'message' => "{$authorName} mentioned you in a comment: \"{$message}\"",
            'comment_id' => $this->comment->id,
            'worklog_id' => $this->comment->worklog_id,
            'author_name' => $authorName,
            'comment_preview' => substr($message, 0, 100),
            'type' => 'comment_mention',
        ];
    }
}