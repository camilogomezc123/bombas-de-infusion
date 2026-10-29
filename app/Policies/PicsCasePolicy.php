<?php

namespace App\Policies;

use App\Enums\ProgramPermission;
use App\Enums\ProgramRole;
use App\Models\PicsCase;
use App\Models\User;
use App\Support\ProgramAccess;

class PicsCasePolicy
{
    public function viewAny(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewCases, 'pics');
    }

    public function view(User $user, PicsCase $case): bool
    {
        if (! ProgramAccess::can($user, ProgramPermission::ViewCases, 'pics')) {
            return false;
        }

        if ($user->isAdministrator() || ! ProgramAccess::hasRole($user, 'pics', ProgramRole::Auditor)) {
            return true;
        }

        return $case->assigned_auditor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::CreateCases, 'pics');
    }

    public function update(User $user, PicsCase $case): bool
    {
        if ($case->is_cancelled) {
            return false;
        }

        if (ProgramAccess::can($user, ProgramPermission::ApproveCases, 'pics')) {
            return true;
        }

        if (ProgramAccess::hasRole($user, 'pics', ProgramRole::Auditor)) {
            return ProgramAccess::can($user, ProgramPermission::EditCases, 'pics')
                && $case->assigned_auditor_id === $user->id
                && $case->status->isAuditorEditable();
        }

        return ProgramAccess::can($user, ProgramPermission::EditCases, 'pics');
    }

    public function delete(User $user, PicsCase $case): bool
    {
        return false;
    }
}
