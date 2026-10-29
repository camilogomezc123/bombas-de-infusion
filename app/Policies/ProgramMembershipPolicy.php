<?php

namespace App\Policies;

use App\Enums\ProgramPermission;
use App\Models\ProgramMember;
use App\Models\User;
use App\Support\ProgramAccess;

class ProgramMembershipPolicy
{
    public function viewAny(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageProgramUsers);
    }

    public function view(User $user, ProgramMember $membership): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageProgramUsers);
    }

    public function create(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageProgramUsers);
    }

    public function update(User $user, ProgramMember $membership): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageProgramUsers);
    }

    public function delete(User $user, ProgramMember $membership): bool
    {
        return false;
    }
}
