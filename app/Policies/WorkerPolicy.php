<?php

namespace App\Policies;

use App\Models\User;

class WorkerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $worker): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $worker): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, User $worker): bool
    {
        return $user->isAdmin() && $worker->role->value === 'worker';
    }

    public function toggleStatus(User $user, User $worker): bool
    {
        return $user->isAdmin();
    }

    public function resetPassword(User $user, User $worker): bool
    {
        return $user->isAdmin();
    }
}
