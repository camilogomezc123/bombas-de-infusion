<?php

namespace App\Policies;

use App\Enums\ProgramPermission;
use App\Models\IndicatorDefinition;
use App\Models\User;
use App\Support\ProgramAccess;

class IndicatorDefinitionPolicy
{
    public function viewAny(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewIndicators);
    }

    public function view(User $user, IndicatorDefinition $indicator): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewIndicators);
    }

    public function create(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageGovernance);
    }

    public function update(User $user, IndicatorDefinition $indicator): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageGovernance);
    }

    public function delete(User $user, IndicatorDefinition $indicator): bool
    {
        return false;
    }
}
