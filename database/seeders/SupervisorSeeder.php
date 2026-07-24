<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySupervisor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class SupervisorSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::where('name', 'supervisor')->first();

        if ($role === null) {
            $this->command->warn("Role 'supervisor' not found — skipping SupervisorSeeder.");
            return;
        }

        // Fetch companies seeded by CompanySeeder
        $techSolutions = Company::where('company_name', 'Tech Solutions Cambodia')->first();
        $digitalLab = Company::where('company_name', 'Digital Innovation Lab')->first();

        $supervisors = [];

        // --- Tech Solutions Cambodia supervisors ---
        if ($techSolutions) {
            $supervisors = array_merge($supervisors, [
                [
                    'first_name' => 'Supervisor',
                    'last_name'  => 'User',
                    'email'      => 'company@gmail.com',
                    'phone'      => '012 345 678',
                    'company'    => $techSolutions,
                ],
                [
                    'first_name' => 'Sokha',
                    'last_name'  => 'Chea',
                    'email'      => 'sokha.chea@techsolutions-kh.com',
                    'phone'      => '012 111 222',
                    'company'    => $techSolutions,
                ],
                [
                    'first_name' => 'Sreyneang',
                    'last_name'  => 'Oun',
                    'email'      => 'sreyneang.oun@techsolutions-kh.com',
                    'phone'      => '012 333 444',
                    'company'    => $techSolutions,
                ],
            ]);
        }

        // --- Digital Innovation Lab supervisors ---
        if ($digitalLab) {
            $supervisors = array_merge($supervisors, [
                [
                    'first_name' => 'Rithy',
                    'last_name'  => 'Chan',
                    'email'      => 'rithy.chan@digital-lab-kh.com',
                    'phone'      => '098 765 432',
                    'company'    => $digitalLab,
                ],
                [
                    'first_name' => 'Sreymom',
                    'last_name'  => 'Kong',
                    'email'      => 'sreymom.kong@digital-lab-kh.com',
                    'phone'      => '098 111 222',
                    'company'    => $digitalLab,
                ],
            ]);
        }

        foreach ($supervisors as $data) {
            $company = $data['company'];
            unset($data['company']);

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'first_name' => $data['first_name'],
                    'last_name'  => $data['last_name'],
                    'email'      => $data['email'],
                    'password'   => '12345678',
                    'role_id'    => $role->id,
                    'must_change_password' => true,
                    'theme'      => 'light',
                    'phone'      => $data['phone'] ?? null,
                    'status'     => 'active',
                ]
            );

            CompanySupervisor::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'user_id'    => $user->id,
                    'company_id' => $company->id,
                ]
            );
        }

        $count = count($supervisors);
        $this->command->info("Seeded {$count} supervisor(s) across companies.");
    }
}
