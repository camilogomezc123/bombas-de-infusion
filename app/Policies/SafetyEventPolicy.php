<?php

namespace App\Policies;

use App\Enums\ProgramPermission;
use App\Models\SepsisSafetyEvent;
use App\Models\User;
use App\Support\ProgramAccess;

class SafetyEventPolicy
{
    public function viewAny(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function view(User $user, SepsisSafetyEvent $event): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function create(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageGovernance)
            || ProgramAccess::can($user, ProgramPermission::AuditCases);
    }

    public function update(User $user, SepsisSafetyEvent $event): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageGovernance)
            || ProgramAccess::can($user, ProgramPermission::AuditCases);
    }

    public function delete(User $user, SepsisSafetyEvent $event): bool
    {
        return false;
    }
}
