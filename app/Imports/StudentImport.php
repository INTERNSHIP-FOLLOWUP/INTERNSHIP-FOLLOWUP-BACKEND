<?php

namespace App\Imports;

use App\Models\Batch;
use App\Models\Role;
use App\Models\Student;
use App\Models\Tutor;
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

    private array $imported = [];

    public function model(array $row)
    {
        if (empty($row['email'])) {
            return null;
        }

        if (User::where('email', $row['email'])->exists()) {
            return null;
        }

        $firstName = trim($row['first_name'] ?? '');
        $lastName = trim($row['last_name'] ?? '');

        if (empty($firstName) && empty($lastName)) {
            return null;
        }

        $role = Role::where('name', 'student')->first();
        if (!$role) {
            return null;
        }

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $row['email'],
            'password' => Hash::make('12345678'),
            'role_id' => $role->id,
        ]);

        $batchId = null;
        if (!empty($row['batch'])) {
            $batch = Batch::where('batch_name', $row['batch'])->first();
            $batchId = $batch?->id;
        }

        $tutorId = null;
        if (!empty($row['tutor'])) {
            $name = trim($row['tutor']);
            $tutor = Tutor::where(function ($q) use ($name) {
                    $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$name}%")
                      ->orWhere('first_name', 'like', "%{$name}%")
                      ->orWhere('last_name', 'like', "%{$name}%");
                })
                ->first();
            $tutorId = $tutor?->id;
        }

        $code = !empty($row['student_code']) ? $row['student_code'] : 'STU-' . str_pad(Student::max('id') + 1, 3, '0', STR_PAD_LEFT);

        Student::create([
            'user_id' => $user->id,
            'student_code' => $code,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $row['email'],
            'gender' => !empty($row['gender']) ? $row['gender'] : 'Other',
            'phone' => $row['phone'] ?? null,
            'batch_id' => $batchId,
            'tutor_id' => $tutorId,
            'status' => 'active',
        ]);

        $this->imported[] = $row['email'];

        return $user;
    }

    public function rules(): array
    {
        return [
            'student_code' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'gender' => 'nullable|string|in:Male,Female,Other',
            'phone' => 'nullable|string|max:20',
            'batch' => 'nullable|string|max:255',
            'tutor' => 'nullable|string|max:255',
        ];
    }

    public function getImportedCount(): int
    {
        return count($this->imported);
    }
}
