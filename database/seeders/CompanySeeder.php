<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                'company_name' => 'Tech Solutions Cambodia',
                'address' => 'Phnom Penh, Cambodia',
                'industry' => 'Information Technology',
                'contact_person' => 'Sokha Chea',
                'phone' => '012 345 678',
                'email' => 'info@techsolutions-kh.com',
                'website' => 'https://techsolutions-kh.com',
                'user_email' => 'company@gmail.com',
            ],
            [
                'company_name' => 'Digital Innovation Lab',
                'address' => 'Siem Reap, Cambodia',
                'industry' => 'Digital Marketing',
                'contact_person' => 'Rithy Chan',
                'phone' => '098 765 432',
                'email' => 'contact@digital-lab-kh.com',
                'website' => 'https://digital-lab-kh.com',
                'user_email' => null,
            ],
        ];

        foreach ($companies as $data) {
            $companyData = [
                'company_name' => $data['company_name'],
                'address' => $data['address'],
                'industry' => $data['industry'],
                'contact_person' => $data['contact_person'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'website' => $data['website'],
            ];

            if ($data['user_email']) {
                $user = User::where('email', $data['user_email'])->first();
                if ($user) {
                    $companyData['user_id'] = $user->id;
                }
            }

            Company::firstOrCreate(
                ['company_name' => $data['company_name']],
                $companyData
            );
        }
    }
}
