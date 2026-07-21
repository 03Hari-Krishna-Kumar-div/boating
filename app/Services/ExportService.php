<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;
use App\Exports\RentalsExport;
use App\Exports\ActivityLogExport;
use App\Exports\UtilizationExport;

class ExportService
{
    public function pdf(string $view, array $data, string $filename): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView($view, $data);
        return $pdf->download($filename . '.pdf');
    }

    public function excel(string $type, array $filters, string $filename): \Illuminate\Http\Response
    {
        $export = match ($type) {
            'rentals' => new RentalsExport($filters),
            'activity_logs' => new ActivityLogExport($filters),
            'utilization' => new UtilizationExport($filters),
            default => throw new \InvalidArgumentException("Unknown export type: {$type}"),
        };

        return Excel::download($export, $filename . '.xlsx');
    }

    public function csv(string $type, array $filters, string $filename): \Illuminate\Http\Response
    {
        $export = match ($type) {
            'rentals' => new RentalsExport($filters),
            'activity_logs' => new ActivityLogExport($filters),
            'utilization' => new UtilizationExport($filters),
            default => throw new \InvalidArgumentException("Unknown export type: {$type}"),
        };

        return Excel::download($export, $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
    }
}
