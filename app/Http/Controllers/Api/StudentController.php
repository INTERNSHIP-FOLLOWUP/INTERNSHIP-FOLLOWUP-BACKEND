<?php

namespace App\Http\Controllers\Api;

use App\Exports\StudentImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudentRequest;
use App\Http\Resources\StudentResource;
use App\Imports\StudentImport;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Student::with(['batch', 'tutor']);

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->filled('tutor_id')) {
            $query->where('tutor_id', $request->tutor_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('student_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('sort')) {
            match ($request->sort) {
                'name_asc' => $query->orderBy('first_name')->orderBy('last_name'),
                'name_desc' => $query->orderBy('first_name', 'desc')->orderBy('last_name', 'desc'),
                'oldest' => $query->orderBy('created_at'),
                default => $query->orderBy('created_at', 'desc'),
            };
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = min((int) $request->per_page, 100) ?: 15;
        $students = $query->paginate($perPage);

        return response()->json([
            'data' => StudentResource::collection($students->items()),
            'message' => 'Students retrieved successfully.',
            'meta' => [
                'total' => $students->total(),
                'per_page' => $students->perPage(),
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'from' => $students->firstItem(),
                'to' => $students->lastItem(),
            ],
        ]);
    }

    public function store(StudentRequest $request): JsonResponse
    {
        $data = $request->validated();

        $studentRole = Role::where('name', 'student')->first();

        $nameParts = explode(' ', $data['name'], 2);
        $user = User::create([
            'first_name' => $nameParts[0],
            'last_name'  => $nameParts[1] ?? '',
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'role_id'    => $studentRole?->id,
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('students', 'public');
        }

        $data['user_id'] = $user->id;
        $data['first_name'] = $nameParts[0];
        $data['last_name'] = $nameParts[1] ?? '';
        unset($data['name'], $data['password'], $data['password_confirmation']);

        $student = Student::create($data);

        return response()->json([
            'data' => new StudentResource($student->load(['batch', 'tutor'])),
            'message' => 'Student created successfully.',
        ], 201);
    }

    public function show(int $student): JsonResponse
    {
        $studentModel = Student::with(['batch', 'tutor'])->find($student);

        if (!$studentModel) {
            $studentModel = Student::with(['batch', 'tutor'])->where('user_id', $student)->first();
        }

        if (!$studentModel) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        return response()->json([
            'data' => new StudentResource($studentModel),
            'message' => 'Student retrieved successfully.',
        ]);
    }

    public function update(StudentRequest $request, int $student): JsonResponse
    {
        $studentModel = Student::find($student);

        if (!$studentModel) {
            $studentModel = Student::where('user_id', $student)->first();
        }

        if (!$studentModel) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        $data = $request->validated();

        if ($request->hasFile('photo')) {
            if ($studentModel->photo) {
                Storage::disk('public')->delete($studentModel->photo);
            }
            $data['photo'] = $request->file('photo')->store('students', 'public');
        }

        if (isset($data['name'])) {
            $nameParts = explode(' ', $data['name'], 2);
            $data['first_name'] = $nameParts[0];
            $data['last_name'] = $nameParts[1] ?? '';
            unset($data['name']);
        }
        unset($data['password'], $data['password_confirmation']);

        $studentModel->update($data);

        return response()->json([
            'data' => new StudentResource($student->fresh()->load(['batch', 'tutor'])),
            'message' => 'Student updated successfully.',
        ]);
    }

    public function destroy(Student $student): JsonResponse
    {
        if ($student->photo) {
            Storage::disk('public')->delete($student->photo);
        }

        if ($student->user) {
            $student->user->delete();
        }

        $student->delete();

        return response()->json([
            'message' => 'Student deleted successfully.',
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:20480', // Increased to 20MB
        ]);

        try {
            // Increase PHP limits for large imports
            set_time_limit(600); // 10 minutes
            ini_set('memory_limit', '1024M'); // 1GB memory
            ini_set('default_socket_timeout', 600); // 10 minutes socket timeout
            ini_set('max_input_time', 600); // 10 minutes input time
            
            $import = new StudentImport();
            Excel::import($import, $request->file('file'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = [
                    'row' => $failure->row(),
                    'reason' => implode(', ', $failure->errors()),
                ];
            }
            return response()->json([
                'message' => 'Import failed due to validation errors.',
                'imported' => 0,
                'failed' => count($errors),
                'errors' => $errors,
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Import failed: ' . $e->getMessage(),
                'imported' => 0,
                'failed' => 1,
                'errors' => [
                    ['row' => 0, 'reason' => $e->getMessage()],
                ],
            ], 500);
        }

        $errors = $import->getErrors();
        $importedCount = $import->getImportedCount();
        $failedCount = count($errors);

        $failedRows = array_map(function ($err) {
            return [
                'row' => $err['row'],
                'errors' => [$err['reason']],
                'reason' => $err['reason'],
            ];
        }, $errors);

        // Generate appropriate message based on results
        if ($failedCount === 0 && $importedCount > 0) {
            $message = "Import completed successfully. {$importedCount} students imported.";
        } elseif ($importedCount === 0 && $failedCount > 0) {
            $message = "Import failed. All {$failedCount} rows could not be imported.";
        } elseif ($importedCount > 0 && $failedCount > 0) {
            $message = "Import completed with warnings. {$importedCount} students imported, {$failedCount} rows failed.";
        } else {
            $message = "Import completed. No data found to import.";
        }

        return response()->json([
            'message' => $message,
            'imported' => $importedCount,
            'failed' => $failedCount,
            'errors' => $errors,
            'success_count' => $importedCount,
            'failed_count' => $failedCount,
            'failed_rows' => $failedRows,
        ]);
    }

    public function importTemplate()
    {
        return Excel::download(new StudentImportTemplateExport, 'student-import-template.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = Student::with(['batch', 'tutor']);

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('student_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('first_name')->get();

        $pdf = Pdf::loadView('students.list', [
            'students' => $students,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ]);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download("students-list-" . now()->format('Y-m-d') . ".pdf");
    }

    public function exportExcel(Request $request)
    {
        $query = Student::with(['batch', 'tutor']);

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('student_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('first_name')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Students');

        $sheet->setCellValue('A1', 'Students List');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $sheet->setCellValue('A2', 'Generated: ' . now()->format('Y-m-d H:i:s'));
        $sheet->mergeCells('A2:H2');

        $headers = ['Student Code', 'First Name', 'Last Name', 'Email', 'Gender', 'Phone', 'Batch', 'Tutor'];
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
            $sheet->setCellValue("A{$row}", $student->student_code ?? 'N/A');
            $sheet->setCellValue("B{$row}", $student->first_name);
            $sheet->setCellValue("C{$row}", $student->last_name);
            $sheet->setCellValue("D{$row}", $student->email ?? 'N/A');
            $sheet->setCellValue("E{$row}", $student->gender ?? 'N/A');
            $sheet->setCellValue("F{$row}", $student->phone ?? '');
            $sheet->setCellValue("G{$row}", $student->batch?->batch_name ?? 'N/A');
            $sheet->setCellValue("H{$row}", $student->tutor?->name ?? 'N/A');
            $row++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getStyle("A4:H{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $writer = new Xlsx($spreadsheet);
        $filename = "students-list-" . now()->format('Y-m-d') . ".xlsx";

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
