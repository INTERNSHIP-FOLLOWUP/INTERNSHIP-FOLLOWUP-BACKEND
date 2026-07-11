<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InternshipAssignment;
use App\Models\Student;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AssignmentController extends Controller
{
    /**
     * Display a listing of internship assignments with filters.
     */
    public function index(Request $request)
    {
        $query = InternshipAssignment::with(['student', 'company', 'tutor']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by company
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by student
        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $assignments = $query->paginate(15);

        return response()->json([
            'data' => $assignments->items(),
            'message' => 'Internship assignments retrieved successfully.',
            'meta' => [
                'total' => $assignments->total(),
                'per_page' => $assignments->perPage(),
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'from' => $assignments->firstItem(),
                'to' => $assignments->lastItem(),
            ]
        ], 200);
    }

    /**
     * Store a newly created internship assignment.
     * Admin assigns a student to a company with a tutor and position.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'company_id' => 'required|exists:companies,id',
            'tutor_id' => 'required|exists:users,id',
            'position' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ], [
            'student_id.required' => 'The student field is required.',
            'student_id.exists' => 'The selected student does not exist.',
            'company_id.required' => 'The company field is required.',
            'company_id.exists' => 'The selected company does not exist.',
            'tutor_id.required' => 'The tutor field is required.',
            'tutor_id.exists' => 'The selected tutor does not exist.',
            'position.required' => 'The position field is required.',
            'position.string' => 'The position must be a string.',
            'position.max' => 'The position may not exceed 255 characters.',
            'start_date.required' => 'The start date field is required.',
            'start_date.date' => 'The start date must be a valid date.',
            'end_date.required' => 'The end date field is required.',
            'end_date.date' => 'The end date must be a valid date.',
            'end_date.after' => 'The end date must be after the start date.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $assignment = InternshipAssignment::create([
            'student_id' => $request->student_id,
            'company_id' => $request->company_id,
            'tutor_id' => $request->tutor_id,
            'position' => $request->position,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'Assigned',
        ]);

        return response()->json([
            'data' => $assignment->load(['student', 'company', 'tutor']),
            'message' => 'Internship assignment created successfully.'
        ], 201);
    }

    /**
     * Update the specified internship assignment.
     * Admin reassigns a student to a different company/tutor, or updates assignment status.
     */
    public function update(Request $request, InternshipAssignment $assignment)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'sometimes|exists:students,id',
            'company_id' => 'sometimes|exists:companies,id',
            'tutor_id' => 'sometimes|exists:users,id',
            'position' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'status' => 'sometimes|in:Assigned,In Progress,Completed,Terminated',
        ], [
            'student_id.exists' => 'The selected student does not exist.',
            'company_id.exists' => 'The selected company does not exist.',
            'tutor_id.exists' => 'The selected tutor does not exist.',
            'position.string' => 'The position must be a string.',
            'position.max' => 'The position may not exceed 255 characters.',
            'start_date.date' => 'The start date must be a valid date.',
            'end_date.date' => 'The end date must be a valid date.',
            'end_date.after' => 'The end date must be after the start date.',
            'status.in' => 'The status must be one of: Assigned, In Progress, Completed, Terminated.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        // Validate status transitions
        if ($request->has('status')) {
            $newStatus = $request->status;
            $currentStatus = $assignment->status;

            // Define valid status transitions
            $validTransitions = [
                'Assigned' => ['In Progress', 'Terminated'],
                'In Progress' => ['Completed', 'Terminated'],
                'Completed' => [], // No transitions from Completed
                'Terminated' => ['Assigned'], // Can reassign after termination
            ];

            if (!in_array($newStatus, $validTransitions[$currentStatus])) {
                return response()->json([
                    'message' => 'Invalid status transition.',
                    'error' => "Cannot transition from '{$currentStatus}' to '{$newStatus}'."
                ], 422);
            }
        }

        $assignment->update($request->all());

        return response()->json([
            'data' => $assignment->load(['student', 'company', 'tutor']),
            'message' => 'Internship assignment updated successfully.'
        ], 200);
    }

    /**
     * Display the specified internship assignment.
     */
    public function show(InternshipAssignment $assignment)
    {
        return response()->json([
            'data' => $assignment->load(['student', 'company', 'tutor']),
            'message' => 'Internship assignment retrieved successfully.',
        ], 200);
    }

    /**
     * Remove the specified internship assignment.
     */
    public function destroy(InternshipAssignment $assignment)
    {
        $assignment->delete();

        return response()->json([
            'message' => 'Internship assignment deleted successfully.'
        ], 200);
    }
}
