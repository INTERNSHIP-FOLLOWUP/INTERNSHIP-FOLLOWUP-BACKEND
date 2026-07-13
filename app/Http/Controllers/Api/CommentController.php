<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Services\CommentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    /**
     * Display a listing of comments for a specific worklog.
     */
    public function index(Request $request, $worklogId)
    {
        $comments = Comment::where('worklog_id', $worklogId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'data' => $comments->items(),
            'message' => 'Comments retrieved successfully.',
            'meta' => [
                'total' => $comments->total(),
                'per_page' => $comments->perPage(),
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
            ]
        ], 200);
    }

    /**
     * Store a newly created comment.
     */
    public function store(Request $request, CommentService $commentService)
    {
        $validator = Validator::make($request->all(), [
            'worklog_id' => 'required|exists:worklogs,id',
            'message' => 'required|string|max:1000',
        ], [
            'worklog_id.required' => 'The worklog field is required.',
            'worklog_id.exists' => 'The selected worklog does not exist.',
            'message.required' => 'The message field is required.',
            'message.string' => 'The message must be a string.',
            'message.max' => 'The message may not exceed 1000 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $comment = $commentService->createComment($request->all());

        return response()->json([
            'data' => $comment->load('user'),
            'message' => 'Comment added successfully.'
        ], 201);
    }

    /**
     * Update the specified comment.
     */
    public function update(Request $request, Comment $comment)
    {
        // Check if the authenticated user is the comment author
        if ($request->user()->id !== $comment->user_id) {
            return response()->json([
                'message' => 'Unauthorized. You can only update your own comments.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
        ], [
            'message.required' => 'The message field is required.',
            'message.string' => 'The message must be a string.',
            'message.max' => 'The message may not exceed 1000 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $comment->update($request->all());

        return response()->json([
            'data' => $comment->load('user'),
            'message' => 'Comment updated successfully.'
        ], 200);
    }

    /**
     * Remove the specified comment.
     */
    public function destroy(Request $request, Comment $comment)
    {
        // Check if the authenticated user is the comment author
        if ($request->user()->id !== $comment->user_id) {
            return response()->json([
                'message' => 'Unauthorized. You can only delete your own comments.'
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully.'
        ], 200);
    }
}