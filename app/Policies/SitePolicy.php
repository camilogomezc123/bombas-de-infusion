<?php

namespace App\Policies;

use App\Enums\ProgramPermission;
use App\Models\Site;
use App\Models\User;
use App\Support\ProgramAccess;

class SitePolicy
{
    public function viewAny(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function view(User $user, Site $site): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function create(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageGovernance);
    }

    public function update(User $user, Site $site): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageGovernance);
    }

    public function delete(User $user, Site $site): bool
    {
        return false;
    }
}
