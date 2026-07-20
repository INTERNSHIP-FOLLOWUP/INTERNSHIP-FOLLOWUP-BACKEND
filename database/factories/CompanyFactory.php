<?php

namespace Database\Factories;

use App\Models\Batch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        $industry = fake()->randomElement([
            'Information Technology',
            'Digital Marketing',
            'FinTech',
            'E-Commerce',
            'Education Technology',
            'Healthcare',
            'Logistics',
            'Telecommunications',
            'Agriculture Technology',
        ]);

        return [
            'company_name'          => fake()->company(),
            'address'               => fake()->address(),
            'industry'              => $industry,
            'contact_person'        => fake()->name(),
            'phone'                 => fake()->phoneNumber(),
            'email'                 => fake()->unique()->safeEmail(),
            'website'               => 'https://' . preg_replace('/[^a-z0-9]/', '', strtolower(fake()->word())) . '.com',
            'company_profile_image' => null,
            'telegram_link'         => fake()->optional()->url(),
            'password'              => fake()->optional()->password(),
            'role'                  => fake()->optional()->randomElement(['company', 'buyer']),
        ];
    }
}
