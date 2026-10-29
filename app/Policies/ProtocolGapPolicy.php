<?php

namespace App\Policies;

use App\Enums\ProgramPermission;
use App\Models\ProtocolGap;
use App\Models\User;
use App\Support\ProgramAccess;

class ProtocolGapPolicy
{
    public function viewAny(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function view(User $user, ProtocolGap $gap): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function create(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageGovernance);
    }

    public function update(User $user, ProtocolGap $gap): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageGovernance);
    }

    public function delete(User $user, ProtocolGap $gap): bool
    {
        return false;
    }
}
