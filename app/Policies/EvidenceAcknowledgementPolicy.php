<?php

namespace App\Policies;

use App\Enums\ProgramPermission;
use App\Models\EvidenceAcknowledgement;
use App\Models\User;
use App\Support\ProgramAccess;

class EvidenceAcknowledgementPolicy
{
    public function viewAny(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function view(User $user, EvidenceAcknowledgement $acknowledgement): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function create(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageEvidence);
    }

    public function update(User $user, EvidenceAcknowledgement $acknowledgement): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageEvidence);
    }

    public function delete(User $user, EvidenceAcknowledgement $acknowledgement): bool
    {
        return false;
    }
}
