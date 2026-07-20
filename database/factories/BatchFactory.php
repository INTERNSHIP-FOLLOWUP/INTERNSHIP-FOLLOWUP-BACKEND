<?php

namespace Database\Factories;

use App\Models\Batch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Batch>
 */
class BatchFactory extends Factory
{
    public function definition(): array
    {
        $baseYear = now()->year;
        $year = fake()->randomElement([$baseYear - 1, $baseYear, $baseYear + 1]);
        $label = fake()->randomElement(['A', 'B', 'C']);
        $batchName = 'Batch ' . $year . '-' . $label;

        return [
            'batch_name' => $batchName,
            'year'       => (string) $year,
        ];
    }
}
