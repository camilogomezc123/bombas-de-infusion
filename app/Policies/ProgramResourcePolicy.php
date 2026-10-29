<?php

namespace App\Policies;

use App\Enums\ProgramPermission;
use App\Models\ProgramResource;
use App\Models\User;
use App\Support\ProgramAccess;

class ProgramResourcePolicy
{
    public function viewAny(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function view(User $user, ProgramResource $resource): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function create(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageGovernance);
    }

    public function update(User $user, ProgramResource $resource): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageGovernance);
    }

    public function delete(User $user, ProgramResource $resource): bool
    {
        return false;
    }
}
