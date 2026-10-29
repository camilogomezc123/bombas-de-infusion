<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Support\ProgramAccess;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'role', 'is_active', 'must_change_password', 'records_per_page', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasName
{
    public const USER_MANAGEMENT_EMAILS = [
        'alexandertorresviveros@gmail.com',
        'programa.acv@clinicadeoccidente.com',
    ];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'records_per_page' => 'integer',
            'password' => 'hashed',
        ];
    }

    public function recordsPerPage(): int
    {
        return $this->records_per_page ?: 50;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (in_array($panel->getId(), ['sepsis', 'infarto', 'tep', 'pics'], true)) {
            return ProgramAccess::canAccess($this, $panel->getId());
        }

        return true;
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function isAdministrator(): bool
    {
        return $this->role === UserRole::Administrator;
    }

    public function canManageAllCases(): bool
    {
        return in_array($this->role, [UserRole::Administrator, UserRole::Leader], true);
    }

    public function canManageUsers(): bool
    {
        return $this->is_active
            && in_array($this->role, [UserRole::Administrator, UserRole::Leader], true)
            && in_array(mb_strtolower((string) $this->email), self::USER_MANAGEMENT_EMAILS, true);
    }

    public function programMemberships(): HasMany
    {
        return $this->hasMany(ProgramMember::class);
    }

    public function canManageSepsisCases(): bool
    {
        return ProgramAccess::canManageCases($this, 'sepsis');
    }

    public function canManageSepsisCompliance(): bool
    {
        return ProgramAccess::canManageCompliance($this, 'sepsis');
    }

    public function canManageInfartoCases(): bool
    {
        return ProgramAccess::canManageCases($this, 'infarto');
    }

    public function canManageTepCases(): bool
    {
        return ProgramAccess::canManageCases($this, 'tep');
    }

    public function canManagePicsCases(): bool
    {
        return ProgramAccess::canManageCases($this, 'pics');
    }

    public function canManagePicsCompliance(): bool
    {
        return ProgramAccess::canManageCompliance($this, 'pics');
    }
}
