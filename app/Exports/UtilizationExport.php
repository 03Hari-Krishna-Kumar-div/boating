<?php

namespace App\Exports;

use App\Models\Boat;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UtilizationExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private array $filters = []) {}

    public function collection()
    {
        return Boat::withCount('rentals')->get();
    }

    public function headings(): array
    {
        return ['Boat #', 'Name', 'Total Rentals', 'Status', 'Created'];
    }

    public function map($boat): array
    {
        return [
            $boat->boat_number,
            $boat->name,
            $boat->rentals_count,
            $boat->status->label(),
            $boat->created_at?->format('Y-m-d'),
        ];
    }
}
