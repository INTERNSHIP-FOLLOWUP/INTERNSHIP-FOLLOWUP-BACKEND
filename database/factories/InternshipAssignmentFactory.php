<?php

namespace Database\Factories;

use App\Models\{Company, Student, User};
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<InternshipAssignment>
 */
class InternshipAssignmentFactory extends Factory
{
    public function definition(): array
    {
        $start = Carbon::now()->subMonths(fake()->numberBetween(1, 5))->startOfMonth()->toDateString();
        $end = Carbon::parse($start)->addMonths(fake()->numberBetween(1, 4))->endOfMonth()->toDateString();

        return [
            'student_id' => Student::inRandomOrder()->first()?->id,
            'company_id' => Company::inRandomOrder()->first()?->id,
            'tutor_id'   => User::where('role', 'tutor')->inRandomOrder()->first()?->id,
            'position'   => fake()->randomElement([
                'Frontend Developer Intern',
                'Backend Developer Intern',
                'Marketing Intern',
                'QA Intern',
                'Design Intern',
            ]),
            'start_date' => $start,
            'end_date'   => $end,
            'status'     => fake()->randomElement(['Assigned', 'In Progress', 'In Progress', 'Completed', 'Terminated']),
        ];
    }
}
