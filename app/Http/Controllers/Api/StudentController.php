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

        $perPage = min((int) $request->per_page, 100) ?: 15;
        $students = $query->orderBy('created_at', 'desc')->paginate($perPage);

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

    public function show(Student $student): JsonResponse
    {
        return response()->json([
            'data' => new StudentResource($student->load(['batch', 'tutor'])),
            'message' => 'Student retrieved successfully.',
        ]);
    }

    public function update(StudentRequest $request, Student $student): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            if ($student->photo) {
                Storage::disk('public')->delete($student->photo);
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

        $student->update($data);

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
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new StudentImport();
        Excel::import($import, $request->file('file'));

        $failures = $import->failures();
        $failedRows = [];

        foreach ($failures as $failure) {
            $failedRows[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
        }

        return response()->json([
            'message' => 'Import completed.',
            'success_count' => $import->getImportedCount(),
            'failed_count' => count($failures),
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
