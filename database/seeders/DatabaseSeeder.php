<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Boat;
use App\Models\Setting;
use App\Enums\UserRole;
use App\Enums\BoatStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@brms.local',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
            'is_active' => true,
        ]);

        // Create worker users
        $workers = [];
        $workerNames = ['John Smith', 'Maria Garcia', 'David Lee', 'Sarah Johnson', 'Mike Brown',
                        'Lisa Davis', 'James Wilson', 'Emma Taylor', 'Robert Anderson', 'Sophie Martin'];

        foreach ($workerNames as $i => $name) {
            $workers[] = User::create([
                'name' => $name,
                'email' => 'worker' . ($i + 1) . '@brms.local',
                'password' => Hash::make('password'),
                'role' => UserRole::WORKER,
                'is_active' => true,
                'last_activity_at' => now()->subMinutes(rand(0, 30)),
            ]);
        }

        // Create boats
        $boatNames = ['Speedster', 'Wave Rider', 'Ocean King', 'Sea Breeze', 'Storm Chaser',
                      'Sun Seeker', 'Blue Marlin', 'Coastal Queen', 'Harbor Master', 'Deep Blue',
                      'Coral Explorer', 'Tide Runner', 'Anchor\'s Away', 'North Star', 'Pelican',
                      'Sandpiper', 'Whale Song', 'Dolphin Dance', 'Mermaid', 'Sea Foam'];

        foreach (range(1, 20) as $i) {
            Boat::create([
                'boat_number' => $i,
                'name' => $boatNames[$i - 1] ?? null,
                'status' => BoatStatus::AVAILABLE,
                'color_hex' => sprintf('#%06X', rand(0, 0xFFFFFF)),
            ]);
        }

        // Create default settings
        $defaultSettings = [
            ['key' => 'rental_duration_minutes', 'value' => '45', 'updated_by' => $admin->id],
            ['key' => 'warning_minutes', 'value' => '5', 'updated_by' => $admin->id],
            ['key' => 'alarm_interval_seconds', 'value' => '1', 'updated_by' => $admin->id],
            ['key' => 'session_timeout_minutes', 'value' => '120', 'updated_by' => $admin->id],
        ];

        foreach ($defaultSettings as $setting) {
            Setting::create($setting);
        }
    }
}
