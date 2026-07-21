<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Boat;
use App\Http\Resources\BoatResource;
use App\Services\RentalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BoatController extends Controller
{
    public function __construct(private RentalService $rentalService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $boats = Boat::with(['currentRental.worker'])
                ->withCount('rentals')
                ->orderBy('boat_number')
                ->get();

            return response()->json([
                'success' => true,
                'data' => BoatResource::collection($boats),
            ]);
        } catch (\Exception $e) {
            Log::error('Boats index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Unable to load boats.',
            ], 500);
        }
    }

    public function show(Boat $boat): JsonResponse
    {
        try {
            $boat->load(['currentRental.worker']);
            $boat->loadCount('rentals');

            return response()->json([
                'success' => true,
                'data' => new BoatResource($boat),
            ]);
        } catch (\Exception $e) {
            Log::error('Boat show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Unable to load boat details.',
            ], 500);
        }
    }

    public function moveToMaintenance(Request $request, Boat $boat): JsonResponse
    {
        try {
            if (!$request->user()->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
            }

            $boat = $this->rentalService->moveToMaintenance($boat, $request->user());

            return response()->json([
                'success' => true,
                'message' => "Boat {$boat->boat_number} moved to maintenance.",
                'data' => new BoatResource($boat),
            ]);
        } catch (\Exception $e) {
            Log::error('Maintenance error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Unable to update maintenance status.',
            ], 500);
        }
    }

    public function removeFromMaintenance(Request $request, Boat $boat): JsonResponse
    {
        try {
            if (!$request->user()->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
            }

            $boat = $this->rentalService->removeFromMaintenance($boat, $request->user());

            return response()->json([
                'success' => true,
                'message' => "Boat {$boat->boat_number} is now available.",
                'data' => new BoatResource($boat),
            ]);
        } catch (\Exception $e) {
            Log::error('Remove from maintenance error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Unable to update availability.',
            ], 500);
        }
    }
}
