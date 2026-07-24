<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InternshipAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Generate and retrieve report data with filters
     */
    public function index(Request $request)
    {
        try {
            $query = InternshipAssignment::with([
                'student.user',
                'student.batch',
                'supervisor.company',
                'tutor',
            ]);

            // Apply filters
            $this->applyFilters($query, $request);

            // Get assignments
            $assignments = $query->get();

            // Calculate summary statistics
            $summary = $this->calculateSummary($assignments);

            // Get students per batch
            $studentsPerBatch = $this->getStudentsPerBatch($assignments);

            // Get students per company
            $studentsPerCompany = $this->getStudentsPerCompany($assignments);

            // Format assignments for response
            $formattedAssignments = $this->formatAssignments($assignments);

            return response()->json([
                'summary' => $summary,
                'students_per_batch' => $studentsPerBatch,
                'students_per_company' => $studentsPerCompany,
                'assignments' => $formattedAssignments,
                'metadata' => [
                    'total_assignments' => $assignments->count(),
                    'filters_applied' => $this->getActiveFilters($request),
                    'generated_at' => Carbon::now()->toDateTimeString(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Report generation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to generate report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply filters to the query
     */
    private function applyFilters($query, Request $request)
    {
        if ($request->filled('batch_id')) {
            $query->whereHas('student.batch', function ($q) use ($request) {
                $q->where('id', $request->batch_id);
            });
        }

        if ($request->filled('company_supervisors_id')) {
            $query->where('company_supervisors_id', $request->company_supervisors_id);
        }

        if ($request->filled('tutor_id')) {
            $query->where('tutor_id', $request->tutor_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('start_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('end_date', '<=', $request->date_to);
        }

        // Additional filters for enhanced reporting
        if ($request->filled('department')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('department', $request->department);
            });
        }

        if ($request->filled('min_duration')) {
            $query->whereRaw('DATEDIFF(end_date, start_date) >= ?', [$request->min_duration]);
        }

        if ($request->filled('max_duration')) {
            $query->whereRaw('DATEDIFF(end_date, start_date) <= ?', [$request->max_duration]);
        }
    }

    /**
     * Calculate summary statistics
     */
    private function calculateSummary($assignments)
    {
        $total = $assignments->count();
        $assigned = $assignments->where('status', 'Assigned')->count();
        $inProgress = $assignments->where('status', 'In Progress')->count();
        $completed = $assignments->where('status', 'Completed')->count();
        $terminated = $assignments->where('status', 'Terminated')->count();

        // Calculate average duration
        $avgDuration = $assignments->avg(function ($assignment) {
            return Carbon::parse($assignment->start_date)
                ->diffInDays(Carbon::parse($assignment->end_date));
        });

        // Calculate completion rate
        $completionRate = $total > 0
            ? round(($completed / $total) * 100, 1)
            : 0;

        return [
            'total_assignments' => $total,
            'assigned' => $assigned,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'terminated' => $terminated,
            'avg_duration_days' => round($avgDuration ?? 0, 1),
            'completion_rate' => $completionRate,
            'active_assignments' => $assigned + $inProgress,
        ];
    }

    /**
     * Get students per batch with enhanced data
     */
    private function getStudentsPerBatch($assignments)
    {
        return $assignments
            ->groupBy(function ($assignment) {
                return optional(optional($assignment->student)->batch)->id ?? 'unassigned';
            })
            ->map(function ($group) {
                $batch = optional(optional($group->first()->student)->batch);
                return [
                    'batch_id' => $batch->id ?? null,
                    'batch' => $batch->batch_name ?? 'Unassigned',
                    'year' => $batch->year ?? null,
                    'student_count' => $group->count(),
                    'active_count' => $group->whereIn('status', ['Assigned', 'In Progress'])->count(),
                    'completed_count' => $group->where('status', 'Completed')->count(),
                ];
            })
            ->values()
            ->sortByDesc('student_count')
            ->values()
            ->toArray();
    }

    /**
     * Get students per company with enhanced data
     */
    private function getStudentsPerCompany($assignments)
    {
        return $assignments
            ->groupBy(function ($a) { return $a->supervisor?->company_id; })
            ->map(function ($group) {
                $company = optional($group->first()->supervisor?->company);
                $first = $group->first();
                return [
                    'company_supervisors_id' => $first->company_supervisors_id,
                    'company' => $company->company_name ?? 'Unknown',
                    'industry' => $company->industry ?? null,
                    'assigned_count' => $group->count(),
                    'active_count' => $group->whereIn('status', ['Assigned', 'In Progress'])->count(),
                    'completed_count' => $group->where('status', 'Completed')->count(),
                    'terminated_count' => $group->where('status', 'Terminated')->count(),
                ];
            })
            ->values()
            ->sortByDesc('assigned_count')
            ->values()
            ->toArray();
    }

    /**
     * Format assignments for response
     */
    private function formatAssignments($assignments)
    {
        return $assignments->map(function ($assignment) {
            $student = optional($assignment->student);
            $batch = optional($student->batch);
            $company = optional($assignment->supervisor?->company);
            $tutor = optional($assignment->tutor);
            return [
                'id' => $assignment->id,
                'student' => optional($student->user)->name ?? 'Unknown',
                'student_code' => $student->student_code ?? 'N/A',
                'student_id' => $assignment->student_id,
                'batch' => $batch->batch_name ?? 'N/A',
                'batch_year' => $batch->year ?? null,
                'company' => $company->company_name ?? 'Unknown',
                'company_supervisors_id' => $assignment->company_supervisors_id,
                'tutor' => $tutor->name ?? 'Unassigned',
                'tutor_id' => $assignment->tutor_id,
                'position' => $assignment->position ?? 'N/A',
                'status' => $assignment->status,
                'start_date' => $assignment->start_date ? Carbon::parse($assignment->start_date)->format('Y-m-d') : null,
                'end_date' => $assignment->end_date ? Carbon::parse($assignment->end_date)->format('Y-m-d') : null,
                'duration_days' => $assignment->start_date && $assignment->end_date
                    ? Carbon::parse($assignment->start_date)->diffInDays(Carbon::parse($assignment->end_date))
                    : 0,
                'created_at' => $assignment->created_at?->toDateTimeString(),
                'updated_at' => $assignment->updated_at?->toDateTimeString(),
            ];
        })->toArray();
    }

    /**
     * Get active filters for metadata
     */
    private function getActiveFilters(Request $request)
    {
        $filters = [];
        $filterMap = [
            'batch_id' => 'Batch',
            'company_supervisors_id' => 'Company Supervisor',
            'tutor_id' => 'Tutor',
            'status' => 'Status',
            'date_from' => 'Date From',
            'date_to' => 'Date To',
            'department' => 'Department',
            'min_duration' => 'Min Duration',
            'max_duration' => 'Max Duration',
        ];

        foreach ($filterMap as $key => $label) {
            if ($request->filled($key)) {
                $filters[$label] = $request->$key;
            }
        }

        return $filters;
    }

    /**
     * Export report as PDF
     */
    public function exportPdf(Request $request)
    {
        try {
            // Get report data
            $reportData = $this->getReportData($request);

            // Generate PDF
            $pdf = Pdf::loadView('reports.internship-performance', [
                'report' => $reportData,
                'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'filters' => $this->getActiveFilters($request),
            ]);

            // Set paper size and orientation
            $pdf->setPaper('A4', 'landscape');

            // Return PDF download
            return $pdf->download('internship-performance-report-' . Carbon::now()->format('Y-m-d') . '.pdf');

        } catch (\Exception $e) {
            Log::error('PDF export failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to export PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export report as Excel
     */
    public function exportExcel(Request $request)
    {
        try {
            // Get report data
            $reportData = $this->getReportData($request);

            // Create spreadsheet
            $spreadsheet = new Spreadsheet();
            $spreadsheet->removeSheetByIndex(0);

            // Create sheets
            $this->createSummarySheet($spreadsheet, $reportData);
            $this->createAssignmentsSheet($spreadsheet, $reportData);
            $this->createBatchSheet($spreadsheet, $reportData);
            $this->createCompanySheet($spreadsheet, $reportData);

            // Set active sheet to first
            $spreadsheet->setActiveSheetIndex(0);

            // Create writer
            $writer = new Xlsx($spreadsheet);

            // Set response headers
            $filename = 'internship-performance-report-' . Carbon::now()->format('Y-m-d') . '.xlsx';

            // Save to output
            $response = new StreamedResponse(function() use ($writer) {
                $writer->save('php://output');
            });

            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->headers->set('Cache-Control', 'max-age=0');

            return $response;

        } catch (\Exception $e) {
            Log::error('Excel export failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to export Excel',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get report data for export
     */
    private function getReportData(Request $request): array
    {
        $query = InternshipAssignment::with([
            'student.user',
            'student.batch',
            'supervisor.company',
            'tutor',
        ]);

        $this->applyFilters($query, $request);

        $assignments = $query->get();

        return [
            'summary' => $this->calculateSummary($assignments),
            'students_per_batch' => $this->getStudentsPerBatch($assignments),
            'students_per_company' => $this->getStudentsPerCompany($assignments),
            'assignments' => $this->formatAssignments($assignments),
            'filters' => $this->getActiveFilters($request),
            'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Create summary sheet in Excel
     */
    private function createSummarySheet($spreadsheet, $reportData)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Summary');

        // Set title
        $sheet->setCellValue('A1', 'Internship Performance Report');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Generated at
        $sheet->setCellValue('A2', 'Generated: ' . $reportData['generated_at']);
        $sheet->mergeCells('A2:D2');
        $sheet->getStyle('A2')->getFont()->setSize(10);

        // Summary headers
        $row = 4;
        $sheet->setCellValue("A{$row}", 'Metric');
        $sheet->setCellValue("B{$row}", 'Value');
        $sheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:B{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Summary data
        $row = 5;
        foreach ($reportData['summary'] as $key => $value) {
            $sheet->setCellValue("A{$row}", ucwords(str_replace('_', ' ', $key)));
            $sheet->setCellValue("B{$row}", $value);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'B') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Create assignments sheet in Excel
     */
    private function createAssignmentsSheet($spreadsheet, $reportData)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Assignments');

        // Headers
        $headers = [
            'Student', 'Student Code', 'Batch', 'Company', 'Tutor',
            'Position', 'Status', 'Start Date', 'End Date', 'Duration (Days)'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            $col++;
        }

        // Data
        $row = 2;
        foreach ($reportData['assignments'] as $assignment) {
            $col = 'A';
            $sheet->setCellValue($col++ . $row, $assignment['student']);
            $sheet->setCellValue($col++ . $row, $assignment['student_code']);
            $sheet->setCellValue($col++ . $row, $assignment['batch']);
            $sheet->setCellValue($col++ . $row, $assignment['company']);
            $sheet->setCellValue($col++ . $row, $assignment['tutor']);
            $sheet->setCellValue($col++ . $row, $assignment['position']);
            $sheet->setCellValue($col++ . $row, $assignment['status']);
            $sheet->setCellValue($col++ . $row, $assignment['start_date']);
            $sheet->setCellValue($col++ . $row, $assignment['end_date']);
            $sheet->setCellValue($col++ . $row, $assignment['duration_days']);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add borders
        $sheet->getStyle('A1:J' . ($row - 1))
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    /**
     * Create batch sheet in Excel
     */
    private function createBatchSheet($spreadsheet, $reportData)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Students per Batch');

        // Headers
        $headers = ['Batch', 'Year', 'Total Students', 'Active', 'Completed'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            $col++;
        }

        // Data
        $row = 2;
        foreach ($reportData['students_per_batch'] as $batch) {
            $col = 'A';
            $sheet->setCellValue($col++ . $row, $batch['batch']);
            $sheet->setCellValue($col++ . $row, $batch['year']);
            $sheet->setCellValue($col++ . $row, $batch['student_count']);
            $sheet->setCellValue($col++ . $row, $batch['active_count']);
            $sheet->setCellValue($col++ . $row, $batch['completed_count']);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add borders
        $sheet->getStyle('A1:E' . ($row - 1))
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    /**
     * Create company sheet in Excel
     */
    private function createCompanySheet($spreadsheet, $reportData)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Students per Company');

        // Headers
        $headers = ['Company', 'Industry', 'Total Students', 'Active', 'Completed', 'Terminated'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            $col++;
        }

        // Data
        $row = 2;
        foreach ($reportData['students_per_company'] as $company) {
            $col = 'A';
            $sheet->setCellValue($col++ . $row, $company['company']);
            $sheet->setCellValue($col++ . $row, $company['industry'] ?? 'N/A');
            $sheet->setCellValue($col++ . $row, $company['assigned_count']);
            $sheet->setCellValue($col++ . $row, $company['active_count']);
            $sheet->setCellValue($col++ . $row, $company['completed_count']);
            $sheet->setCellValue($col++ . $row, $company['terminated_count']);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add borders
        $sheet->getStyle('A1:F' . ($row - 1))
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }
}
