<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            BatchSeeder::class,
            AdminUserSeeder::class,
            TutorSeeder::class,
            CompanySeeder::class,
            SupervisorSeeder::class,
            StudentUserSeeder::class,
            InternshipAssignmentSeeder::class,
        ]);
    }
}
