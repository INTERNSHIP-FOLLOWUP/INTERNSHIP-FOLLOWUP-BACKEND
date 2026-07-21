<?php

namespace App\Http\Controllers\Api;

use App\Events\NewMessage;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyMessage;
use App\Models\InternshipAssignment;
use App\Models\User;
use Illuminate\Http\Request;

class CompanyMessageController extends Controller
{
    /**
     * Get all conversations for the authenticated user.
     * - If user is a company, list tutors they've messaged (with last message).
     * - If user is a tutor, list companies that messaged them (with last message).
     */
    public function conversations(Request $request)
    {
        $user = $request->user();

        // Determine which role the user is acting as
        $company = Company::where('user_id', $user->id)->first();

        if ($company) {
            // Tutors already messaged
            $messagedTutorIds = CompanyMessage::where('company_id', $company->id)
                ->selectRaw('DISTINCT tutor_id')
                ->pluck('tutor_id');

            // Tutors assigned to this company's students (via InternshipAssignment)
            $assignedTutorIds = InternshipAssignment::where('company_id', $company->id)
                ->whereNotNull('tutor_id')
                ->selectRaw('DISTINCT tutor_id')
                ->pluck('tutor_id');

            // Merge: unique tutor IDs, messaging history first
            $allTutorIds = $messagedTutorIds->merge($assignedTutorIds)->unique()->values();

            $conversations = User::whereIn('id', $allTutorIds)->get()->map(function ($tutor) use ($company, $messagedTutorIds) {
                $hasMessages = $messagedTutorIds->contains($tutor->id);

                $lastMessage = null;
                $unreadCount = 0;

                if ($hasMessages) {
                    $lastMessage = CompanyMessage::where('company_id', $company->id)
                        ->where('tutor_id', $tutor->id)
                        ->latest()
                        ->first();

                    $unreadCount = CompanyMessage::where('company_id', $company->id)
                        ->where('tutor_id', $tutor->id)
                        ->where('sender_type', 'tutor')
                        ->where('is_read', false)
                        ->count();
                }

                return [
                    'user' => [
                        'id' => $tutor->id,
                        'name' => $tutor->name,
                        'email' => $tutor->email,
                        'avatar_url' => $tutor->avatar_url,
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
                // Sort: conversations with last_message first, then alphabetically
                return ($conv['last_message'] ? '0' : '1') . ($conv['user']['name'] ?? '');
            })->values();

            return response()->json([
                'data' => $conversations,
                'company_name' => $company->company_name,
            ]);
        }

        // User is a tutor → get companies they've conversed with
        $companyIds = CompanyMessage::where('tutor_id', $user->id)
            ->selectRaw('DISTINCT company_id')
            ->pluck('company_id');

        $conversations = Company::whereIn('id', $companyIds)->get()->map(function ($company) use ($user) {
            $lastMessage = CompanyMessage::where('company_id', $company->id)
                ->where('tutor_id', $user->id)
                ->latest()
                ->first();

            $unreadCount = CompanyMessage::where('company_id', $company->id)
                ->where('tutor_id', $user->id)
                ->where('sender_type', 'company')
                ->where('is_read', false)
                ->count();

            return [
                'company' => [
                    'id' => $company->id,
                    'name' => $company->company_name,
                    'email' => $company->email,
                    'logo_url' => $company->company_image_url ?? $company->company_profile_image_url,
                ],
                'last_message' => $lastMessage ? [
                    'id' => $lastMessage->id,
                    'message' => $lastMessage->message,
                    'sender_type' => $lastMessage->sender_type,
                    'created_at' => $lastMessage->created_at->toISOString(),
                ] : null,
                'unread_count' => $unreadCount,
            ];
        })->values();

        return response()->json([
            'data' => $conversations,
        ]);
    }

    /**
     * Get messages between the authenticated user and another party.
     */
    public function messages(Request $request, $otherPartyId)
    {
        $user = $request->user();
        $company = Company::where('user_id', $user->id)->first();

        $query = CompanyMessage::query();

        if ($company) {
            // Company viewing messages with a tutor
            $query->where('company_id', $company->id)
                ->where('tutor_id', $otherPartyId);
        } else {
            // Tutor viewing messages with a company
            $query->where('tutor_id', $user->id)
                ->where('company_id', $otherPartyId);
        }

        $messages = $query->orderBy('created_at', 'asc')->get()->map(function ($msg) use ($company) {
            return [
                'id' => $msg->id,
                'message' => $msg->message,
                'sender_type' => $msg->sender_type,
                'is_read' => $msg->is_read,
                'created_at' => $msg->created_at->toISOString(),
            ];
        });

        // Mark unread messages as read
        if ($company) {
            CompanyMessage::where('company_id', $company->id)
                ->where('tutor_id', $otherPartyId)
                ->where('sender_type', 'tutor')
                ->where('is_read', false)
                ->update(['is_read' => true]);
        } else {
            CompanyMessage::where('tutor_id', $user->id)
                ->where('company_id', $otherPartyId)
                ->where('sender_type', 'company')
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return response()->json([
            'data' => $messages,
        ]);
    }

    /**
     * Poll for new messages since a given timestamp.
     */
    public function poll(Request $request)
    {
        $request->validate([
            'since' => 'required|date',
            'other_party_id' => 'nullable|integer',
        ]);

        $user = $request->user();
        $company = Company::where('user_id', $user->id)->first();
        $since = $request->input('since');

        // Get conversation updates
        $query = CompanyMessage::where('created_at', '>', $since);

        if ($company) {
            $query->where('company_id', $company->id);
            // Count unread from tutor
            $queryUnread = CompanyMessage::where('company_id', $company->id)
                ->where('sender_type', 'tutor')
                ->where('is_read', false);
        } else {
            $query->where('tutor_id', $user->id);
            // Count unread from company
            $queryUnread = CompanyMessage::where('tutor_id', $user->id)
                ->where('sender_type', 'company')
                ->where('is_read', false);
        }

        // If a specific conversation is active, only poll for that one
        $otherPartyId = $request->input('other_party_id');
        if ($otherPartyId) {
            if ($company) {
                $query->where('tutor_id', $otherPartyId);
            } else {
                $query->where('company_id', $otherPartyId);
            }
        }

        $newMessages = $query->orderBy('created_at', 'asc')->get()->map(function ($msg) use ($company) {
            return [
                'id' => $msg->id,
                'message' => $msg->message,
                'sender_type' => $msg->sender_type,
                'is_read' => $msg->is_read,
                'created_at' => $msg->created_at->toISOString(),
                'company_id' => $msg->company_id,
                'tutor_id' => $msg->tutor_id,
            ];
        });

        // Get total unread count across all conversations
        $totalUnread = $queryUnread->count();

        return response()->json([
            'new_messages' => $newMessages,
            'total_unread' => $totalUnread,
            'server_time' => now()->toISOString(),
        ]);
    }

    /**
     * Send a message to a tutor (company) or company (tutor).
     */
    public function send(Request $request, $otherPartyId)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $user = $request->user();
        $company = Company::where('user_id', $user->id)->first();

        if ($company) {
            // Company sending to tutor
            $message = CompanyMessage::create([
                'company_id' => $company->id,
                'tutor_id' => $otherPartyId,
                'sender_type' => 'company',
                'message' => $validated['message'],
                'is_read' => false,
            ]);
        } else {
            // Tutor sending to company
            $message = CompanyMessage::create([
                'company_id' => $otherPartyId,
                'tutor_id' => $user->id,
                'sender_type' => 'tutor',
                'message' => $validated['message'],
                'is_read' => false,
            ]);
        }

        // Broadcast the event for real-time delivery
        try {
            broadcast(new NewMessage($message));
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
            ],
            'message' => 'Message sent successfully.',
        ], 201);
    }
}
