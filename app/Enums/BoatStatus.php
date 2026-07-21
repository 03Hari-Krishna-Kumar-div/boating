<?php

namespace App\Enums;

enum BoatStatus: string
{
    case AVAILABLE = 'available';
    case OCCUPIED = 'occupied';
    case WARNING = 'warning';
    case AWAITING_CONFIRMATION = 'awaiting_confirmation';
    case ENDED = 'ended';
    case OVERDUE = 'overdue';
    case MAINTENANCE = 'maintenance';
    case TIME_UP = 'time_up';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::OCCUPIED => 'Occupied',
            self::WARNING => 'Warning',
            self::AWAITING_CONFIRMATION => 'Awaiting Confirmation',
            self::ENDED => 'Ended',
            self::OVERDUE => 'Overdue',
            self::MAINTENANCE => 'Maintenance',
            self::TIME_UP => 'Time Up',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AVAILABLE => '#28a745',
            self::OCCUPIED => '#0d6efd',
            self::WARNING => '#ffc107',
            self::AWAITING_CONFIRMATION => '#fd7e14',
            self::ENDED => '#6c5ce7',
            self::OVERDUE => '#dc3545',
            self::MAINTENANCE => '#6c757d',
            self::TIME_UP => '#dc3545',
        };
    }

    public static function validTransitions(): array
    {
        return [
            self::AVAILABLE => [self::OCCUPIED, self::MAINTENANCE],
            self::OCCUPIED => [self::WARNING, self::OVERDUE, self::ENDED],
            self::WARNING => [self::TIME_UP, self::ENDED, self::OVERDUE],
            self::TIME_UP => [self::ENDED, self::OCCUPIED],
            self::ENDED => [self::AVAILABLE],
            self::AWAITING_CONFIRMATION => [self::AVAILABLE, self::OVERDUE],
            self::OVERDUE => [self::AVAILABLE, self::ENDED],
            self::MAINTENANCE => [self::AVAILABLE],
        ];
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, self::validTransitions()[$this] ?? []);
    }
}
