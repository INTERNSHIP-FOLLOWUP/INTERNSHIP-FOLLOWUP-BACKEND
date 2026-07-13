<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Followup;
use App\Services\FollowupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FollowupController extends Controller
{
    /**
     * Display a listing of follow-ups with filters.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Followup::with(['student', 'tutor', 'company']);

        // Role-based filtering
        if ($user->hasRole('tutor')) {
            $query->where('tutor_id', $user->id);
        } elseif ($user->hasRole('student')) {
            $query->where('student_id', $user->student->id);
        }

        // Filters
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->has('meeting_type')) {
            $query->where('meeting_type', $request->meeting_type);
        }
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('meeting_date', [$request->from_date, $request->to_date]);
        }

        $followups = $query->orderBy('meeting_date', 'desc')->paginate(15);

        return response()->json([
            'data' => $followups->items(),
            'message' => 'Follow-ups retrieved successfully.',
            'meta' => [
                'total' => $followups->total(),
                'per_page' => $followups->perPage(),
                'current_page' => $followups->currentPage(),
                'last_page' => $followups->lastPage(),
                'from' => $followups->firstItem(),
                'to' => $followups->lastItem(),
            ]
        ], 200);
    }

    /**
     * Store a newly created follow-up.
     */
    public function store(Request $request, FollowupService $followupService)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'tutor_id' => 'required|exists:users,id',
            'company_id' => 'nullable|exists:companies,id',
            'meeting_type' => 'required|in:Monthly,Quarterly,Annual,Emergency',
            'meeting_date' => 'required|date',
            'notes' => 'required|string|max:5000',
            'action_items' => 'nullable|string|max:2000',
            'next_followup' => 'nullable|date|after:meeting_date',
        ], [
            'student_id.required' => 'The student field is required.',
            'student_id.exists' => 'The selected student does not exist.',
            'tutor_id.required' => 'The tutor field is required.',
            'tutor_id.exists' => 'The selected tutor does not exist.',
            'company_id.exists' => 'The selected company does not exist.',
            'meeting_type.required' => 'The meeting type field is required.',
            'meeting_type.in' => 'The meeting type must be one of: Monthly, Quarterly, Annual, Emergency.',
            'meeting_date.required' => 'The meeting date field is required.',
            'meeting_date.date' => 'The meeting date must be a valid date.',
            'notes.required' => 'The notes field is required.',
            'notes.string' => 'The notes must be a string.',
            'notes.max' => 'The notes may not exceed 5000 characters.',
            'action_items.string' => 'The action items must be a string.',
            'action_items.max' => 'The action items may not exceed 2000 characters.',
            'next_followup.date' => 'The next follow-up must be a valid date.',
            'next_followup.after' => 'The next follow-up must be after the meeting date.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $followup = $followupService->createFollowup($request->all());

        return response()->json([
            'data' => $followup->load(['student', 'tutor', 'company']),
            'message' => 'Follow-up created successfully.'
        ], 201);
    }

    /**
     * Display the specified follow-up.
     */
    public function show(Followup $followup)
    {
        return response()->json([
            'data' => $followup->load(['student', 'tutor', 'company']),
            'message' => 'Follow-up retrieved successfully.',
        ], 200);
    }

    /**
     * Update the specified follow-up.
     */
    public function update(Request $request, Followup $followup, FollowupService $followupService)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'sometimes|exists:students,id',
            'tutor_id' => 'sometimes|exists:users,id',
            'company_id' => 'nullable|exists:companies,id',
            'meeting_type' => 'sometimes|in:Monthly,Quarterly,Annual,Emergency',
            'meeting_date' => 'sometimes|date',
            'notes' => 'sometimes|string|max:5000',
            'action_items' => 'nullable|string|max:2000',
            'next_followup' => 'nullable|date|after:meeting_date',
        ], [
            'student_id.exists' => 'The selected student does not exist.',
            'tutor_id.exists' => 'The selected tutor does not exist.',
            'company_id.exists' => 'The selected company does not exist.',
            'meeting_type.in' => 'The meeting type must be one of: Monthly, Quarterly, Annual, Emergency.',
            'meeting_date.date' => 'The meeting date must be a valid date.',
            'notes.string' => 'The notes must be a string.',
            'notes.max' => 'The notes may not exceed 5000 characters.',
            'action_items.string' => 'The action items must be a string.',
            'action_items.max' => 'The action items may not exceed 2000 characters.',
            'next_followup.date' => 'The next follow-up must be a valid date.',
            'next_followup.after' => 'The next follow-up must be after the meeting date.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $followup = $followupService->updateFollowup($followup, $request->all());

        return response()->json([
            'data' => $followup->load(['student', 'tutor', 'company']),
            'message' => 'Follow-up updated successfully.'
        ], 200);
    }

    /**
     * Remove the specified follow-up.
     */
    public function destroy(Followup $followup)
    {
        $followup->delete();

        return response()->json([
            'message' => 'Follow-up deleted successfully.'
        ], 200);
    }
}