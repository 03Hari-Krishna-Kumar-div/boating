<?php

namespace App\Enums;

enum RentalStatus: string
{
    case ACTIVE = 'active';
    case ENDED = 'ended';
    case COMPLETED = 'completed';
    case OVERDUE = 'overdue';
    case OVERRIDDEN = 'overridden';
    case AWAITING_CONFIRMATION = 'awaiting_confirmation';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::ENDED => 'Ended',
            self::COMPLETED => 'Completed',
            self::OVERDUE => 'Overdue',
            self::OVERRIDDEN => 'Overridden',
            self::AWAITING_CONFIRMATION => 'Awaiting Confirmation',
        };
    }
}
