<?php

namespace App\Exports;

use App\Models\Rental;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RentalsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private array $filters = []) {}

    public function collection()
    {
        $query = Rental::with(['boat', 'worker', 'endedBy']);

        if (!empty($this->filters['date_from'])) {
            $query->where('started_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->where('started_at', '<=', $this->filters['date_to'] . ' 23:59:59');
        }
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        if (!empty($this->filters['boat_id'])) {
            $query->where('boat_id', $this->filters['boat_id']);
        }
        if (!empty($this->filters['worker_id'])) {
            $query->where('worker_id', $this->filters['worker_id']);
        }

        return $query->latest('started_at')->get();
    }

    public function headings(): array
    {
        return [
            'ID', 'Boat #', 'Boat Name', 'Worker', 'Started At',
            'Expected End', 'Ended At', 'Duration (min)', 'Overtime (s)',
            'Status', 'Customer Returned', 'Notes'
        ];
    }

    public function map($rental): array
    {
        return [
            $rental->id,
            $rental->boat?->boat_number,
            $rental->boat?->name,
            $rental->worker?->name,
            $rental->started_at?->format('Y-m-d H:i:s'),
            $rental->expected_end_at?->format('Y-m-d H:i:s'),
            $rental->actual_end_at?->format('Y-m-d H:i:s'),
            $rental->started_at ? $rental->started_at->diffInMinutes($rental->actual_end_at ?? now()) : 0,
            $rental->overtime_seconds,
            $rental->status->label(),
            $rental->customer_returned ? 'Yes' : 'No',
            $rental->notes,
        ];
    }
}
