<?php

namespace App\Policies;

use App\Enums\ProgramPermission;
use App\Models\EvidenceDocument;
use App\Models\User;
use App\Support\ProgramAccess;

class ComplianceEvidencePolicy
{
    public function viewAny(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function view(User $user, EvidenceDocument $evidence): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function create(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageEvidence);
    }

    public function update(User $user, EvidenceDocument $evidence): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageEvidence);
    }

    public function delete(User $user, EvidenceDocument $evidence): bool
    {
        return false;
    }
}
