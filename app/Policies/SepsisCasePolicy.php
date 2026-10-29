<?php

namespace App\Policies;

use App\Enums\ProgramPermission;
use App\Enums\ProgramRole;
use App\Models\SepsisCase;
use App\Models\User;
use App\Support\ProgramAccess;

class SepsisCasePolicy
{
    public function viewAny(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewCases);
    }

    public function view(User $user, SepsisCase $case): bool
    {
        if (! ProgramAccess::can($user, ProgramPermission::ViewCases)) {
            return false;
        }

        if ($user->isAdministrator() || ! ProgramAccess::hasRole($user, 'sepsis', ProgramRole::Auditor)) {
            return true;
        }

        return $case->assigned_auditor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::CreateCases);
    }

    public function update(User $user, SepsisCase $case): bool
    {
        if ($case->is_cancelled) {
            return false;
        }

        if (ProgramAccess::can($user, ProgramPermission::ApproveCases)) {
            return true;
        }

        if (ProgramAccess::hasRole($user, 'sepsis', ProgramRole::Auditor)) {
            return ProgramAccess::can($user, ProgramPermission::EditCases)
                && $case->assigned_auditor_id === $user->id
                && $case->status->isAuditorEditable();
        }

        return ProgramAccess::can($user, ProgramPermission::EditCases);
    }

    public function delete(User $user, SepsisCase $case): bool
    {
        return false;
    }
}
