<?php

namespace App\Policies;

use App\Models\Boat;
use App\Models\User;

class BoatPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Boat $boat): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Boat $boat): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Boat $boat): bool
    {
        return $user->isAdmin() && $boat->status->value === 'available';
    }

    public function toggleMaintenance(User $user, Boat $boat): bool
    {
        return $user->isAdmin();
    }

    public function startRental(User $user, Boat $boat): bool
    {
        return $user->isWorker() && $boat->status->value === 'available';
    }
}
