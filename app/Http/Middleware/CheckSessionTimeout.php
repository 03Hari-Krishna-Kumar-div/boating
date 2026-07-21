<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $timeout = config('brms.session_timeout_minutes', 120);
            $threshold = now()->subMinutes($timeout);

            if ($user->last_activity_at && $user->last_activity_at->lt($threshold)) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'Session expired due to inactivity.');
            }

            $user->update(['last_activity_at' => now()]);
        }

        return $next($request);
    }
}
