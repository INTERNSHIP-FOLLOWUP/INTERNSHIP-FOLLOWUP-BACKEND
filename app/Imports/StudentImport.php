<?php

namespace App\Imports;

use App\Models\Batch;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class StudentImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use Importable, SkipsFailures;

    private int $imported = 0;
    private array $errors = [];
    private int $currentRow = 1;
    private int $codeCounter = 0;

    public function __construct()
    {
        $lastStudent = Student::withTrashed()
            ->where('student_code', 'like', 'STU%')
            ->orderByRaw('LENGTH(student_code) DESC, student_code DESC')
            ->first();
        if ($lastStudent) {
            $num = ltrim(substr($lastStudent->student_code, 3), '0');
            $this->codeCounter = $num !== '' ? (int) $num : 0;
        }
    }

    private function getValue(array $row, array $possibleKeys): ?string
    {
        foreach ($possibleKeys as $key) {
            if (isset($row[$key]) && !empty(trim((string)$row[$key]))) {
                return trim((string)$row[$key]);
            }
        }
        return null;
    }

    public function model(array $row)
    {
        $this->currentRow++;

        // Map column names to support different variations (with/without spaces, case-insensitive)
        $firstName = $this->getValue($row, ['first_name', 'firstname', 'First Name']);
        $lastName = $this->getValue($row, ['last_name', 'lastname', 'Last Name']);
        $email = $this->getValue($row, ['email', 'Email']);
        $phone = $this->getValue($row, ['phone', 'Phone']);
        $gender = $this->getValue($row, ['gender', 'Gender']) ?: 'Other';

        // Support different batch column names
        $batchName = $this->getValue($row, ['batches', 'batch', 'batch_name', 'Batches', 'Batch', 'Batch Name']);

        // Validate required fields
        if (empty($firstName) || empty($lastName) || empty($email) || empty($batchName)) {
            $this->errors[] = [
                'row' => $this->currentRow,
                'reason' => 'First name, last name, email, and batches are required fields',
            ];
            return null;
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = [
                'row' => $this->currentRow,
                'reason' => "Email '{$email}' is not valid",
            ];
            return null;
        }

        // Check email uniqueness
        if (User::where('email', $email)->exists()) {
            $this->errors[] = [
                'row' => $this->currentRow,
                'reason' => "Email '{$email}' already exists",
            ];
            return null;
        }

        // Get student role
        $role = Role::where('name', 'student')->first();
        if (!$role) {
            $this->errors[] = [
                'row' => $this->currentRow,
                'reason' => 'Student role not found in the system',
            ];
            return null;
        }

        // Look up batch by batch_name
        $batch = Batch::where('batch_name', $batchName)->first();
        if (!$batch) {
            $this->errors[] = [
                'row' => $this->currentRow,
                'reason' => "Batch '{$batchName}' not found",
            ];
            return null;
        }

        // Generate sequential student code
        $studentCode = $this->generateStudentCode();

        // Wrap in DB transaction for this row only
        try {
            DB::transaction(function () use ($firstName, $lastName, $email, $phone, $gender, $studentCode, $batch, $role) {
                // Create user record
                $user = User::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => Hash::make('12345678'),
                    'theme' => 'light',
                    'role_id' => $role->id,
                ]);

                // Create student record
                Student::create([
                    'user_id' => $user->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => $phone,
                    'gender' => $gender,
                    'student_code' => $studentCode,
                    'batch_id' => $batch->id,
                    'status' => 'active',
                ]);

                $this->imported++;
            });
        } catch (\Exception $e) {
            $this->errors[] = [
                'row' => $this->currentRow,
                'reason' => "Failed to import row: " . $e->getMessage(),
            ];
            return null;
        }

        return null;
    }

    private function generateStudentCode(): string
    {
        $this->codeCounter++;
        return 'STU' . str_pad((string)$this->codeCounter, 3, '0', STR_PAD_LEFT);
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'gender' => 'nullable|string|max:50',
            'student_code' => 'nullable|string|max:255',
        ];
    }

    public function getImportedCount(): int
    {
        return $this->imported;
    }

    public function getErrors(): array
    {
        $allErrors = $this->errors;

        foreach ($this->failures() as $failure) {
            $values = $failure->values();
            $hasData = false;
            foreach ($values as $val) {
                if ($val !== null && trim((string)$val) !== '') {
                    $hasData = true;
                    break;
                }
            }
            if (!$hasData) {
                continue;
            }
            $allErrors[] = [
                'row' => $failure->row(),
                'reason' => implode(', ', $failure->errors()),
            ];
        }

        return $allErrors;
    }

    public function getFailedCount(): int
    {
        return count($this->getErrors());
    }
}
