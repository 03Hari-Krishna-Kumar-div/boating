<?php

namespace App\Models;

use App\Enums\BoatStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Boat extends Model
{
    protected $fillable = [
        'boat_number',
        'name',
        'status',
        'current_rental_id',
        'color_hex',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => BoatStatus::class,
            'boat_number' => 'integer',
        ];
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class, 'boat_id');
    }

    public function currentRental(): BelongsTo
    {
        return $this->belongsTo(Rental::class, 'current_rental_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'boat_id');
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class, 'boat_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', BoatStatus::AVAILABLE);
    }

    public function scopeOccupied($query)
    {
        return $query->whereIn('status', [BoatStatus::OCCUPIED, BoatStatus::WARNING]);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('boat_number', 'like', "%{$term}%")
              ->orWhere('name', 'like', "%{$term}%");
        });
    }
}
