<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Boat;
use App\Models\MaintenanceRecord;
use App\Enums\BoatStatus;
use App\Http\Requests\StoreBoatRequest;
use App\Http\Requests\UpdateBoatRequest;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class BoatController extends Controller
{
    public function __construct(private ActivityLogService $activityLogService) {}

    public function index(Request $request)
    {
        $query = Boat::with(['currentRental.worker'])
            ->withCount('rentals');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('boat_number', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->get('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;

        $boats = $query->orderBy('boat_number')->paginate($perPage);
        return view('admin.boats.index', compact('boats'));
    }

    public function create()
    {
        return view('admin.boats.create');
    }

    public function store(StoreBoatRequest $request)
    {
        $boat = Boat::create($request->validated());

        $this->activityLogService->log('boat_created', auth()->user(), $boat, null,
            "Boat #{$boat->boat_number} created");

        return redirect()->route('admin.boats.index')
            ->with('success', 'Boat created successfully.');
    }

    public function edit(Boat $boat)
    {
        return view('admin.boats.edit', compact('boat'));
    }

    public function update(UpdateBoatRequest $request, Boat $boat)
    {
        $boat->update($request->validated());

        return redirect()->route('admin.boats.index')
            ->with('success', 'Boat updated successfully.');
    }

    public function destroy(Boat $boat)
    {
        if ($boat->status->value !== 'available') {
            return redirect()->route('admin.boats.index')
                ->with('error', 'Cannot delete a boat that is not available.');
        }

        $boat->delete();

        return redirect()->route('admin.boats.index')
            ->with('success', 'Boat deleted successfully.');
    }

    public function toggleMaintenance(Request $request, Boat $boat)
    {
        $request->validate(['notes' => 'nullable|string']);

        if ($boat->status === BoatStatus::MAINTENANCE) {
            $boat->update(['status' => BoatStatus::AVAILABLE]);

            if ($boat->currentRental) {
                MaintenanceRecord::where('boat_id', $boat->id)
                    ->whereNull('ended_at')
                    ->update(['ended_at' => now(), 'notes' => $request->notes]);
            }

            $this->activityLogService->log('boat_maintenance_off', auth()->user(), $boat, null,
                "Boat #{$boat->boat_number} removed from maintenance");
        } else {
            if ($boat->status !== BoatStatus::AVAILABLE) {
                return redirect()->route('admin.boats.index')
                    ->with('error', 'Cannot put a rented boat into maintenance.');
            }

            $boat->update(['status' => BoatStatus::MAINTENANCE]);

            MaintenanceRecord::create([
                'boat_id' => $boat->id,
                'admin_id' => auth()->id(),
                'started_at' => now(),
                'notes' => $request->notes,
            ]);

            $this->activityLogService->log('boat_maintenance_on', auth()->user(), $boat, null,
                "Boat #{$boat->boat_number} sent to maintenance");
        }

        return redirect()->route('admin.boats.index')
            ->with('success', 'Boat maintenance status updated.');
    }
}
