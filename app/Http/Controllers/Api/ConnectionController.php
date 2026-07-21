<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ConnectionController extends Controller
{
    public function ping(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'server_time' => now()->format('Y-m-d\TH:i:s.u\Z'),
            'server_timestamp' => now()->timestamp,
            'status' => 'connected',
            'user' => auth()->user()?->only(['id', 'name', 'email', 'role']),
        ]);
    }
}
