<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Http\Resources\DashboardResource;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}

    public function index(): JsonResponse
    {
        $data = $this->dashboardService->getDashboardData(auth()->user());

        return response()->json([
            'server_time' => now()->format('Y-m-d\TH:i:s.u\Z'),
            'boats' => $data['boats'],
            'stats' => $data['stats'],
            'notifications' => $data['notifications'],
        ]);
    }
}
