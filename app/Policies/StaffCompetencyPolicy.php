<?php

namespace App\Policies;

use App\Enums\ProgramPermission;
use App\Models\StaffCompetency;
use App\Models\User;
use App\Support\ProgramAccess;

class StaffCompetencyPolicy
{
    public function viewAny(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function view(User $user, StaffCompetency $staffCompetency): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function create(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageProgramUsers);
    }

    public function update(User $user, StaffCompetency $staffCompetency): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageProgramUsers);
    }

    public function delete(User $user, StaffCompetency $staffCompetency): bool
    {
        return false;
    }
}
