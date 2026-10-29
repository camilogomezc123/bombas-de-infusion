<?php

namespace App\Policies;

use App\Enums\ProgramPermission;
use App\Models\CommitteeMeeting;
use App\Models\User;
use App\Support\ProgramAccess;

class CommitteeMeetingPolicy
{
    public function viewAny(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function view(User $user, CommitteeMeeting $meeting): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ViewProgram);
    }

    public function create(User $user): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageGovernance);
    }

    public function update(User $user, CommitteeMeeting $meeting): bool
    {
        return ProgramAccess::can($user, ProgramPermission::ManageGovernance);
    }

    public function delete(User $user, CommitteeMeeting $meeting): bool
    {
        return false;
    }
}
