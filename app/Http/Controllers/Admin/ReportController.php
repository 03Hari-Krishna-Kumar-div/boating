<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Services\ExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService,
        private ExportService $exportService
    ) {}

    public function index(Request $request)
    {
        $type = $request->get('type', 'daily');
        $data = [];

        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        switch ($type) {
            case 'daily':
                $date = $request->get('date') ? Carbon::parse($request->get('date')) : now();
                $data = $this->reportService->daily($date);
                break;
            case 'weekly':
                $start = $request->get('date') ? Carbon::parse($request->get('date'))->startOfWeek() : now()->startOfWeek();
                $data = $this->reportService->weekly($start);
                break;
            case 'monthly':
                $year = $request->get('year', now()->year);
                $month = $request->get('month', now()->month);
                $data = $this->reportService->monthly((int)$year, (int)$month);
                break;
            case 'utilization':
                $start = Carbon::parse($dateFrom);
                $end = Carbon::parse($dateTo)->endOfDay();
                $data['utilization'] = $this->reportService->utilization($start, $end);
                $data['date_from'] = $dateFrom;
                $data['date_to'] = $dateTo;
                break;
            case 'worker_performance':
                $start = Carbon::parse($dateFrom);
                $end = Carbon::parse($dateTo)->endOfDay();
                $data['performance'] = $this->reportService->workerPerformance($start, $end);
                $data['date_from'] = $dateFrom;
                $data['date_to'] = $dateTo;
                break;
            case 'maintenance':
                $start = Carbon::parse($dateFrom);
                $end = Carbon::parse($dateTo)->endOfDay();
                $data['maintenance'] = $this->reportService->maintenanceHistory($start, $end);
                $data['date_from'] = $dateFrom;
                $data['date_to'] = $dateTo;
                break;
        }

        $data['type'] = $type;
        return view('admin.reports.index', $data);
    }

    public function export(Request $request, string $type, string $format)
    {
        $filters = $request->only(['date_from', 'date_to', 'status', 'boat_id', 'worker_id']);
        $filename = "brms-{$type}-report-" . now()->format('Y-m-d');

        return match ($format) {
            'pdf' => $this->exportService->pdf("admin.reports.export-{$type}", $filters, $filename),
            'excel' => $this->exportService->excel($type === 'utilization' ? 'utilization' : 'rentals', $filters, $filename),
            'csv' => $this->exportService->csv($type === 'utilization' ? 'utilization' : 'rentals', $filters, $filename),
            default => abort(400, 'Invalid export format'),
        };
    }
}
