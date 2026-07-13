<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class CommentService
{
    /**
     * Extract mentioned user IDs from a message.
     *
     * @param string $message
     * @return array
     */
    public function extractMentions(string $message): array
    {
        preg_match_all('/@(\w+)/', $message, $matches);
        
        $userIds = [];
        if (!empty($matches[1])) {
            $usernames = $matches[1];
            $users = User::whereIn('name', $usernames)->get();
            
            foreach ($users as $user) {
                $userIds[] = $user->id;
            }
        }
        
        return array_unique($userIds);
    }

    /**
     * Create a comment and send notifications to mentioned users.
     *
     * @param array $data
     * @return Comment
     */
    public function createComment(array $data): Comment
    {
        $comment = Comment::create($data);
        
        // Send notifications to mentioned users
        $this->sendMentionNotifications($comment);
        
        return $comment;
    }

    /**
     * Send notifications to mentioned users in a comment.
     *
     * @param Comment $comment
     * @return void
     */
    private function sendMentionNotifications(Comment $comment): void
    {
        $mentionedUserIds = $this->extractMentions($comment->message);
        
        if (!empty($mentionedUserIds)) {
            $mentionedUsers = User::whereIn('id', $mentionedUserIds)->get();
            
            foreach ($mentionedUsers as $user) {
                // Don't notify the comment author
                if ($user->id !== $comment->user_id) {
                    Notification::send($user, new \App\Notifications\CommentMentionNotification($comment));
                }
            }
        }
    }

    /**
     * Get comments for a worklog with filters.
     *
     * @param int $worklogId
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getWorklogComments(int $worklogId, array $filters = [])
    {
        $query = Comment::where('worklog_id', $worklogId)
            ->with('user');

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }
}