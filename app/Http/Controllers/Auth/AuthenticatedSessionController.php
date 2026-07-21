<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\FailedLogin;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private ActivityLogService $activityLogService) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();
            $request->session()->regenerate();

            // Log successful login
            $this->activityLogService->log('login_success', Auth::user(), null, null,
                'User logged in successfully');

            // Update last activity
            Auth::user()->update(['last_activity_at' => now()]);

            return redirect()->intended(route('dashboard', absolute: false));
        } catch (\Exception $e) {
            // Log failed login
            FailedLogin::create([
                'email' => $request->email,
                'ip_address' => $request->ip(),
                'attempted_at' => now(),
            ]);

            $this->activityLogService->log('login_failed', null, null, null,
                "Failed login attempt for {$request->email}");

            throw $e;
        }
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            $this->activityLogService->log('logout', $user, null, null,
                'User logged out');
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
