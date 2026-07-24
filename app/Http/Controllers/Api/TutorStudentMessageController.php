<?php

namespace App\Http\Controllers\Api;

use App\Events\NewTutorStudentMessage;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\TutorStudentMessage;
use App\Models\User;
use Illuminate\Http\Request;

class TutorStudentMessageController extends Controller
{
    /**
     * Resolve the tutor's users.id from the authenticated user.
     * students.tutor_id references users.id, so we use $user->id directly.
     */
    private function resolveTutorId(\Illuminate\Contracts\Auth\Authenticatable $user): ?int
    {
        return $user->getAuthIdentifier();
    }

    /**
     * Get all student conversations for the authenticated tutor.
     * Only returns students that are officially assigned to this tutor.
     */
    public function conversations(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role?->name !== 'tutor') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $tutorUserId = $user->id;
        $tutorId = $this->resolveTutorId($user);

        // Only students assigned to this tutor
        $assignedStudentIds = Student::where('tutor_id', $tutorUserId)
            ->pluck('id');

        $conversations = Student::whereIn('id', $assignedStudentIds)->get()->map(function ($student) use ($tutorUserId) {
            $lastMessage = TutorStudentMessage::where('tutor_id', $tutorUserId)
                ->where('student_id', $student->id)
                ->latest()
                ->first();

            $unreadCount = TutorStudentMessage::where('tutor_id', $tutorUserId)
                ->where('student_id', $student->id)
                ->where('sender_type', 'student')
                ->where('is_read', false)
                ->count();

            return [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'photo_url' => $student->photo_url,
                ],
                'last_message' => $lastMessage ? [
                    'id' => $lastMessage->id,
                    'message' => $lastMessage->message,
                    'sender_type' => $lastMessage->sender_type,
                    'created_at' => $lastMessage->created_at->toISOString(),
                ] : null,
                'unread_count' => $unreadCount,
            ];
        })->sortBy(function ($conv) {
            return ($conv['last_message'] ? '0' : '1') . ($conv['student']['name'] ?? '');
        })->values();

        return response()->json([
            'data' => $conversations,
        ]);
    }

    /**
     * Get messages between the tutor and a specific student.
     * Only allows if the student is assigned to the tutor.
     */
    public function messages(Request $request, $studentId)
    {
        $user = $request->user();

        if (!$user || $user->role?->name !== 'tutor') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $tutorUserId = $user->id;

        // Verify student is assigned to this tutor
        $isAssigned = Student::where('id', $studentId)
            ->where('tutor_id', $tutorUserId)
            ->exists();

        if (!$isAssigned) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        $messages = TutorStudentMessage::where('tutor_id', $tutorUserId)
            ->where('student_id', $studentId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'message' => $msg->message,
                    'sender_type' => $msg->sender_type,
                    'is_read' => $msg->is_read,
                    'created_at' => $msg->created_at->toISOString(),
                    'tutor_id' => $msg->tutor_id,
                    'student_id' => $msg->student_id,
                ];
            });

        // Mark unread messages from student as read
        TutorStudentMessage::where('tutor_id', $tutorUserId)
            ->where('student_id', $studentId)
            ->where('sender_type', 'student')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'data' => $messages,
        ]);
    }

    /**
     * Send a message from tutor to a student.
     * Only allows if the student is assigned to the tutor.
     */
    public function send(Request $request, $studentId)
    {
        $user = $request->user();

        if (!$user || $user->role?->name !== 'tutor') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $tutorUserId = $user->id;

        // Verify student is assigned to this tutor
        $isAssigned = Student::where('id', $studentId)
            ->where('tutor_id', $tutorUserId)
            ->exists();

        if (!$isAssigned) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $message = TutorStudentMessage::create([
            'tutor_id' => $user->id,
            'student_id' => $studentId,
            'sender_type' => 'tutor',
            'message' => $validated['message'],
            'is_read' => false,
        ]);

        // Broadcast for real-time delivery
        try {
            broadcast(new NewTutorStudentMessage($message));
        } catch (\Throwable $e) {
            // Broadcasting is optional — fall back to polling
        }

        return response()->json([
            'data' => [
                'id' => $message->id,
                'message' => $message->message,
                'sender_type' => $message->sender_type,
                'is_read' => $message->is_read,
                'created_at' => $message->created_at->toISOString(),
                'tutor_id' => $message->tutor_id,
                'student_id' => $message->student_id,
            ],
            'message' => 'Message sent successfully.',
        ], 201);
    }

    /**
     * Send a message from student to their tutor.
     * Used by the student messaging endpoint.
     */
    public function studentSend(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role?->name !== 'student') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $student = Student::where('email', $user->email)->first();
        if (!$student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        // Resolve tutor user ID from student's assigned tutor
        $tutorUserId = null;
        if ($student->tutor) {
            $tutorUserId = $student->tutor->user_id;
        }

        if (!$tutorUserId) {
            return response()->json(['message' => 'No tutor assigned.'], 404);
        }

        $message = TutorStudentMessage::create([
            'tutor_id' => $tutorUserId,
            'student_id' => $student->id,
            'sender_type' => 'student',
            'message' => $validated['message'],
            'is_read' => false,
        ]);

        // Broadcast for real-time delivery
        try {
            broadcast(new NewTutorStudentMessage($message));
        } catch (\Throwable $e) {
            // Broadcasting is optional — fall back to polling
        }

        return response()->json([
            'data' => [
                'id' => $message->id,
                'message' => $message->message,
                'sender_type' => $message->sender_type,
                'is_read' => $message->is_read,
                'created_at' => $message->created_at->toISOString(),
                'tutor_id' => $message->tutor_id,
                'student_id' => $message->student_id,
            ],
            'message' => 'Message sent successfully.',
        ], 201);
    }

    /**
     * Get recent messages for a student (their conversations with tutors).
     * Also returns the tutor's name so the frontend can display it even when no messages exist yet.
     */
    public function studentConversations(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->role?->name !== 'student') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $student = Student::where('email', $user->email)->first();
        if (!$student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $studentId = $student->id;

        // Resolve tutor info — use the internship assignment's tutor (users.id) which is the source of truth,
        // not the student's tutors.id which may be outdated.
        $tutorName = null;
        $tutorPhotoUrl = null;
        $assignment = $student->internshipAssignment()->with('tutor')->first();
        if ($assignment && $assignment->tutor) {
            $tutorName = $assignment->tutor->name;
            $tutorPhotoUrl = $assignment->tutor?->avatar ?? null;
        }

        $messages = TutorStudentMessage::where('student_id', $studentId)
            ->with('tutor')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'message' => $msg->message,
                    'sender_type' => $msg->sender_type,
                    'is_read' => $msg->is_read,
                    'created_at' => $msg->created_at->toISOString(),
                    'tutor_id' => $msg->tutor_id,
                    'student_id' => $msg->student_id,
                    'tutor_name' => $msg->tutor?->name,
                ];
            });

        // Mark unread messages from tutor as read
        TutorStudentMessage::where('student_id', $studentId)
            ->where('sender_type', 'tutor')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'data' => $messages,
            'tutor_name' => $tutorName,
            'tutor_photo_url' => $tutorPhotoUrl,
        ]);
    }
}
