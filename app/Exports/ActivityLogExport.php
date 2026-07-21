<?php

namespace App\Exports;

use App\Models\ActivityLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ActivityLogExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private array $filters = []) {}

    public function collection()
    {
        $query = ActivityLog::with(['user', 'boat']);

        if (!empty($this->filters['action'])) {
            $query->where('action', $this->filters['action']);
        }
        if (!empty($this->filters['date_from'])) {
            $query->where('created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->where('created_at', '<=', $this->filters['date_to'] . ' 23:59:59');
        }

        return $query->latest('created_at')->get();
    }

    public function headings(): array
    {
        return ['ID', 'User', 'Action', 'Details', 'IP Address', 'Created At'];
    }

    public function map($log): array
    {
        return [
            $log->id,
            $log->user?->name ?? 'System',
            $log->action,
            $log->details,
            $log->ip_address,
            $log->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
