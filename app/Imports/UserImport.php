<?php

namespace App\Imports;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class UserImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use Importable, SkipsFailures;

    private array $imported = [];
    private int $row = 1;

    public function model(array $row)
    {
        $this->row++;

        if (User::where('email', $row['email'])->exists()) {
            return null;
        }

        $role = Role::where('name', strtolower($row['role']))->first();
        if (!$role) {
            return null;
        }

        $user = User::create([
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            'password' => Hash::make('12345678'),
            'role_id' => $role->id,
        ]);

        $this->imported[] = $row['email'];

        return $user;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role' => 'required|in:admin,tutor,student,supervisor',
        ];
    }

    public function getImportedCount(): int
    {
        return count($this->imported);
    }
}
