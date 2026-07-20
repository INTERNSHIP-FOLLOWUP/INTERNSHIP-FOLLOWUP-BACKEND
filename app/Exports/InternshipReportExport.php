<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class InternshipReportExport implements FromArray, WithHeadings, WithTitle
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return $this->data['assignments'] ?? [];
    }

    public function headings(): array
    {
        return ['Student', 'Code', 'Batch', 'Company', 'Tutor', 'Position', 'Status', 'Start', 'End'];
    }

    public function title(): string
    {
        return 'Internship Assignments';
    }
}
