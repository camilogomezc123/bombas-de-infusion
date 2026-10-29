<?php

namespace App\Policies;

use App\Enums\ProgramPermission;
use App\Models\ClinicalProgram;
use App\Models\User;
use App\Support\ProgramAccess;

class ClinicalProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function view(User $user, ClinicalProgram $program): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function update(User $user, ClinicalProgram $program): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageProgram);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, ClinicalProgram $program): bool
    {
        return false;
    }
}
