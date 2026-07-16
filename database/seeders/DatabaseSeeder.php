<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,         // roles first
            BatchSeeder::class,        // batches (independent)
            AdminUserSeeder::class,    // admin, tutor, company users (depends on roles)
            TutorSeeder::class,        // additional tutors (depends on roles)
            CompanySeeder::class,      // companies (depends on company user)
            StudentUserSeeder::class,  // student users (depends on roles)
        ]);
    }
}