<?php

namespace App\Policies;

use App\Enums\ProgramPermission;
use App\Models\Competency;
use App\Models\User;
use App\Support\ProgramAccess;

class CompetencyPolicy
{
    public function viewAny(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function view(User $user, Competency $competency): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function create(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageProgramUsers);
    }

    public function update(User $user, Competency $competency): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageProgramUsers);
    }

    public function delete(User $user, Competency $competency): bool
    {
        return false;
    }
}
