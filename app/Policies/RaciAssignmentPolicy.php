<?php

namespace App\Policies;

use App\Enums\ProgramPermission;
use App\Models\RaciAssignment;
use App\Models\User;
use App\Support\ProgramAccess;

class RaciAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function view(User $user, RaciAssignment $assignment): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function create(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageGovernance);
    }

    public function update(User $user, RaciAssignment $assignment): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageGovernance);
    }

    public function delete(User $user, RaciAssignment $assignment): bool
    {
        return false;
    }
}
