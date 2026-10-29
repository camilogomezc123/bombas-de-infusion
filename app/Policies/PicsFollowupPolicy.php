<?php

namespace App\Policies;

use App\Models\PicsFollowup;
use App\Models\User;
use App\Support\ProgramAccess;

class PicsFollowupPolicy
{
    public function viewAny(User $user): bool
    {
        return ProgramAccess::canAccess($user, 'pics');
    }

    public function view(User $user, PicsFollowup $followup): bool
    {
        return ProgramAccess::canAccess($user, 'pics');
    }

    public function create(User $user): bool
    {
        return $user->canManagePicsCases();
    }

    public function update(User $user, PicsFollowup $followup): bool
    {
        return $user->canManagePicsCases();
    }

    public function delete(User $user, PicsFollowup $followup): bool
    {
        return false;
    }
}
