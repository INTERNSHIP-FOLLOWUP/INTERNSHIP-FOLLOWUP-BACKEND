<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BatchController extends Controller
{
    
    // Display a listing of all batches.
    public function index(Request $request)
    {
        $query = Batch::withCount('students');

        if ($request->has('year') && $request->year !== '') {
            $query->where('year', $request->year);
        }

        $batches = $query->get();
        
        return response()->json([
            'data' => $batches,
            'message' => 'Batches retrieved successfully.'
        ], 200);
    }

    // Seed batches (2025-2027)
    public function seed()
    {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'BatchSeeder', '--force' => true]);
        
        $batches = Batch::withCount('students')->get();

        return response()->json([
            'data' => $batches,
            'message' => 'Batches seeded successfully.'
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

    /**
     * Export batch students as PDF.
     */
    public function exportPdf(Batch $batch)
    {
        $students = $batch->students()->orderBy('name')->get();

        $pdf = Pdf::loadView('batches.students-list', [
            'batch' => $batch,
            'students' => $students,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ]);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download("batch-{$batch->batch_name}-students.pdf");
    }

    /**
     * Export batch students as Excel.
     */
    public function exportExcel(Batch $batch)
    {
        $students = $batch->students()->orderBy('name')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Students');

        $sheet->setCellValue('A1', "Students - {$batch->batch_name} ({$batch->year})");
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $sheet->setCellValue('A2', 'Generated: ' . now()->format('Y-m-d H:i:s'));
        $sheet->mergeCells('A2:E2');

        $headers = ['Name', 'Student Code', 'Gender', 'Email', 'Status'];
        $col = 'A';
        foreach ($headers as $i => $header) {
            $sheet->setCellValue($col . '4', $header);
            $sheet->getStyle($col . '4')->getFont()->setBold(true);
            $sheet->getStyle($col . '4')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            $col++;
        }

        $row = 5;
        foreach ($students as $student) {
            $sheet->setCellValue("A{$row}", $student->name);
            $sheet->setCellValue("B{$row}", $student->student_code ?? 'N/A');
            $sheet->setCellValue("C{$row}", $student->gender ?? 'N/A');
            $sheet->setCellValue("D{$row}", $student->email ?? 'N/A');
            $sheet->setCellValue("E{$row}", $student->status ?? 'N/A');
            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getStyle("A4:E{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $writer = new Xlsx($spreadsheet);
        $filename = "batch-{$batch->batch_name}-students.xlsx";

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
