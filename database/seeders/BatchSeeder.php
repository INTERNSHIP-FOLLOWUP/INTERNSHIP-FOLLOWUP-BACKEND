<?php

namespace Database\Seeders;

use App\Models\Batch;
use Illuminate\Database\Seeder;

class BatchSeeder extends Seeder
{
    public function run(): void
    {
        $batches = [
            ['batch_name' => 'Batch 2025-A', 'year' => '2025'],
            ['batch_name' => 'Batch 2025-B', 'year' => '2025'],
            ['batch_name' => 'Batch 2026-A', 'year' => '2026'],
            ['batch_name' => 'Batch 2026-B', 'year' => '2026'],
        ];

        foreach ($batches as $batch) {
            Batch::firstOrCreate(
                ['batch_name' => $batch['batch_name']],
                $batch
            );
        }
    }
}
