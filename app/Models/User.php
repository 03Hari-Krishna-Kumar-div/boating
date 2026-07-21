<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Traits\LogsActivity;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use Notifiable, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_activity_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'last_activity_at' => 'datetime',
            'email_verified_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isWorker(): bool
    {
        return $this->role === UserRole::WORKER;
    }

    public function isOnline(): bool
    {
        if (!$this->last_activity_at) return false;
        return $this->last_activity_at->diffInSeconds(now()) < config('brms.online_threshold_seconds', 10);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class, 'worker_id');
    }

    public function endedRentals(): HasMany
    {
        return $this->hasMany(Rental::class, 'ended_by');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class, 'admin_id');
    }

    public function settingsUpdated(): HasMany
    {
        return $this->hasMany(Setting::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWorkers($query)
    {
        return $query->where('role', 'worker');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeOnline($query)
    {
        $threshold = now()->subSeconds(config('brms.online_threshold_seconds', 10));
        return $query->where('last_activity_at', '>=', $threshold);
    }
}
