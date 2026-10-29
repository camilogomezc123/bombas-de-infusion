<?php

namespace App\Policies;

use App\Enums\ProgramPermission;
use App\Models\ProgramCommittee;
use App\Models\User;
use App\Support\ProgramAccess;

class ProgramCommitteePolicy
{
    public function viewAny(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function view(User $user, ProgramCommittee $committee): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function create(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageGovernance);
    }

    public function update(User $user, ProgramCommittee $committee): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageGovernance);
    }

    public function delete(User $user, ProgramCommittee $committee): bool
    {
        return false;
    }
}
