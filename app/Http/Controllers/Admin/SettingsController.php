<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(private ActivityLogService $activityLogService) {}

    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'rental_duration_minutes' => 'required|integer|min:1|max:480',
            'warning_minutes' => 'required|integer|min:1|max:60',
            'alarm_interval_seconds' => 'required|integer|min:1|max:10',
            'session_timeout_minutes' => 'required|integer|min:5|max:480',
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'updated_by' => auth()->id()]
            );
        }

        // Also update config
        foreach ($validated as $key => $value) {
            config(["brms.{$key}" => $value]);
        }

        $this->activityLogService->log('settings_updated', auth()->user(), null, null,
            'System settings updated');

        return redirect()->route('admin.settings.index')
            ->with('success', 'Settings updated successfully.');
    }
}
