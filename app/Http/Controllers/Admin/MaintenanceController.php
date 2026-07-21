<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRecord;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function __construct(private ActivityLogService $activityLogService) {}

    public function index(Request $request)
    {
        $query = MaintenanceRecord::with(['boat', 'admin']);

        if ($search = $request->get('search')) {
            $query->whereHas('boat', fn($q) => $q->where('boat_number', 'like', "%{$search}%"));
        }

        $records = $query->latest('started_at')->paginate(15);
        return view('admin.maintenance.index', compact('records'));
    }
}
