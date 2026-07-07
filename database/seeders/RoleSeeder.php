<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $roles = [
            'admin',
            'tutor', 
            'student',
            'company representative',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }
}