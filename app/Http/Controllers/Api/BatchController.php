<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BatchController extends Controller
{
    
    // Display a listing of all batches.
    public function index()
    {
        $batches = Batch::all();
        
        return response()->json([
            'data' => $batches,
            'message' => 'Batches retrieved successfully.'
        ], 200);
    }

  
    // Store a newly created batch in storage.
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'batch_name' => 'required|string|max:255|unique:batches,batch_name',
            'year' => 'required|string|max:4',
        ], [
            'batch_name.required' => 'The batch name field is required.',
            'batch_name.string' => 'The batch name must be a string.',
            'batch_name.max' => 'The batch name may not exceed 255 characters.',
            'batch_name.unique' => 'This batch name already exists.',
            'year.required' => 'The year field is required.',
            'year.string' => 'The year must be a string.',
            'year.max' => 'The year may not exceed 4 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $batch = Batch::create($request->all());

        return response()->json([
            'data' => $batch,
            'message' => 'Batch created successfully.'
        ], 201);
    }


    // Display the specified batch.
  
    public function show(Batch $batch)
    {
        return response()->json([
            'data' => $batch,
            'message' => 'Batch retrieved successfully.'
        ], 200);
    }

    
    // Update the specified batch in storage.
     
    public function update(Request $request, Batch $batch)
    {
        $validator = Validator::make($request->all(), [
            'batch_name' => 'required|string|max:255|unique:batches,batch_name,' . $batch->id,
            'year' => 'required|string|max:4',
        ], [
            'batch_name.required' => 'The batch name field is required.',
            'batch_name.string' => 'The batch name must be a string.',
            'batch_name.max' => 'The batch name may not exceed 255 characters.',
            'batch_name.unique' => 'This batch name already exists.',
            'year.required' => 'The year field is required.',
            'year.string' => 'The year must be a string.',
            'year.max' => 'The year may not exceed 4 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $batch->update($request->all());

        return response()->json([
            'data' => $batch,
            'message' => 'Batch updated successfully.'
        ], 200);
    }

    // Remove the specified batch from storage.
    public function destroy(Batch $batch)
    {
        $batch->delete();

        return response()->json([
            'message' => 'Batch deleted successfully.'
        ], 200);
    }

    /**
     * Get statistics for a specific batch.
     *
     * @param  \App\Models\Batch  $batch
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics(Batch $batch)
    {
        $totalStudents = $batch->students()->count();
        
        $statusBreakdown = $batch->students()
            ->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return response()->json([
            'data' => [
                'batch_id' => $batch->id,
                'batch_name' => $batch->batch_name,
                'year' => $batch->year,
                'total_students' => $totalStudents,
                'status_breakdown' => $statusBreakdown,
            ],
            'message' => 'Batch statistics retrieved successfully.'
        ], 200);
    }
}
