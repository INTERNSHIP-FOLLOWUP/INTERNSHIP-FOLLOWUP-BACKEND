<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
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
    public function store(Request $request)
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

        $comment = Comment::create($request->all());

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
        $this->authorize('update', $comment);

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
    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully.'
        ], 200);
    }
}