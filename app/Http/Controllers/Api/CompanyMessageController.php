<?php

namespace App\Http\Controllers\Api;

use App\Events\NewMessage;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyMessage;
use App\Models\CompanySupervisor;
use App\Models\InternshipAssignment;
use App\Models\User;
use Illuminate\Http\Request;

class CompanyMessageController extends Controller
{
    /**
     * Get the authenticated supervisor's record.
     */
    private function getSupervisor(Request $request): ?CompanySupervisor
    {
        return CompanySupervisor::where('user_id', $request->user()->id)->first();
    }

    /**
     * Get all company supervisor IDs for a given company.
     */
    private function getSupervisorIdsForCompany(int $companyId): array
    {
        return CompanySupervisor::where('company_id', $companyId)->pluck('id')->toArray();
    }

    /**
     * Get all conversations for the authenticated user.
     * - If user is a supervisor, list tutors they've messaged (with last message).
     * - If user is a tutor, list companies that messaged them (with last message).
     */
    public function conversations(Request $request)
    {
        $user = $request->user();
        $supervisor = $this->getSupervisor($request);

        if ($supervisor) {
            $company = $supervisor->company;
            $supervisorIds = $this->getSupervisorIdsForCompany($company->id);

            // Tutors already messaged by any supervisor from this company
            $messagedTutorIds = CompanyMessage::whereIn('company_supervisors_id', $supervisorIds)
                ->selectRaw('DISTINCT tutor_id')
                ->pluck('tutor_id');

            // Tutors assigned to this company's students (via InternshipAssignment)
            $assignedTutorIds = InternshipAssignment::whereIn('company_supervisors_id', $supervisorIds)
                ->whereNotNull('tutor_id')
                ->selectRaw('DISTINCT tutor_id')
                ->pluck('tutor_id');

            // Merge: unique tutor IDs (these are user IDs), messaging history first
            $allUserIds = $messagedTutorIds->merge($assignedTutorIds)->unique()->values();

            $conversations = User::whereIn('id', $allUserIds)->get()->map(function ($convUser) use ($supervisorIds, $messagedTutorIds) {
                $hasMessages = $messagedTutorIds->contains($convUser->id);

                $lastMessage = null;
                $unreadCount = 0;

                if ($hasMessages) {
                    $lastMessage = CompanyMessage::whereIn('company_supervisors_id', $supervisorIds)
                        ->where('tutor_id', $convUser->id)
                        ->latest()
                        ->first();

                    $unreadCount = CompanyMessage::whereIn('company_supervisors_id', $supervisorIds)
                        ->where('tutor_id', $convUser->id)
                        ->where('sender_type', 'tutor')
                        ->where('is_read', false)
                        ->count();
                }

                return [
                    'user' => [
                        'id' => $convUser->id,
                        'name' => $convUser->name,
                        'email' => $convUser->email,
                        'avatar_url' => $convUser->avatar_url,
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
                return ($conv['last_message'] ? '0' : '1') . ($conv['user']['name'] ?? '');
            })->values();

            return response()->json([
                'data' => $conversations,
                'company_name' => $company->company_name,
            ]);
        }

        // User is a tutor → get companies they've conversed with
        $tutorUserId = $user->id;

        $supervisorIds = CompanyMessage::where('tutor_id', $tutorUserId)
            ->selectRaw('DISTINCT company_supervisors_id')
            ->pluck('company_supervisors_id')
            ->toArray();

        $companyIds = CompanySupervisor::whereIn('id', $supervisorIds)->pluck('company_id')->unique()->values()->toArray();

        $conversations = Company::whereIn('id', $companyIds)->get()->map(function ($convCompany) use ($tutorUserId) {
            $supervisorIds = $this->getSupervisorIdsForCompany($convCompany->id);

            $lastMessage = CompanyMessage::whereIn('company_supervisors_id', $supervisorIds)
                ->where('tutor_id', $tutorUserId)
                ->latest()
                ->first();

            $unreadCount = CompanyMessage::whereIn('company_supervisors_id', $supervisorIds)
                ->where('tutor_id', $tutorUserId)
                ->where('sender_type', 'company')
                ->where('is_read', false)
                ->count();

            return [
                'company' => [
                    'id' => $convCompany->id,
                    'name' => $convCompany->company_name,
                    'email' => $convCompany->email,
                    'logo_url' => $convCompany->company_image_url ?? $convCompany->company_profile_image_url,
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
        $supervisor = $this->getSupervisor($request);

        $query = CompanyMessage::query();

        if ($supervisor) {
            $supervisorIds = $this->getSupervisorIdsForCompany($supervisor->company_id);
            // Supervisor viewing messages with a tutor
            $query->whereIn('company_supervisors_id', $supervisorIds)
                ->where('tutor_id', $otherPartyId);
        } else {
            // Tutor viewing messages with a company — get all supervisors for that company
            $tutorSupervisorIds = $this->getSupervisorIdsForCompany($otherPartyId);
            $query->where('tutor_id', $user->id)
                ->whereIn('company_supervisors_id', $tutorSupervisorIds);
        }

        $messages = $query->orderBy('created_at', 'asc')->get()->map(function ($msg) {
            return [
                'id' => $msg->id,
                'message' => $msg->message,
                'sender_type' => $msg->sender_type,
                'is_read' => $msg->is_read,
                'created_at' => $msg->created_at->toISOString(),
            ];
        });

        // Mark unread messages as read
        if ($supervisor) {
            $supervisorIds = $this->getSupervisorIdsForCompany($supervisor->company_id);
            CompanyMessage::whereIn('company_supervisors_id', $supervisorIds)
                ->where('tutor_id', $otherPartyId)
                ->where('sender_type', 'tutor')
                ->where('is_read', false)
                ->update(['is_read' => true]);
        } else {
            $tutorSupervisorIds = $this->getSupervisorIdsForCompany($otherPartyId);
            CompanyMessage::where('tutor_id', $user->id)
                ->whereIn('company_supervisors_id', $tutorSupervisorIds)
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
        $supervisor = $this->getSupervisor($request);
        $since = $request->input('since');

        // Get conversation updates
        $query = CompanyMessage::where('created_at', '>', $since);

        if ($supervisor) {
            $supervisorIds = $this->getSupervisorIdsForCompany($supervisor->company_id);
            $query->whereIn('company_supervisors_id', $supervisorIds);
            // Count unread from tutor
            $queryUnread = CompanyMessage::whereIn('company_supervisors_id', $supervisorIds)
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
            if ($supervisor) {
                $query->where('tutor_id', $otherPartyId);
            } else {
                $query->where('company_supervisors_id', $otherPartyId);
            }
        }

        $newMessages = $query->orderBy('created_at', 'asc')->get()->map(function ($msg) {
            return [
                'id' => $msg->id,
                'message' => $msg->message,
                'sender_type' => $msg->sender_type,
                'is_read' => $msg->is_read,
                'created_at' => $msg->created_at->toISOString(),
                'company_supervisors_id' => $msg->company_supervisors_id,
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
        $supervisor = $this->getSupervisor($request);

        if ($supervisor) {
            // Supervisor sending to tutor
            $message = CompanyMessage::create([
                'company_supervisors_id' => $supervisor->id,
                'tutor_id' => $otherPartyId,
                'sender_type' => 'company',
                'message' => $validated['message'],
                'is_read' => false,
            ]);
        } else {
            // Tutor sending to company — use first supervisor of that company
            $targetSupervisor = CompanySupervisor::where('company_id', $otherPartyId)->first();
            $message = CompanyMessage::create([
                'company_supervisors_id' => $targetSupervisor?->id,
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
