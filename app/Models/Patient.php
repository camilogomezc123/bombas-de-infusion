<?php

namespace App\Models;

use App\Concerns\PortalAccountAuthenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['identification', 'full_name', 'sex', 'age', 'email', 'portal_username', 'password', 'must_change_password', 'has_seen_portal_tour', 'portal_easy_mode'])]
#[Hidden(['password', 'remember_token'])]
class Patient extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    use PortalAccountAuthenticatable;

    public function portalGuardName(): string
    {
        return 'patient';
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'has_seen_portal_tour' => 'boolean',
            'portal_easy_mode' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function cases(): HasMany
    {
        return $this->hasMany(AcvCase::class);
    }

    public function picsCases(): HasMany
    {
        return $this->hasMany(PicsCase::class);
    }

    public function goalProgressReports(): MorphMany
    {
        return $this->morphMany(GoalProgressReport::class, 'reporter');
    }

    public function diaryEntries(): MorphMany
    {
        return $this->morphMany(DiaryEntry::class, 'authorable');
    }

    public function pushSubscriptions(): MorphMany
    {
        return $this->morphMany(PushSubscription::class, 'subscriber');
    }
}
