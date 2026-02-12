<?php

namespace App\Policies;

use App\Models\User;

class AdminOnlyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function view(User $user, mixed $record): bool
    {
        return $user->is_admin === true;
    }

    public function create(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function update(User $user, mixed $record): bool
    {
        return $user->is_admin === true;
    }

    public function delete(User $user, mixed $record): bool
    {
        return $user->is_admin === true;
    }

    public function deleteAny(User $user): bool
    {
        return $user->is_admin === true;
    }
}
