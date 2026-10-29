<?php

namespace App\Policies;

use App\Enums\ProgramPermission;
use App\Models\AssessmentFinding;
use App\Models\User;
use App\Support\ProgramAccess;

class FindingPolicy
{
    public function viewAny(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function view(User $user, AssessmentFinding $finding): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function create(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageStandards)
            || ProgramAccess::can($user, ProgramPermission::ManageGovernance);
    }

    public function update(User $user, AssessmentFinding $finding): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageStandards)
            || ProgramAccess::can($user, ProgramPermission::ManageGovernance);
    }

    public function delete(User $user, AssessmentFinding $finding): bool
    {
        return false;
    }
}
