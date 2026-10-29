<?php

namespace App\Support;

use App\Enums\ProgramPermission;
use App\Enums\ProgramRole;
use App\Models\ClinicalProgram;
use App\Models\ProgramMember;
use App\Models\User;

class ProgramAccess
{
    public static function currentCode(): string
    {
        return app(ProgramContext::class)->code();
    }

    public static function program(?string $code = null): ?ClinicalProgram
    {
        return app(ProgramContext::class)->program($code);
    }

    public static function membership(User $user, ?string $code = null): ?ProgramMember
    {
        $code ??= self::currentCode();
        if (! $user->is_active) {
            return null;
        }

        return ProgramMember::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('starts_on')->orWhere('starts_on', '<=', today()))
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhere('ends_on', '>=', today()))
            ->whereHas('program', fn ($query) => $query
                ->whereRaw('upper(code) = ?', [mb_strtoupper($code)])
                ->where('is_active', true))
            ->first();
    }

    public static function can(User $user, ProgramPermission $permission, ?string $code = null): bool
    {
        $code ??= self::currentCode();
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdministrator()) {
            return true;
        }

        $membership = self::membership($user, $code);

        if (! $membership) {
            return false;
        }

        $permissions = $membership->permissions
            ?: array_map(fn (ProgramPermission $item) => $item->value, $membership->role->defaultPermissions());

        return in_array($permission->value, $permissions, true);
    }

    public static function canAccess(User $user, ?string $code = null): bool
    {
        return self::can($user, ProgramPermission::ViewProgram, $code);
    }

    public static function canManageCases(User $user, ?string $code = null): bool
    {
        return self::can($user, ProgramPermission::ApproveCases, $code);
    }

    public static function canManageCompliance(User $user, ?string $code = null): bool
    {
        return self::can($user, ProgramPermission::ManageStandards, $code);
    }

    public static function hasRole(User $user, string $code, ProgramRole $role): bool
    {
        return self::membership($user, $code)?->role === $role;
    }
}
