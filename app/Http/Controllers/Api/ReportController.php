<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Report API available. Use /api/reports/rentals or /api/reports/utilization',
        ]);
    }

    public function rentals(Request $request): JsonResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'worker_id', 'boat_id', 'status']);
        $report = $this->reportService->generateRentalReport($filters);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function utilization(Request $request): JsonResponse
    {
        $start = $request->get('date_from', now()->startOfMonth()->toDateString());
        $end = $request->get('date_to', now()->endOfMonth()->toDateString());
        $report = $this->reportService->generateUtilizationReport($start, $end);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }
}
