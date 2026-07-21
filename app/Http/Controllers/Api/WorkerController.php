<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workers = User::where('role', 'worker')
            ->select(['id', 'name', 'email', 'is_active'])
            ->withCount(['rentals as active_rentals_count' => function ($q) {
                $q->whereIn('status', ['active', 'overdue']);
            }])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $workers,
        ]);
    }

    public function online(): JsonResponse
    {
        try {
            $onlineWorkers = User::workers()->online()
                ->select(['id', 'name'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $onlineWorkers,
                'count' => $onlineWorkers->count(),
                'server_time' => now()->format('Y-m-d\TH:i:s.u\Z'),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Online workers error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => [],
                'count' => 0,
                'message' => 'Unable to fetch online workers.',
            ], 500);
        }
    }
}
