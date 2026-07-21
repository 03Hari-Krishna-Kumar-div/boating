<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class RentalController extends Controller
{
    public function __construct(private ActivityLogService $activityLogService) {}

    public function index(Request $request)
    {
        $query = Rental::with(['boat', 'worker', 'endedBy']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('boat', function ($b) use ($search) {
                    $b->where('boat_number', 'like', "%{$search}%");
                })->orWhereHas('worker', function ($w) use ($search) {
                    $w->where('name', 'like', "%{$search}%")
                      ->orWhere('id', 'like', "%{$search}%");
                });
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->where('started_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->where('started_at', '<=', $dateTo . ' 23:59:59');
        }

        $perPage = (int) $request->get('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;

        $rentals = $query->latest()->paginate($perPage);

        return view('admin.rentals.index', compact('rentals'));
    }
}
