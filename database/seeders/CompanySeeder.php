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
                'email' => 'info@techsolutions-kh.com',
                'website' => 'https://techsolutions-kh.com',
                'company_image' => '/images/companies/tech-solutions.png',
            ],
            [
                'company_name' => 'Digital Innovation Lab',
                'address' => 'Siem Reap, Cambodia',
                'industry' => 'Digital Marketing',
                'email' => 'contact@digital-lab-kh.com',
                'website' => 'https://digital-lab-kh.com',
                'company_image' => '/images/companies/digital-lab.png',
            ],
        ];

        foreach ($companies as $data) {
            Company::firstOrCreate(
                ['company_name' => $data['company_name']],
                $data
            );
        }
    }
}
