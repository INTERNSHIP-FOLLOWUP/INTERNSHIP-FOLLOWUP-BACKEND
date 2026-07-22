<?php

namespace Database\Seeders;

use App\Models\Batch;
use Illuminate\Database\Seeder;

class BatchSeeder extends Seeder
{
    public function run(): void
    {
        $batches = [
            ['batch_name' => 'PNC2025', 'year' => '2025'],
            ['batch_name' => 'PNC2026', 'year' => '2026'],
            ['batch_name' => 'PNC2027', 'year' => '2027'],
        ];

        foreach ($batches as $batch) {
            Batch::firstOrCreate(
                ['batch_name' => $batch['batch_name']],
                $batch
            );
        }
    }
}
