<?php

namespace App\Policies;

use App\Enums\ProgramPermission;
use App\Models\AccreditationStandard;
use App\Models\User;
use App\Support\ProgramAccess;

class QualityStandardPolicy
{
    public function viewAny(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function view(User $user, AccreditationStandard $standard): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function create(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageStandards);
    }

    public function update(User $user, AccreditationStandard $standard): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageStandards);
    }

    public function delete(User $user, AccreditationStandard $standard): bool
    {
        return false;
    }
}
