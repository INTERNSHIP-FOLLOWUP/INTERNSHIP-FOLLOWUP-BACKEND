<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentImportTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            ['STU001', 'John', 'Doe', 'john.doe@example.com', 'Male', '08123456789', 'Batch 2025A', 'HEY Him'],
        ];
    }

    public function headings(): array
    {
        return [
            'Student Code',
            'First Name',
            'Last Name',
            'Email',
            'Gender',
            'Phone',
            'Batch',
            'Tutor',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
