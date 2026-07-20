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
            StudentUserSeeder::class,
            TutorDashboardSeeder::class,
        ]);
    }
}
