<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class UsersExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    public function collection()
    {
        return User::withTrashed()->with('role')->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'First Name',
            'Last Name',
            'Email',
            'Role',
            'Status',
            'Created At',
        ];
    }

    public function map($user): array
    {
        return [
            $user->first_name,
            $user->last_name,
            $user->email,
            $user->role?->name ? ucfirst($user->role->name) : '—',
            $user->trashed() ? 'Deactivated' : 'Active',
            $user->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                $sheet->setCellValue('A1', 'Users List');
                $sheet->mergeCells('A1:F1');
                $sheet->getDelegate()->getStyle('A1')->getFont()->setBold(true)->setSize(13);

                $sheet->setCellValue('A2', 'Generated: ' . now()->format('Y-m-d H:i:s'));
                $sheet->mergeCells('A2:F2');

                $sheet->getDelegate()->getStyle('A4:F4')->getFont()->setBold(true);
                $sheet->getDelegate()->getStyle('A4:F4')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE0E0E0');

                $lastRow = $sheet->getDelegate()->getHighestRow();
                $sheet->getDelegate()->getStyle("A4:F{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                foreach (range('A', 'F') as $col) {
                    $sheet->getDelegate()->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}
