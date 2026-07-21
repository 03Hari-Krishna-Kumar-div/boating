<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}

    public function index()
    {
        $data = $this->dashboardService->getDashboardData(auth()->user());
        return view('dashboard.index', $data);
    }

    public function tv()
    {
        $data = $this->dashboardService->getDashboardData(auth()->user());
        return view('dashboard.tv-mode', $data);
    }
}
