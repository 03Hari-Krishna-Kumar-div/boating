<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Boat;
use App\Models\Rental;
use App\Models\User;
use App\Http\Requests\StartRentalRequest;
use App\Http\Requests\EndRentalRequest;
use App\Http\Requests\ConfirmReturnRequest;
use App\Http\Resources\RentalResource;
use App\Services\RentalService;
use App\Exceptions\BoatNotAvailableException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RentalController extends Controller
{
    public function __construct(private RentalService $rentalService) {}

    public function start(StartRentalRequest $request): JsonResponse
    {
        try {
            $boat = Boat::findOrFail($request->boat_id);
            $rental = $this->rentalService->startRental($boat, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Rental started successfully.',
                'rental' => new RentalResource($rental),
            ], 201);
        } catch (BoatNotAvailableException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        } catch (\Exception $e) {
            Log::error('Rental start error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function end(EndRentalRequest $request, Rental $rental): JsonResponse
    {
        try {
            // Strict ownership check: only the owning worker OR admin can end
            if (!$request->user()->isAdmin() && !$rental->isOwnedBy($request->user()->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to end this rental. Only the worker who started it or an admin can end it.',
                ], 403);
            }

            $rental = $this->rentalService->endRental($rental, $request->user(), $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Rental ended successfully. Boat is awaiting receipt confirmation.',
                'rental' => new RentalResource($rental),
            ]);
        } catch (\Exception $e) {
            Log::error('Rental end error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark rental as received (boat becomes available).
     */
    public function markReceived(Request $request, Rental $rental): JsonResponse
    {
        try {
            if (!$request->user()->isAdmin() && !$rental->isOwnedBy($request->user()->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to receive this boat.',
                ], 403);
            }

            if ($rental->status->value !== 'ended') {
                return response()->json([
                    'success' => false,
                    'message' => 'Rental must be in ENDED status to receive the boat.',
                ], 400);
            }

            $rental = $this->rentalService->markReceived($rental, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Boat received and now available.',
                'rental' => new RentalResource($rental),
            ]);
        } catch (\Exception $e) {
            Log::error('Mark received error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Transfer rental ownership to another worker (admin only).
     */
    public function transfer(Request $request, Rental $rental): JsonResponse
    {
        $request->validate([
            'worker_id' => 'required|integer|exists:users,id',
        ]);

        try {
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can transfer ownership.',
                ], 403);
            }

            $newWorker = User::findOrFail($request->worker_id);
            $rental = $this->rentalService->transferOwnership($rental, $newWorker, $request->user());

            return response()->json([
                'success' => true,
                'message' => "Boat ownership transferred to {$newWorker->name}.",
                'rental' => new RentalResource($rental),
            ]);
        } catch (\Exception $e) {
            Log::error('Transfer error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function confirmReturn(ConfirmReturnRequest $request, Rental $rental): JsonResponse
    {
        try {
            if (!$this->rentalService->checkWorkerOwnership($rental, $request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to confirm this rental.',
                ], 403);
            }

            if ($request->returned) {
                $rental = $this->rentalService->confirmReturn($rental, $request->user());
                return response()->json([
                    'success' => true,
                    'message' => 'Return confirmed. Rental ended.',
                    'rental' => new RentalResource($rental),
                ]);
            } else {
                $rental = $this->rentalService->markStillOut($rental, $request->user());
                return response()->json([
                    'success' => true,
                    'message' => 'Boat marked as still out. Overtime started.',
                    'rental' => new RentalResource($rental),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Rental confirm error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function markStillOut(Request $request, Rental $rental): JsonResponse
    {
        try {
            if (!$this->rentalService->checkWorkerOwnership($rental, $request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authorized.',
                ], 403);
            }

            $rental = $this->rentalService->markStillOut($rental, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Boat marked as still out.',
                'rental' => new RentalResource($rental),
            ]);
        } catch (\Exception $e) {
            Log::error('Mark still out error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function myRentals(Request $request): JsonResponse
    {
        try {
            $rentals = Rental::with(['boat'])
                ->where('worker_id', $request->user()->id)
                ->latest()
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => RentalResource::collection($rentals),
                'pagination' => [
                    'total' => $rentals->total(),
                    'per_page' => $rentals->perPage(),
                    'current_page' => $rentals->currentPage(),
                    'last_page' => $rentals->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('My rentals error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extend rental time.
     */
    public function extend(Request $request, Rental $rental): JsonResponse
    {
        $request->validate([
            'minutes' => 'required|integer|min:1|max:120',
        ]);

        try {
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can extend rental time.',
                ], 403);
            }

            $rental = $this->rentalService->extendTime($rental, $request->user(), (int) $request->minutes);

            return response()->json([
                'success' => true,
                'message' => "Rental extended by {$request->minutes} minutes.",
                'rental' => new RentalResource($rental),
            ]);
        } catch (\Exception $e) {
            Log::error('Extend time error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reduce rental time.
     */
    public function reduce(Request $request, Rental $rental): JsonResponse
    {
        $request->validate([
            'minutes' => 'required|integer|min:1|max:120',
        ]);

        try {
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can reduce rental time.',
                ], 403);
            }

            $rental = $this->rentalService->reduceTime($rental, $request->user(), (int) $request->minutes);

            $isCompleted = in_array($rental->status->value, ['completed', 'overridden']);

            return response()->json([
                'success' => true,
                'message' => $isCompleted
                    ? "Time fully reduced. Rental for Boat #{$rental->boat->boat_number} completed."
                    : "Rental reduced by {$request->minutes} minutes.",
                'rental' => new RentalResource($rental),
            ]);
        } catch (\Exception $e) {
            Log::error('Reduce time error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Force-end a rental (admin only).
     */
    public function forceEnd(Request $request, Rental $rental): JsonResponse
    {
        try {
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can force-end rentals.',
                ], 403);
            }

            $rental = $this->rentalService->forceEnd($rental, $request->user(), $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Rental force-ended successfully.',
                'rental' => new RentalResource($rental),
            ]);
        } catch (\Exception $e) {
            Log::error('Force-end error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: get all active rentals.
     */
    public function allActive(Request $request): JsonResponse
    {
        try {
            $rentals = $this->rentalService->getActiveRentals();

            return response()->json([
                'success' => true,
                'data' => RentalResource::collection($rentals),
            ]);
        } catch (\Exception $e) {
            Log::error('Active rentals error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: complete rental (mark as completed directly).
     */
    public function complete(Request $request, Rental $rental): JsonResponse
    {
        try {
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can complete rentals.',
                ], 403);
            }

            $rental = $this->rentalService->adminCompleteRental($rental, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Rental marked as completed.',
                'rental' => new RentalResource($rental),
            ]);
        } catch (\Exception $e) {
            Log::error('Complete rental error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
