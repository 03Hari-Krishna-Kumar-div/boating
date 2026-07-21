<?php

namespace App\Policies;

use App\Models\Rental;
use App\Models\User;

class RentalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Rental $rental): bool
    {
        return $user->isAdmin() || $rental->worker_id === $user->id;
    }

    public function end(User $user, Rental $rental): bool
    {
        return $rental->worker_id === $user->id || $user->isAdmin();
    }

    public function forceEnd(User $user, Rental $rental): bool
    {
        return $user->isAdmin();
    }

    public function confirmReturn(User $user, Rental $rental): bool
    {
        return $rental->worker_id === $user->id;
    }
}
