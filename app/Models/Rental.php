<?php

namespace App\Models;

use App\Enums\RentalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rental extends Model
{
    protected $fillable = [
        'boat_id',
        'worker_id',
        'started_at',
        'expected_end_at',
        'ended_at',
        'actual_end_at',
        'received_at',
        'received_by_worker_id',
        'overtime_seconds',
        'status',
        'ended_by',
        'customer_returned',
        'notes',
        'extended_minutes',
        'reduced_minutes',
        'end_reason',
        'admin_override',
        'extended_until',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expected_end_at' => 'datetime',
            'ended_at' => 'datetime',
            'actual_end_at' => 'datetime',
            'received_at' => 'datetime',
            'extended_until' => 'datetime',
            'status' => RentalStatus::class,
            'customer_returned' => 'boolean',
            'admin_override' => 'boolean',
            'overtime_seconds' => 'integer',
            'extended_minutes' => 'integer',
            'reduced_minutes' => 'integer',
        ];
    }

    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class, 'boat_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_worker_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'rental_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', RentalStatus::ACTIVE);
    }

    public function scopeEnded($query)
    {
        return $query->where('status', RentalStatus::ENDED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', RentalStatus::COMPLETED);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', RentalStatus::OVERDUE);
    }

    public function scopeForWorker($query, $workerId)
    {
        return $query->where('worker_id', $workerId);
    }

    public function scopeForBoat($query, $boatId)
    {
        return $query->where('boat_id', $boatId);
    }

    public function scopeForDateRange($query, $start, $end)
    {
        return $query->whereBetween('started_at', [$start, $end]);
    }

    public function getRemainingSecondsAttribute(): int
    {
        $end = $this->extended_until ?? $this->expected_end_at;
        return max(0, now()->diffInSeconds($end, false));
    }

    public function getEffectiveEndAtAttribute()
    {
        return $this->extended_until ?? $this->expected_end_at;
    }

    public function isOwnedBy($userId): bool
    {
        return $this->worker_id === (int) $userId;
    }
}
