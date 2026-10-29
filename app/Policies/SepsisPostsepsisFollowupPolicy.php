<?php

namespace App\Policies;

use App\Models\SepsisPostsepsisFollowup;
use App\Models\User;
use App\Support\ProgramAccess;

class SepsisPostsepsisFollowupPolicy
{
    public function viewAny(User $user): bool
    {
        return ProgramAccess::canAccess($user, 'sepsis');
    }

    public function view(User $user, SepsisPostsepsisFollowup $followup): bool
    {
        return ProgramAccess::canAccess($user, 'sepsis');
    }

    public function create(User $user): bool
    {
        return $user->canManageSepsisCases();
    }

    public function update(User $user, SepsisPostsepsisFollowup $followup): bool
    {
        return $user->canManageSepsisCases();
    }

    public function delete(User $user, SepsisPostsepsisFollowup $followup): bool
    {
        return false;
    }
}
