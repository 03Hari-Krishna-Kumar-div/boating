<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Exports\ActivityLogExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function __construct(private ActivityLogService $activityLogService) {}

    public function index(Request $request)
    {
        $filters = $request->only(['action', 'user_id', 'boat_id', 'search', 'date_from', 'date_to']);

        $perPage = (int) $request->get('per_page', 50);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 50;

        $logs = $this->activityLogService->getLogs($filters, $perPage);

        return view('admin.activity-logs.index', compact('logs'));
    }

    public function export(Request $request)
    {
        $filters = $request->only(['action', 'date_from', 'date_to']);
        $filename = "brms-activity-logs-" . now()->format('Y-m-d');

        return Excel::download(
            new ActivityLogExport($filters),
            $filename . '.xlsx'
        );
    }
}
